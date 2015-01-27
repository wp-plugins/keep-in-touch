<?php

defined('ABSPATH') or die ('No direct access to this file.');

class Keep_In_Touch_Utils
{
	static $PAGE_SLUG = 'keep-in-touch';

	static function get_blog_marker()
	{
		//return '[' . get_bloginfo('name') . '] ';
		return '';
	}
	
	static function generate_unique_id($maxLength = null)
	{
		$entropy = '';

		// try ssl first
		if (function_exists('openssl_random_pseudo_bytes'))
		{
			$entropy = openssl_random_pseudo_bytes(64, $strong);
			// skip ssl since it wasn't using the strong algo
			if($strong !== true)
				$entropy = '';
		}

		// add some basic mt_rand/uniqid combo
		$entropy .= uniqid(mt_rand(), true);

		// try to read from the windows RNG
		//if (class_exists('COM'))
		//{
		//	try
		//	{
		//		$com = new COM('CAPICOM.Utilities.1');
		//		$entropy .= base64_decode($com->GetRandom(64, 0));
		//	}
		//	catch (Exception $ex)
		//	{
		//	}
		//}

		// try to read from the unix RNG
		if (is_readable('/dev/urandom'))
		{
			$h = fopen('/dev/urandom', 'rb');
			$entropy .= fread($h, 64);
			fclose($h);
		}

		$hash = hash('whirlpool', $entropy);
		if ($maxLength)
			return substr($hash, 0, $maxLength);
		return $hash;
	}

	static function get_page_path_from_slug($slug)
	{
		global $wp_rewrite;

		$link = $wp_rewrite->get_page_permastruct();

		if (!empty($link))
		{
			$link = str_replace('%pagename%', $slug, $link);
		}

		//$link = home_url($link);
		$link = user_trailingslashit($link, 'page');

		return $link;
	}
	
	static function get_subscribe_link_from_email($email)
	{
		return home_url(Keep_In_Touch_Utils::get_page_path_from_slug(Keep_In_Touch_Utils::$PAGE_SLUG) . '?subscribe=' . $email);
	}

	static function get_unsubscribe_link_from_email($email)
	{
		return home_url(Keep_In_Touch_Utils::get_page_path_from_slug(Keep_In_Touch_Utils::$PAGE_SLUG) . '?unsubscribe=' . $email);
	}

	static function array_column($array, $column)
	{
		$column_array = array();

		foreach ($array as $item)
	    	$column_array[] = $item[$column];
	    
	    return $column_array;
	}

	static function object_list_column($array, $column)
	{
		$column_array = array();

		foreach ($array as $item)
	    	$column_array[] = $item->$column;
	    
	    return $column_array;
	}
	
	static function explode($delimiter, $string)
	{
		if (empty($string))
			return array();
		return explode($delimiter, $string);
	}

	static function format_time($seconds)
	{
		return date('H:i', abs($seconds));
	}

	static function format_time_offset($seconds)
	{
		return (($seconds < 0)?'-':'+') . self::format_time($seconds);
	}
}
