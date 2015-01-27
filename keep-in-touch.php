<?php

/*
Plugin Name: Keep in Touch
Plugin URI: https://wordpress.org/plugins/keep-in-touch/
Description: Maintains a list of subscribers to updates and newsletter.
Version: 1.0.2
Author: Racanu
Author URI: https://profiles.wordpress.org/racanu/
Text Domain: keep-in-touch
#Domain Path: Optional. Plugin's relative directory path to .mo files. Example: /locale/
#Network: Optional. Whether the plugin can only be activated network wide. Example: true
License: GPL2
*/

defined('ABSPATH') or die ('No direct access to this file.');

//? >
//	<script type="text/javascript" >
//	function keep_in_touch_send_daily_digest_now()
//	{
//		//alert('keep_in_touch_send_daily_digest_now called');
//		
//		var data =
//		{
//			'action': 'keep_in_touch_action',
//			'whatever': 1234
//		};
//
//		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
//		$.post(ajaxurl, data, function(response)
//		{
//			alert('Got this from the server: ' + response);
//		});
//	}
//	</script>
//< ?php

//define('WP_DEBUG', true);
//define('WP_DEBUG_LOG', true);

include_once(ABSPATH . 'wp-includes/locale.php');
include_once('class-keep-in-touch-utils.php');
include_once('class-keep-in-touch-db.php');
include_once('class-keep-in-touch-msg.php');
include_once('class-keep-in-touch-widget.php');


class Keep_In_Touch
{
	public function __construct()
	{
		add_action('init', 'Keep_In_Touch::init_translation');
		add_action('init', 'Keep_In_Touch::init_virtual_page');
		add_action('widgets_init', 'Keep_In_Touch_Widget::register');
		//add_action('template_redirect', array($this, 'process_form'));
		//add_filter('query_vars', array($this, 'query_vars'));
		//add_action('parse_request', array($this, 'parse_request'));
		add_action('keep_in_touch_daily_event_hook', 'Keep_In_Touch::handle_daily_event');
		add_action('keep_in_touch_weekly_event_hook', 'Keep_In_Touch::handle_weekly_event');
		add_action('admin_menu', 'Keep_In_Touch::plugin_menu');
		//add_action('wp_ajax_keep_in_touch_action', 'Keep_In_Touch::ajax_action_callback');
	}

