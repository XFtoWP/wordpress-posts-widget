<?php

namespace Kolakube\WpPostsWidget\Option;

use Kolakube\WpPostsWidget\Framework;

use XF\Option\AbstractOption;
use XF\Entity\Option;

class WpPath extends AbstractOption
{
	public static function verifyOption(&$value, Option $option)
	{
		$value = '/'.trim($value, '/');
		
		if ( Framework::getWp($value) )
			return true;
		
		$option->error(\XF::phrase('kolakube_wp_posts_wpload_failed', ['error' => Framework::$vars['wp load error']]));
		
		return false;
	}
}