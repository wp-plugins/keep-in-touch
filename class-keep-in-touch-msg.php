<?php

defined('ABSPATH') or die ('No direct access to this file.');

include_once('class-keep-in-touch-utils.php');
include_once('class-virtual-page.php');

class Keep_In_Touch_Msg
{
	//static $style_table = 'width: 100%; font-family: \'Arial\'; margin: 25px auto; border-collapse: collapse; border: 1px solid #eee; border-bottom: 2px solid #776B53; ';
	static $style_table = 'width: 100%; font-family: \'Arial\'; margin: 25px auto; border-collapse: collapse; border: 1px solid #eee; border-bottom: 2px solid #000000; ';
	//static $style_table_th_or_table_td = 'background: #F4EDDF; color: #776B53; border: 1px solid #eee; padding: 12px 35px; border-collapse: collapse; text-align: left; ';
	static $style_table_th_or_table_td = 'background: none; color: #000000; border-top: 2px solid #000000; border-bottom: 2px solid #000000; padding: 12px 35px; border-collapse: collapse; text-align: left; ';
	//static $style_table_th = 'background: #776B53; color: #F4EDDF; text-transform: uppercase; font-size: 12px; ';
	static $style_table_th = 'background: none; color: #000000; text-transform: uppercase; font-size: 12px; ';
	static $style_col_1 = 'text-align: left; width: 30%; ';
	static $style_col_2 = 'text-align: left; ';
	//static $style_a = 'color: #776B53; text-decoration: underline; ';
	static $style_a = 'color: #000000; text-decoration: underline; ';
	
	static function initialize_style_options()
	{
		//if (!get_option('keep_in_touch_style_table'))
		//	update_option('keep_in_touch_style_table', self::$style_table);

		//self::$style_table = get_option('keep_in_touch_style_table');
	}
	
	static function emit_subscription_anti_robot($email)
	{
		new Virtual_Page(array(
			'slug' => Keep_In_Touch_Utils::$PAGE_SLUG,
			'title' => __('Keep in Touch', 'keep-in-touch'),
			'content' => 
				__('To complete the subscription request, please type your email address again:', 'keep-in-touch') .
				'<form method="post" action="' . home_url(Keep_In_Touch_Utils::get_page_path_from_slug(Keep_In_Touch_Utils::$PAGE_SLUG)) . '">' .
				'<input type="hidden" name="keep_in_touch_email_reference" value="' . $email . '" />' .
				'<input type="text" placeholder="' . __('Enter email', 'keep-in-touch') . '" name="keep_in_touch_email" value="" />' .
				'<br /><input type="submit" name="keep_in_touch_submit" value="' . __('Sign up', 'keep-in-touch') . '" />' .
				'</form>' .
				''
		));
	}
	
	static function emit_invalid_anti_robot_check()
	{
		new Virtual_Page(array(
			'slug' => Keep_In_Touch_Utils::$PAGE_SLUG,
			'title' => __('Keep in Touch', 'keep-in-touch'),
			'content' => 
				'<p>' . __('The anti-robot check has failed. Your subscription request cannot be considered.', 'keep-in-touch') . '</p>'
		));
	}
	
	static function emit_confirm_subscription($email, $confirmation_code)
	{
		$link = home_url(Keep_In_Touch_Utils::get_page_path_from_slug(Keep_In_Touch_Utils::$PAGE_SLUG)) . '?confirmation_code=' . $confirmation_code;
		$common_content = '<p>' . __('Thank you for subscribing to updates from us.', 'keep-in-touch') . '</p>';
		
		wp_mail(
			$email,
			Keep_In_Touch_Utils::get_blog_marker() . __('Confirm subscription', 'keep-in-touch'), 
			self::get_email_heading() .
				$common_content .
				'<p>' . sprintf(__('To confirm the subscription, use the following link: %s', 'keep-in-touch'), $link) . '</p>',
			'Content-type: text/html'
		);
		
		// emit page or redirect? otherwise we get a 404
		new Virtual_Page(array(
			'slug' => Keep_In_Touch_Utils::$PAGE_SLUG,
			'title' => __('Keep in Touch', 'keep-in-touch'),
			'content' => 
				$common_content . 
				'<p>&nbsp;</p>' .
				'<p>' . __('You will shortly receive an email message to confirm your subscription request.', 'keep-in-touch') . '</p>'.
				'<p>' . self::get_junk_mail_notice_text() . '</p>', 
		));
	}
				
