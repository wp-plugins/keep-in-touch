<?php

defined('ABSPATH') or die ('No direct access to this file.');

include_once('class-keep-in-touch-utils.php');

class Keep_In_Touch_Widget extends WP_Widget
{
	public function Keep_In_Touch_Widget()
	{
		$this->WP_Widget(
			'wp_keepintouch',
			__('Keep in Touch', 'keep-in-touch'), 
			array('description' => __('Displays a form that allows visitors to subscribe for updates.', 'keep-in-touch'),)
		);
	}
	
	public function widget($args, $instance)
	{
		extract( $args );
		
		echo $before_widget;
		echo $before_title . __('Keep in Touch', 'keep-in-touch') . $after_title;
		echo '<p>' . __('Receive a weekly digest of the posts.', 'keep-in-touch') . '</p>';
		echo '<form method="post" action="' . home_url(Keep_In_Touch_Utils::get_page_path_from_slug(Keep_In_Touch_Utils::$PAGE_SLUG)) . '">';
		echo '<input placeholder="' . __('Enter email', 'keep-in-touch') . '" name="keep_in_touch_email" ';
		if (is_user_logged_in())
		{
			global $current_user;
			get_currentuserinfo();
			echo 'value="' . $current_user->user_email . '" ';
		}
		echo '/>';
		echo '<input type="submit" name="keep_in_touch_submit" value="' . __('Sign up', 'keep-in-touch') . '" />';
		echo sprintf('<a href="%s"><img src="%s" style="max-width:20px;max-height:20px;vertical-align:middle;float:right;"/></a>', home_url(Keep_In_Touch_Utils::get_page_path_from_slug('feed')), plugins_url( 'rss-logo.png', __FILE__ ));
		echo '</form>';
		//echo '<p>' . sprintf(__('Or subscribe to our %s', 'keep-in-touch'), sprintf('<a href="%s">%s</a>', home_url(Keep_In_Touch_Utils::get_page_path_from_slug('feed')), __('RSS feed', 'keep-in-touch'))) . '</p>';
		echo $after_widget;
	}
	
	public function form($instance)
	{
	}
	
	public function update($new_instance, $old_instance)
	{
	}
	
	public static function register()
	{
		register_widget('Keep_In_Touch_Widget');
	}
}

