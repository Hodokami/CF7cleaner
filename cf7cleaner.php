<?php
/*
	Plugin Name: CF7 JS&CSS Cleaner
	Description: Clean JS&CSS of "Contact Form 7" on Pages other than Form page. THIS PLUGIN CANNOT ACTIVATE WHEN "Contact Form 7" IS DISABLED!
	Version: 0.1
*/
if ( ! defined( 'ABSPATH' ) ) exit; // This php needs load wp-config.php

add_filter('pre_update_option_active_plugins', 'cleaner_load_before_cf7', 10, 2); // plugin enabled/disabled hook for 'cleaner_load_before_cf7'
function cleaner_load_before_cf7( $active_plugins, $old_value )
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