	static function init_translation()
	{
 		load_plugin_textdomain('keep-in-touch', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
	}
	
	static function init_virtual_page()
	{
		$request_path = user_trailingslashit(trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'), 'page');
		$virtual_path = Keep_In_Touch_Utils::get_page_path_from_slug(Keep_In_Touch_Utils::$PAGE_SLUG);

		if ($request_path == $virtual_path)
		{
			self::handle_virtual_path();
		}
	}
	
	static function handle_virtual_path()
	{
		if (isset($_POST['keep_in_touch_submit']) and isset($_POST['keep_in_touch_email']))
			self::handle_subscribe($_POST['keep_in_touch_email'], $_POST['keep_in_touch_email_reference']);
		//else if (isset($_GET['subscribe']))
		//	self::handle_subscribe_first_step($_GET['subscribe']);
		else if (isset($_GET['unsubscribe']))
			self::handle_unsubscribe(trim($_GET['unsubscribe']));
		else if (isset($_GET['confirmation_code']))
			self::handle_confirmation_code(trim($_GET['confirmation_code']));
	}
	
	static function handle_subscribe($email, $email_reference)
	{
		if (!isset($email_reference))
		{
			Keep_In_Touch_Msg::emit_subscription_anti_robot($email);
		}
		else
		{
			$confirmation_code = Keep_In_Touch_Utils::generate_unique_id(20);

			if (trim($email) != trim($email_reference))
				Keep_In_Touch_Msg::emit_invalid_anti_robot_check();
			else if (Keep_In_Touch_Db::register_subscription_request($email, $confirmation_code))
				Keep_In_Touch_Msg::emit_confirm_subscription($email, $confirmation_code);
			else
				Keep_In_Touch_Msg::emit_subscription_request_failed($email);
		}
	}

	static function handle_unsubscribe($email)
	{
		$confirmation_code = Keep_In_Touch_Utils::generate_unique_id(20);

		if (Keep_In_Touch_Db::register_cancellation_request($email, $confirmation_code))
			Keep_In_Touch_Msg::emit_confirm_cancellation($email, $confirmation_code);
		else
			Keep_In_Touch_Msg::emit_cancellation_request_failed($email);
	}

	static function handle_confirmation_code($confirmation_code)
	{
		$db_row = Keep_In_Touch_Db::find_row_by_code($confirmation_code);
	
		if (Keep_In_Touch_Db::activate_subscription_by_code($confirmation_code))
			Keep_In_Touch_Msg::emit_subscription_confirmation($db_row->email, $confirmation_code);
		
		else if (Keep_In_Touch_Db::remove_subscription_by_code($confirmation_code))
			Keep_In_Touch_Msg::emit_cancellation_confirmation($db_row->email, $confirmation_code);
		
		else
			Keep_In_Touch_Msg::emit_invalid_code($db_row->email, $confirmation_code);			
	}


	static function schedule_events()
	{
		self::reschedule_daily_event();
		self::reschedule_weekly_event();
	}
	
	static function reschedule_over_one_minute($hook)
	{
		wp_schedule_single_event(
			strtotime('today ' . date('H:i', time() + get_option('gmt_offset') * 3600 + 1 * 60) . ' ' . Keep_In_Touch_Utils::format_time_offset(get_option('gmt_offset') * 3600)), 
			$hook
		);
	}

	static function reschedule_daily_event()
	{
		wp_clear_scheduled_hook('keep_in_touch_daily_event_hook');
		
		$t0 = get_option('keep_in_touch_delivery_time') . ' ' . Keep_In_Touch_Utils::format_time_offset(get_option('gmt_offset') * 3600);
		$t1 = strtotime('today ' . $t0);
		$t2 = strtotime('tomorrow ' . $t0);
		$t = ($t1 > floor(time() / 60) * 60) ? $t1 : $t2;
		
		wp_schedule_single_event($t, 'keep_in_touch_daily_event_hook');
		//self::reschedule_over_one_minute('keep_in_touch_daily_event_hook');
	}
	
	static function reschedule_weekly_event()
	{
		wp_clear_scheduled_hook('keep_in_touch_weekly_event_hook');
		
		$t0 = get_option('keep_in_touch_delivery_time') . ' ' . Keep_In_Touch_Utils::format_time_offset(get_option('gmt_offset') * 3600);
		$t1 = strtotime('sunday ' . $t0);
		$t2 = strtotime('next sunday ' . $t0);
		$t = ($t1 > floor(time() / 60) * 60) ? $t1 : $t2;
		
		wp_schedule_single_event($t, 'keep_in_touch_weekly_event_hook');
		//self::reschedule_over_one_minute('keep_in_touch_weekly_event_hook');
	}

	static function unschedule_events()
	{
		wp_clear_scheduled_hook('keep_in_touch_daily_event_hook');
		wp_clear_scheduled_hook('keep_in_touch_weekly_event_hook');
	}

	static function send_daily_digest($recipients)
	{
		$digest = Keep_In_Touch_Msg::make_digest(array(
			'date_query' => array(
				array(
					'after'     => date('Y-m-d', strtotime('yesterday')),
					'before'    => date('Y-m-d', strtotime('today')),
					'inclusive' => true,
				),
			),
			'posts_per_page' => -1,
		));

		Keep_In_Touch_Msg::send_mail_to_recipients(
			empty($recipients) ? Keep_In_Touch_Db::get_emails_of_all_confirmed_daily_digest_subscribers() : $recipients,
			Keep_In_Touch_Utils::get_blog_marker() . __('Daily digest', 'keep-in-touch'),
			$digest
		);
	}

	static function handle_daily_event()
	{		
		self::send_daily_digest();
		self::reschedule_daily_event();
	}

	static function send_weekly_digest($recipients)
	{
		$digest = Keep_In_Touch_Msg::make_digest(array(
			'date_query' => array(
				array(
					'after'     => date('Y-m-d', strtotime('sunday previous week')),
					'before'    => date('Y-m-d', strtotime('sunday this week')),
					'inclusive' => true,
				),
			),
			'posts_per_page' => -1,
		));
		
		Keep_In_Touch_Msg::send_mail_to_recipients(
			empty($recipients) ? Keep_In_Touch_Db::get_emails_of_all_confirmed_weekly_digest_subscribers() : $recipients,
			Keep_In_Touch_Utils::get_blog_marker() . __('Weekly digest', 'keep-in-touch'),
			$digest
		);
	}
	
	static function handle_weekly_event()
	{
		self::send_weekly_digest();
		self::reschedule_weekly_event();
	}

	static function send_newsletter($title, $text, $recipients)
	{
		Keep_In_Touch_Msg::send_mail_to_recipients(
			empty($recipients) ? Keep_In_Touch_Db::get_emails_of_all_newsletter_subscribers() : $recipients,
			Keep_In_Touch_Utils::get_blog_marker() . sprintf(__('Newsletter: %s', 'keep-in-touch'), $title),
			$text
		);
	}
	
	static function plugin_menu()
	{
		add_options_page('Keep in Touch', 'Keep in Touch', 'manage_options', 'keep-in-touch', 'Keep_In_Touch::plugin_options');
	}

	static function explode_recipients($input)
	{
		return array_map('trim', Keep_In_Touch_Utils::explode(',', trim($input)));
	}
	
	static function get_hour($time, $default)
	{
		if (empty($time))
			return $default;
		return intval(date('H', strtotime($time)));
	}

	static function get_minute($time, $default)
	{
		if (empty($time))
			return $default;
		return intval(date('i', strtotime($time)));
	}
	
	static function plugin_options()
	{
		if (!current_user_can('manage_options'))
			wp_die(__('You do not have sufficient permissions to access this page.'));

		if ($_POST['form'] == 'keep_in_touch_digest_delivery_settings')
		{
			update_option('keep_in_touch_delivery_weekday', $_POST['delivery_weekday']);
			update_option('keep_in_touch_delivery_time', Keep_In_Touch_Utils::format_time($_POST['delivery_hour'] * 3600 + $_POST['delivery_minute'] * 60));
			self::reschedule_daily_event();
			self::reschedule_weekly_event();
		}
		else if ($_POST['form'] == 'keep_in_touch_send_digest_now' and isset($_POST['send_daily_digest']))
			self::send_daily_digest(self::explode_recipients($_POST['digest_recipients']));
		else if ($_POST['form'] == 'keep_in_touch_send_digest_now' and isset($_POST['send_weekly_digest']))
			self::send_weekly_digest(self::explode_recipients($_POST['digest_recipients']));
		else if ($_POST['form'] == 'keep_in_touch_send_newsletter' and isset($_POST['send_newsletter']))
			self::send_newsletter(
				$_POST['newsletter_message_title'],
				$_POST['newsletter_message_text'],
				self::explode_recipients($_POST['newsletter_recipients'])
			);
		
		echo
			'<div class="wrap">' .
			
			'<h2>' . __('Digest delivery settings', 'keep-in-touch') . '</h2>' .
			'<form method="POST" action="/wordpress/wp-admin/options-general.php?page=keep-in-touch">' .
			'<input type="hidden" name="form" value="keep_in_touch_digest_delivery_settings" />' .
			'<table class="form-table"><tr>' .
			'<th scope="row">' . __('Delivery weekday', 'keep-in-touch') . '</th>';
		$wp_locale = new WP_Locale();
		echo '<td><select name="delivery_weekday">';
		for ($wdi = 0; $wdi < 7; $wdi++)
		{
			$selected = (($wdi==get_option('keep_in_touch_delivery_weekday'))?'selected="selected" ':'');
			echo '<option ' . $selected . 'value="' . $wdi . '">' . $wp_locale->get_weekday($wdi) . '</option>';
		}
		echo 
			'</select><p class="description">' . __('The day of the week in which the weekly digest will be delivered', 'keep-in-touch') . '</p></td>' .
			'</tr><tr>' .
			'<th scope="row">' . __('Delivery time', 'keep-in-touch') . '</th>' .
			'<td><select name="delivery_hour">';
		for ($h = 0; $h < 24; $h++)
			echo '<option ' . (($h==self::get_hour(get_option('keep_in_touch_delivery_time')))?'selected="selected" ':'') . 'value="' . sprintf('%02d', $h) . '">' . sprintf('%02d', $h) . '</option>';
		echo '</select>&nbsp;:&nbsp;<select name="delivery_minute">';
		foreach (array(0, 15, 30, 45) as $m)
		{
			$selected = (($m==self::get_minute(get_option('keep_in_touch_delivery_time')))?'selected="selected" ':'');
			$value = sprintf('%02d', $m);
			echo '<option ' . $selected . 'value="' . $value . '">' . $value . '</option>';
		}
		echo 
			'</select>' .
			'<p class="description">' . __('The time of the day in which the daily and weekly digests will be delivered', 'keep-in-touch') . '</p></td>' .
			'</tr><tr>' .
			'<th scope="row"></th>' .
			'<td><input type="submit" name="save_settings" class="button-primary" value="' . __('Save settings', 'keep-in-touch') . '" /></td>' .
			'</tr></table class="form-table">' .
			'</form>' .

			'<h2>' . __('Send digest now', 'keep-in-touch') . '</h2>' .
			'<form method="POST" action="/wordpress/wp-admin/options-general.php?page=keep-in-touch">' .
			'<input type="hidden" name="form" value="keep_in_touch_send_digest_now" />' .
			'<table class="form-table"><tr>' .
			'<th scope="row">'. __('Email addresses to send to', 'keep-in-touch') . '</th>' .
			'<td><input type="text" name="digest_recipients" placeholder="' . __('Enter comma-separated list of email addresses', 'keep-in-touch') . '" size="80" />' .
			'<p class="description">' . sprintf(__('or leave empty to send to %d daily digest or %d weekly digest confirmed subscribers', 'keep-in-touch'), count(Keep_In_Touch_Db::get_all_confirmed_daily_digest_subscribers()), count(Keep_In_Touch_Db::get_all_confirmed_weekly_digest_subscribers())) . '</p></td>' .
			'</tr><tr>' .
			'<th scope="row"></th>' .
			'<td><input type="submit" name="send_daily_digest" class="button-primary" value="' . __('Send daily digest now', 'keep-in-touch') . '" />&nbsp;' .
			'<input type="submit" name="send_weekly_digest" class="button-primary" value="' . __('Send weekly digest now', 'keep-in-touch') . '" /></td>' .
			'</tr></table>' .
			'</form>' .
		
			'<h2>' . __('Newsletter', 'keep-in-touch') . '</h2>' .
			'<form method="POST" action="/wordpress/wp-admin/options-general.php?page=keep-in-touch">' .
			'<input type="hidden" name="form" value="keep_in_touch_send_newsletter" />' .
			'<table class="form-table"><tr>' .
			'<th scope="row">'. __('Email addresses to send to', 'keep-in-touch') . '</th>' .
			'<td><input type="text" name="newsletter_recipients" placeholder="' . __('Enter comma-separated list of email addresses', 'keep-in-touch') . '" size="80" />' .
			'<p class="description">' . sprintf(__('or leave empty to send to %d confirmed newsletter subscribers', 'keep-in-touch'), count(Keep_In_Touch_Db::get_all_confirmed_newsletter_subscribers())) . '</p></td>' .
			'</tr><tr>' .
			'<th scope="row">'. __('Subject', 'keep-in-touch') . '</th>' .
			'<td><input type="text" name="newsletter_message_title" placeholder="' . __('Enter the subject of the newsletter message', 'keep-in-touch') . '" size="80" />' .
			'</tr><tr>' .
			'<th scope="row">' . __('Message', 'keep-in-touch') . '</th>' .
			'<td>';
		wp_editor('', 'newsletter_message_text', array('textarea_name' => 'newsletter_message_text', 'drag_drop_upload' => 'true', 'editor_css' => '<style>textarea {height: 15em; width: 50em;}</style>', ));
		echo
			'</td>' .
			'</tr><tr>' .
			'<th scope="row"></th>' .
			'<td><input type="submit" name="send_newsletter" class="button-primary" value="' . __('Send newsletter message', 'keep-in-touch') . '" /></td>' .
			'</tr></table>' .
			'</form>' .
				
			'</div>';
	}

	//static function ajax_action_callback()
	//{
	//	global $wpdb; // this is how you get access to the database
	//
	//	$whatever = intval( $_POST['whatever'] );
	//	$whatever += 10;
	//	echo $whatever;
	//	Keep_In_Touch::send_daily_digest();
	//	wp_die(); // this is required to terminate immediately and return a proper response
	//}
}

new Keep_In_Touch();

register_activation_hook(__FILE__, array('Keep_In_Touch_Db', 'create_table'));
register_activation_hook(__FILE__, array('Keep_In_Touch', 'schedule_events'));
register_deactivation_hook(__FILE__, array('Keep_In_Touch', 'unschedule_events'));

//How-to:
//http://wordpress.stackexchange.com/questions/139071/plugin-generated-virtual-pages