	static function emit_subscription_request_failed($email)
	{
		$admin_message = '<p>' . Keep_In_Touch_Utils::get_blog_marker() . sprintf(__('Subscription request failed for email address %s', 'keep-in-touch'), $email) . '</p>';
		
		wp_mail(
			get_bloginfo('admin_email'),
			$admin_message,
			self::get_email_heading() . $admin_message,
			'Content-type: text/html'
		);
		
		$common_content = __('Your subscription request failed.', 'keep-in-touch') . "\n\n";
			__('An email has been sent to us and we will handle your request shortly.', 'keep-in-touch');
		
		new Virtual_Page(array(
			'slug' => Keep_In_Touch_Utils::$PAGE_SLUG,
			'title' => __('Keep in Touch', 'keep-in-touch'),
			'content' => $common_content, 
		));
	}
	
	static function emit_subscription_confirmation($email, $confirmation_code)
	{
		$common_content = '<p>' . __('Your subscription is now confirmed. You will be receiving updates from us. Welcome and enjoy!', 'keep-in-touch') . '</p>';
		
		wp_mail(
			$email,
			Keep_In_Touch_Utils::get_blog_marker() . __('Subscription confirmed', 'keep-in-touch'), 
			self::get_email_heading() . 
				$common_content .
				'<p>&nbsp;</p>' . 
				self::get_unsubscribe_text_from_email($email),
			'Content-type: text/html'
		);
		
		new Virtual_Page(array(
			'slug' => Keep_In_Touch_Utils::$PAGE_SLUG,
			'title' => __('Keep in Touch', 'keep-in-touch'),
			'content' => $common_content, 
		));
	}
	
	static function emit_confirm_cancellation($email, $confirmation_code)
	{	
		$link = home_url(Keep_In_Touch_Utils::get_page_path_from_slug(Keep_In_Touch_Utils::$PAGE_SLUG) . '?confirmation_code=' . $confirmation_code);
		
		wp_mail(
			$email,
			Keep_In_Touch_Utils::get_blog_marker() . __('Confirm cancellation', 'keep-in-touch'),
			self::get_email_heading() .
				'<p>' . __('We have received a request to cancel your subscription.', 'keep-in-touch') . '</p>' .
				'<p>&nbsp;</p>' .
				'<p>' . sprintf(__('To confirm the request, use the following link: %s', 'keep-in-touch'), $link) . '</p>',
			'Content-type: text/html'
		);
		
		new Virtual_Page(array(
			'slug' => Keep_In_Touch_Utils::$PAGE_SLUG,
			'title' => __('Keep in Touch', 'keep-in-touch'),
			'content' => 
				__('You will shortly receive an email message to confirm the cancellation request.', 'keep-in-touch') .
				'<p>' . self::get_junk_mail_notice_text() . '</p>', 
		));
	}
		
	static function emit_cancellation_request_failed($email)
	{		
		$admin_message = Keep_In_Touch_Utils::get_blog_marker() . 
			sprintf(__('Cancellation request failed for email address %s', 'keep-in-touch'), $email);
		
		wp_mail(
			get_bloginfo('admin_email'),
			$admin_message,
			self::get_email_heading() . $admin_message,
			'Content-type: text/html'
		);
		
		new Virtual_Page(array(
			'slug' => Keep_In_Touch_Utils::$PAGE_SLUG,
			'title' => __('Keep in Touch', 'keep-in-touch'),
			'content' => 
				__('Your subscription cancellation request failed.', 'keep-in-touch') . "\n\n" . 
				__('An email has been sent to us and we will handle your request shortly.', 'keep-in-touch')
		));
	}
	
	static function emit_cancellation_confirmation($email, $confirmation_code)
	{
		$common_content = 
			'<p>' . __('Your subscription cancellation request has been confirmed.', 'keep-in-touch') . '</p>' .
			'<p>' . __('You will no longer be receiving updates from us.', 'keep-in-touch') . '</p>' .
			'<p>&nbsp;</p>' . 
			'<p>' . __('Hope you change your mind and come back soon', 'keep-in-touch') . '</p>';
	
		wp_mail(
			$email,
			Keep_In_Touch_Utils::get_blog_marker() . __('Subscription cancellation confirmed', 'keep-in-touch'), 
			self::get_email_heading() . $common_content,
			'Content-type: text/html'
		);

		new Virtual_Page(array(
			'slug' => Keep_In_Touch_Utils::$PAGE_SLUG,
			'title' => __('Keep in Touch', 'keep-in-touch'),
			'content' => $common_content,
		));
	}
	
