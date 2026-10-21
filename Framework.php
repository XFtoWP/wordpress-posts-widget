<?php

namespace Kolakube\WpPostsWidget;

class Framework
{
	public static $vars = [];
	
	public static function getWp($path = '')
	{
		static $loaded;
		
		if ( $loaded )
			return true;
		
		$fullpath = rtrim($path ?: \XF::options()->KolakubeWpPostsWidget_wpPath, '/').'/wp-load.php';
		
		try
		{
			include $fullpath;
		}
		
		catch ( \Exception $e )
		{
			self::$vars['wp load error'] = $e->getMessage();
			
			return;
		}
		
		if ( !( function_exists('did_action') && did_action('wp_loaded') ) )
		{
			self::$vars['wp load error'] = 'WordPress was not initiated properly';
			
			return;
		}
		
		self::$vars['wp load error'] = null;
		
		return $loaded = true;
	}
}