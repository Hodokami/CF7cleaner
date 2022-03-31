<?php
/*
	Plugin Name:       CF7 JS&CSS Cleaner
	Plugin URI:        https://github.com/Hodokami/CF7cleaner
	Description:       Clean JS&CSS of "Contact Form 7" on Pages other than Form page. THIS PLUGIN CANNOT ACTIVATE WHEN "Contact Form 7" IS DISABLED!
	Version:           0.4
	Requires at least: 5.9
	Requirres PHP:     8.0
	Author:            Hodokami
	Author URI:        https://tellaresdo.com/
	Licence:           GPL v3 or later
	Licence URI:       https://www.gnu.org/licenses/gpl-3.0.html
	Update URI:        https://github.com/Hodokami/
*/
if (!defined('ABSPATH')) exit; // This php needs load wp-config.php

add_action('wp_enqueue_scripts', 'enable_cf7_jscss', 21);
function enable_cf7_jscss()
{
	add_filter( 'wpcf7_load_js', '__return_false' ); // JS dont load
	add_filter( 'wpcf7_load_css', '__return_false' ); // CSS dont load
	$nowID = get_the_ID();
	$cf7cID = cf7c_get();
	$is_cf7page = in_array($nowID, $cf7cID);
	if (true === $is_cf7page)
	{
		if (function_exists('wpcf7_enqueue_scripts')) wpcf7_enqueue_scripts();
		if (function_exists('wpcf7_enqueue_styles')) wpcf7_enqueue_styles();
		wp_enqueue_style('reCAPTCHA_masker', plugins_url('reCAPTCHA_masker.css', __FILE__)); //load masker
	}
	else wp_deregister_script('google-recaptcha'); // unload reCAPTCHA v3 from page (needs priority > 20, dont move on default priority)
}

function cf7c_get() // Option get
{
	return get_option('tellaresdo_cf7c_ID');
}
function cf7c_set($cf7cID) // Option set
{
	update_option ('tellaresdo_cf7c_ID', $cf7cID, 'no');
}

add_filter('pre_update_option_active_plugins', 'cleaner_load_before_cf7', 10, 2); // plugin enabled/disabled hook for 'cleaner_load_before_cf7'
function cleaner_load_before_cf7($active_plugins, $old_value)
/*
	Set cf7cleaner load before CF7.
	<!> WHEN CF7 IS DISABLED, 'cleaner_load_before_cf7' REMOVE "cf7cleaner" FROM "active_plugins"!
 */
{
	// set plugins path
	$cf7cleaner = str_replace(wp_normalize_path(WP_PLUGIN_DIR).'/', '',wp_normalize_path(__FILE__) );
	$contactform7 = wp_normalize_path('contact-form-7/wp-contact-form-7.php');
	// set flag
	$cf7cremoved = 0;
	foreach ($active_plugins as $no=>$path)
	{
		if ($path == $cf7cleaner) // REMOVER
			{
				unset($active_plugins[$no]);
				$active_plugins = array_values($active_plugins);
				$cf7cremoved = 1;
				/*
					"$cf7cremoved == 1" expresses that cf7cleaner is enabled.
					"$cf7cremoved == 0" expresses that cf7cleaner is disabled. 'cleaner_load_before_cf7' will do nothing.
				 */
			}
	
	}
	if ($cf7cremoved == 1) // cf7cleaner is enabled, but removed by REMOVER. ($cf7cremoved == 1) Sets cf7cleaner before CF7 AGAIN.
	{
		foreach ($active_plugins as $no=>$path) // SET
		{
			if ($path == $contactform7)
			{
				array_splice($active_plugins, $no, 0, $cf7cleaner);
				break;
			}
		}
	}
	$active_plugins = array_values($active_plugins); // reset array key
	return $active_plugins;
}
add_action('save_post', 'cf7cleaner_checker', 10, 1); //action works when update posts
function cf7cleaner_checker($post_ID) // set Option value
{
	$cf7csetting_ID = wp_is_post_revision($post_ID); // revision check
	if(false === $cf7csetting_ID) $cf7csetting_ID = $post_ID; // not revision

	$cf7cID = cf7c_get(); //get now option array
	$init_cf7c = array(); // option init value(empty array)
	if(false === $cf7cID) cf7c_set($init_cf7c); //check option existence
	$cf7c_optscan = in_array($cf7csetting_ID, $cf7cID); // search option array

	// $cf7csetting_post = get_post($cf7csetting_ID);
	// $cf7cmatching_content = apply_filters('the_content', $cf7csetting_post->post_content);
	$cf7cmatching_content = get_post($cf7csetting_ID)->post_content;

	$cf7_match = preg_match('/\[contact-form-7 id=(.+?) title=(.+?)\]/', $cf7cmatching_content); // match CF7 from post
	if (1 === $cf7_match && false === $cf7c_optscan) $cf7cID[] = $cf7csetting_ID; // add option value
	if (0 === $cf7_match && false !== $cf7c_optscan) foreach ($cf7cID as $no=>$ID) if ($ID == $cf7csetting_ID) unset($cf7cID[$no]); //remove option
	$cf7cID = array_values($cf7cID);
	cf7c_set($cf7cID);
}