	static function emit_invalid_code($email, $confirmation_code)
	{
		new Virtual_Page(array(
			'slug' => Keep_In_Touch_Utils::$PAGE_SLUG,
			'title' => __('Keep in Touch', 'keep-in-touch'),
			'content' => 
				sprintf(__('Code %s is invalid.', 'keep-in-touch'), $confirmation_code) . ' ' .
				__('You may have used an expired confirmation link.', 'keep-in-touch'),
		));
	}

	static function make_digest($query_args)
	{
		$query = new WP_Query($query_args);

		if ($query->found_posts == 0)
		{
			$message = 
				'<p>' . __('As it seems, we haven\'t been very active lately.', 'keep-in-touch') . '</p>' .
				'<p>' . __('There are no new posts.', 'keep-in-touch') . '</p>' .
				'<p>' . __('Maybe you can contribute some yourself ;)', 'keep-in-touch') . '</p>';
		}
		else
		{
			$message = $message .
				'<p>' . __('These are the articles published lately on our site:', 'keep-in-touch') . '</p>' .
				'<table style="' . self::$style_table . '">' .
				'<col style="' . self::$style_col_1 . '"><col style="' . self::$style_col_2 . '">' .
				'<thead><tr>' .
				'<th style="' . self::$style_table_th_or_table_td . self::$style_table_th . '">' . __('Date', 'keep-in-touch') . '</th>' .
				'<th style="' . self::$style_table_th_or_table_td . self::$style_table_th . '">' . __('Title', 'keep-in-touch') . '</th>' .
				'</tr></thead>' .
				'<tbody>';
			while ($query->have_posts())
			{
				$query->next_post();
				$message = $message . '<tr>' .					
					'<td style="' . self::$style_table_th_or_table_td . '">' . get_the_date('', $query->post->ID) . '</td>' .
					'<td style="' . self::$style_table_th_or_table_td . '"><a style="' . self::$style_a . '" href="' . get_permalink($query->post->ID) . '">' . get_the_title($query->post->ID) . '</a></td>' .
					'</tr>';
			}
			$message = $message . '</tbody></table>';
			//$message = $message . '<p>' . 'Articles found: ' . $query->found_posts . '</p>';
		}
		
		return $message;
	}
	
	static function send_mail_to_recipients($recipients, $title, $message)
	{
		foreach ($recipients as $recipient)
			wp_mail(
				$recipient,
				$title, 
				self::get_email_heading() . 
					$message . 
					'<p><small>' . self::get_unsubscribe_text_from_email($recipient) . '</small></p>',
				'Content-type: text/html'
			);
	}
	
	static function get_configured_header_image_url()
	{
		if (get_option('keep_in_touch_header_image_option') == 'get_header_image')
		{
			return get_header_image();
		}
		
		if (get_option('keep_in_touch_header_image_option') == 'custom_path')
		{
			$custom_path = get_option('keep_in_touch_header_image_custom_path');
			if (Keep_In_Touch_Utils::startsWith('/', $custom_path))
				return get_home_url(null, $custom_path);
			else
				return $custom_path;
		}
		
		return "";
	}
	
	static function get_email_heading()
	{
		return '<p><a href="' . get_home_url() . '" alt="' . get_bloginfo('name') . '"><img src="' . self::get_configured_header_image_url() . '"></a></p>';
	}
	
	static function get_junk_mail_notice_text()
	{
		return __('Also check your junk mail folder as the message is sometimes place there.', 'keep-in-touch');
	}
	
	static function get_unsubscribe_text_from_email($email)
	{
		$link = Keep_In_Touch_Utils::get_unsubscribe_link_from_email($email);
		$a = sprintf('<a href="%s" style="%s">%s</a>', $link, self::$style_a, $link);
		return sprintf(__('To cancel your subscription. use the following link: %s', 'keep-in-touch'), $a);
	}
}