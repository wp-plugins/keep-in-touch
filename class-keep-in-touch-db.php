<?php

defined('ABSPATH') or die ('No direct access to this file.');

class Keep_In_Touch_Db
{
	private static $REQUIRED_DB_VERSION = '3';
	private static $TABLE_NAME = 'keep_in_touch';
	
	static function create_table()
	{
		global $wpdb;
		
		if (get_option('keep_in_touch_db_version') != self::$REQUIRED_DB_VERSION)
		{
			$charset_collate = $wpdb->get_charset_collate();
			
			$sql = 'CREATE TABLE ' . $wpdb->prefix . self::$TABLE_NAME . ' (
				email tinytext NOT NULL,
				status set(\'pending_activation\', \'active\', \'pending_removal\') NOT NULL,
				code tinytext NOT NULL DEFAULT \'\',
				categories tinytext NOT NULL DEFAULT \'\',
				daily bool NOT NULL DEFAULT 0,
				weekly bool NOT NULL DEFAULT 0,
				newsletter bool NOT NULL DEFAULT 0,
				UNIQUE email ( email ( 255 ) )
			) $charset_collate;';
			
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
			
			update_option('keep_in_touch_db_version', self::$REQUIRED_DB_VERSION);
		}
	}
	
	static function find_row_by_email($email)
	{
		global $wpdb;
		
		return $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM " . $wpdb->prefix . self::$TABLE_NAME . " WHERE email = %s",
			$email
		));
	}
	
	static function find_row_by_code($code)
	{
		global $wpdb;
		
		return $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM " . $wpdb->prefix . self::$TABLE_NAME . " WHERE code = %s",
			$code
		));
	}
	
	static function register_subscription_request($email, $code)
	{
		global $wpdb;
		
		// race condition?
		
		if (self::find_row_by_email($email))
		{
			return $wpdb->update(
				$wpdb->prefix . self::$TABLE_NAME,
				array(
					'code' => $code,
					'status' => 'pending_activation',
				),
				array(
					'email' => $email,
				)
			);
		}
		
		return $wpdb->insert(
			$wpdb->prefix . self::$TABLE_NAME,
			array(
				'email' => $email,
				'status' => 'pending_activation',
				'code' => $code,
				'categories' => '',
				'daily' => false,
				'weekly' => true,
				'newsletter' => true,
			)
		);
	}
	
	static function activate_subscription_by_code($code)
	{
		global $wpdb;
		
		return $wpdb->update(
			$wpdb->prefix . self::$TABLE_NAME,
			array(
				'code' => '',
				'status' => 'active',
			),
			array(
				'code' => $code,
				'status' => 'pending_activation',
			)
		);
	}
	
	static function register_cancellation_request($email, $code)
	{
		global $wpdb;
		
		// race condition?
		
		// this is silly, but we do it for symmetry
		if (!self::find_row_by_email($email))
		{
			return $wpdb->insert(
				$wpdb->prefix . self::$TABLE_NAME,
				array(
					'email' => $email,
					'status' => 'pending_removal',
					'code' => $code,
					'categories' => '',
					'daily' => false,
					'weekly' => true,
					'newsletter' => false,
				)
			);
		}
		
		return $wpdb->update(
			$wpdb->prefix . self::$TABLE_NAME,
			array(
				'code' => $code,
				'status' => 'pending_removal',
			),
			array(
				'email' => $email,
			)
		);
	}
	
	static function remove_subscription_by_code($code)
	{
		global $wpdb;
		
		return $wpdb->delete(
			$wpdb->prefix . self::$TABLE_NAME,
			array(
				'code' => $code,
				'status' => 'pending_removal',
			)
		);
	}
	
	static function get_all_confirmed_subscribers()
	{
		global $wpdb;
		
		return $wpdb->get_results(
			"SELECT * FROM " . $wpdb->prefix . self::$TABLE_NAME . " WHERE status != 'pending_activation'"
		);
	}
	
	static function get_all_confirmed_daily_digest_subscribers()
	{
		global $wpdb;
		
		return $wpdb->get_results(
			"SELECT * FROM " . $wpdb->prefix . self::$TABLE_NAME . " WHERE status != 'pending_activation' AND daily = 1"
		);
	}
	
	static function get_emails_of_all_confirmed_daily_digest_subscribers()
	{
		return Keep_In_Touch_Utils::object_list_column(self::get_all_confirmed_daily_digest_subscribers(), 'email');
	}
	
	static function get_all_confirmed_weekly_digest_subscribers()
	{
		global $wpdb;
		
		return $wpdb->get_results(
			"SELECT * FROM " . $wpdb->prefix . self::$TABLE_NAME . " WHERE status != 'pending_activation' AND weekly = 1"
		);
	}
	
	static function get_emails_of_all_confirmed_weekly_digest_subscribers()
	{
		return Keep_In_Touch_Utils::object_list_column(self::get_all_confirmed_weekly_digest_subscribers(), 'email');
	}
	
	static function get_all_confirmed_newsletter_subscribers()
	{
		global $wpdb;
		
		return $wpdb->get_results(
			"SELECT * FROM " . $wpdb->prefix . self::$TABLE_NAME . " WHERE status != 'pending_activation' AND newsletter = 1"
		);
	}
	
	static function get_emails_of_all_confirmed_newsletter_subscribers()
	{
		return Keep_In_Touch_Utils::object_list_column(self::get_all_confirmed_newsletter_subscribers(), 'email');
	}
}
