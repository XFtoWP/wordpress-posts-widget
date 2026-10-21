<?php

namespace Kolakube\WpPostsWidget\Widget;

use Kolakube\WpPostsWidget\Framework;

use XF\Widget\AbstractWidget;

class WpPosts extends AbstractWidget
{
	# image sizes WordPress registers by default, in the order they are offered, plus 'none' to hide images
	const IMAGE_SIZES = ['thumbnail','medium','medium_large','large','full','none'];
	
	# inline tags allowed in titles and excerpts coming from WordPress
	const INLINE_TAGS = ['em' => [], 'i' => [], 'strong' => [], 'b' => [], 'br' => []];
	
	const DEFAULT_LIMIT = 5;
	
	const DEFAULT_CACHE_MINUTES = 5;
	
	# bump to discard cached data after a change to what gets cached
	const CACHE_VERSION = 3;
	
	protected $defaultOptions = [
		'limit' => self::DEFAULT_LIMIT,
		'post_ids' => '',
		'cat_ids' => '',
		'title_url' => '',
		'image_size' => 'medium',
		'cache_minutes' => self::DEFAULT_CACHE_MINUTES
	];
	
	public function render()
	{
		$o = $this->getCleanOptions();
		
		$cache = $o['cache_minutes'] > 0 ? \XF::app()->cache() : null;
		
		$cacheKey = 'kolakube_wp_posts_'.self::CACHE_VERSION.'_'.md5(json_encode([$o['post_ids'], $o['cat_ids'], $o['limit'], $o['image_size']]));
		
		$data = $cache ? $cache->fetch($cacheKey) : false;
		
		if ( $data === false )
		{
			if ( !Framework::getWp() )
			{
				\XF::logError(\XF::phrase('kolakube_wp_posts_wpload_failed', ['error' => Framework::$vars['wp load error']]));
				
				return;
			}
			
			$data = $this->fetchPosts($o);
			
			if ( $data['posts'] && $cache )
				$cache->save($cacheKey, $data, $o['cache_minutes'] * 60);
		}
		
		if ( !$data['posts'] )
			return;
		
		return $this->renderer('widget_kolakube_wp_posts', $data);
	}
	
	protected function getCleanOptions()
	{
		$o = $this->options;
		
		$o['limit'] = (int) $o['limit'];
		
		if ( $o['limit'] < 1 )
			$o['limit'] = self::DEFAULT_LIMIT;
		
		if ( !in_array($o['image_size'], self::IMAGE_SIZES, true) )
			$o['image_size'] = $this->defaultOptions['image_size'];
		
		$o['cache_minutes'] = max(0, (int) $o['cache_minutes']);
		
		foreach ( ['post_ids','cat_ids'] as $var )
			$o[$var] = $o[$var] ? explode(',', $o[$var]) : [];
		
		return $o;
	}
	
	protected function fetchPosts(array $o)
	{
		extract($o);
		
		$posts = [];
		
		# if specific posts are specified, get them first
		if ( $post_ids )
		{
			$posts = get_posts([
				'post_type' => ['post','product','page'],
				'include' => $post_ids,
				'orderby' => 'post__in'
			]);
		}
		
		$posts = array_merge($posts, get_posts(array_merge(
			[
				'post_type' => $cat_ids ? ['post','product','page'] : ['post'],
				'exclude' => $post_ids,
				'numberposts' => $limit
			],
			$cat_ids ? [
				'tax_query' => [
					'relation' => 'OR',
					[
						'taxonomy' => 'category',
						'terms' => $cat_ids
					],
					[
						'taxonomy' => 'product_cat',
						'terms' => $cat_ids
					]
				]
			] : []
		)));
		
		$currencySymbol = function_exists('get_woocommerce_currency_symbol') ? html_entity_decode(get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8') : null;
		
		$items = [];
		
		# keep only what the template uses, cleaned for output
		foreach ( $posts as $post )
		{
			$image = $image_size == 'none' ? false : wp_get_attachment_image_src(get_post_thumbnail_id($post), $image_size);
			
			$items[] = [
				'post_type' => $post->post_type,
				'created_ts' => strtotime($post->post_date),
				'permalink' => esc_url_raw(get_permalink($post)),
				'thumbnail' => $image ? esc_url_raw($image[0]) : '',
				# width and height reserve the image's space before it loads, so lazy loading does not shift the page
				'image_dims' => $image && (int) $image[1] > 0 && (int) $image[2] > 0 ? 'width="'.(int) $image[1].'" height="'.(int) $image[2].'"' : '',
				'image_alt' => $image_size == 'none' ? '' : $this->getImageAlt($post),
				'post_title' => wp_kses(get_the_title($post), self::INLINE_TAGS),
				'post_excerpt' => wp_kses($post->post_excerpt, self::INLINE_TAGS),
				'price' => $post->post_type == 'product' && ( $price = get_post_meta($post->ID, '_price', true) ) ? number_format((float) $price) : null,
				'rating' => $post->post_type == 'product' ? get_post_meta($post->ID, '_wc_average_rating', true) : null,
				'author_url' => esc_url_raw(get_author_posts_url($post->post_author)),
				'author_name' => html_entity_decode(get_the_author_meta('display_name', $post->post_author), ENT_QUOTES, 'UTF-8')
			];
		}
		
		return ['posts' => $items, 'currencySymbol' => $currencySymbol];
	}
	
	# the image's own alt text from the WordPress media library, or the post title when it has none
	protected function getImageAlt($post)
	{
		$alt = trim((string) get_post_meta(get_post_thumbnail_id($post), '_wp_attachment_image_alt', true));
		
		if ( $alt === '' )
			$alt = get_the_title($post);
		
		return html_entity_decode(wp_strip_all_tags($alt), ENT_QUOTES, 'UTF-8');
	}
	
	public function verifyOptions(\XF\Http\Request $request, array &$options, &$error = null)
	{
		$options = $request->filter([
			'limit' => 'uint',
			'post_ids' => 'str',
			'cat_ids' => 'str',
			'title_url' => 'str',
			'image_size' => 'str',
			'cache_minutes' => 'uint'
		]);
		
		if ( !in_array($options['image_size'], self::IMAGE_SIZES, true) )
			$options['image_size'] = $this->defaultOptions['image_size'];
		
		if ( $options['limit'] < 1 )
			$options['limit'] = self::DEFAULT_LIMIT;
		
		foreach ( ['post_ids','cat_ids'] as $field )
		{
			$options[$field] = trim($options[$field]);
			
			$options[$field] = trim($options[$field], ',');
			
			if ( !$options[$field] )
				continue;
			
			$values = explode(',', $options[$field]);
			
			$newValues = [];
			
			foreach ( $values as $value )
			{
				$value = trim($value);
				
				if ( !$value || $value != abs((int) $value) )
				{
					$error = \XF::phrase('kolakube_wp_posts_invalid_post_id', ['post_id' => $value]);
					
					return false;
				}
				
				$newValues[] = $value;
			}
			
			$options[$field] = implode(',', $newValues);
		}
		
		return true;
	}
}