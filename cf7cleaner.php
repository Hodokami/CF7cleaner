<?php
/*
	Plugin Name: CF7 JS&CSS Cleaner
	Description: Clean JS&CSS of "Contact Form 7" on Pages other than Form page. THIS PLUGIN CANNOT ACTIVATE WHEN "Contact Form 7" IS DISABLED!
	Version: 0.2
*/
if ( ! defined( 'ABSPATH' ) ) exit; // This php needs load wp-config.php

add_filter( 'wpcf7_load_js', '__return_false' ); // JS dont load
add_filter( 'wpcf7_load_css', '__return_false' ); // CSS dont load

$cf7cslag = cf7c_get();

add_action( 'wp_enqueue_scripts', 'enable_cf7_jscss' );
function enable_cf7_jscss()
{
	global $post;
	$nowslug = $post->post_name;
	$cf7cslag = cf7c_get();
	$is_cf7page = in_array($nowslug, $cf7cslag);
	if (false === $is_cf7page)
	{
		if (function_exists('wpcf7_enqueue_scripts'))
		{
			wpcf7_enqueue_scripts();
		}
		if (function_exists('wpcf7_enqueue_styles'))
		{
			wpcf7_enqueue_styles();
		}
	}
}

if(false === $cf7cslag)//check option existence
{
	$init_cf7c = array();
	cf7c_set($init_cf7c);
}
function cf7c_get()
{
	return get_option('tellaresdo_cf7c_slag');
}
function cf7c_set($cf7cslag)
{
	update_option ('tellaresdo_cf7c_slag', $cf7cslag, 'no');
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

add_filter('wp_insert_post_data', 'cf7cleaner_checker', 10, 1);
function cf7cleaner_checker($data) // set Option value
{
	$cf7cslag = cf7c_get();
	$post_slag = $data['post_name'];
	$cf7c_optscan = in_array($post_slag, $cf7cslag);
	$cf7_match = preg_match('/\[contact-form-7 id=(.+?) title=(.+?)\]/', $data['post_content']);
	if (1 === $cf7_match && false === $cf7c_optscan)
	{
		$cf7cslag[] = $post_slag;
		cf7c_set($cf7cslag);
	}
	if (0 === $cf7_match && false !== $cf7c_optscan)
	{
		foreach ($cf7cslag as $no=>$slag)
		{
			if ($slag == $post_slag) // REMOVER
				{
					unset($cf7cslag[$no]);
					$cf7cslag = array_values($cf7cslag);
					cf7c_set($cf7cslag);
				}
		
		}
	}
	return $data;
}