<?php
/*
Plugin Name: Memberful WP
Plugin URI: http://github.com/jestro/memberful-wp
Description: Allows Memberful users to login to WordPress using the Memberful oAuth 2.0 endpoint.
Version: 0.1
Author: Memberful
Author URI: http://memberful.com
License: GPL
*/

if( ! defined('MEMBERFUL_DIR'))
	define('MEMBERFUL_DIR', dirname(__FILE__));

define('MEMBERFUL_HTML', NULL);
define('MEMBERFUL_JSON', 'json');

require_once MEMBERFUL_DIR.'/lib/memberful-wp/urls.php';
require_once MEMBERFUL_DIR.'/lib/memberful-wp/user/map.php';
require_once MEMBERFUL_DIR.'/lib/memberful-wp/authenticator.php';
require_once MEMBERFUL_DIR.'/lib/memberful-wp/options.php';
require_once MEMBERFUL_DIR.'/lib/memberful-wp/metabox.php';
require_once MEMBERFUL_DIR.'/lib/memberful-wp/acl.php';

add_action('admin_menu', 'memberful_wp_register_options_panel');
add_action('admin_init', 'memberful_wp_register_options');

add_action('init', 'memberful_init');
register_activation_hook(__FILE__, 'memberful_activate');

function memberful_init() {}

function memberful_activate()
{
	global $wpdb;

	$columns = $wpdb->get_results('SHOW COLUMNS FROM `'.$wpdb->users.'` WHERE `Field` LIKE "memberful_%"');

	if(empty($columns))
	{
		$result = $wpdb->query('ALTER TABLE `'.$wpdb->users.'`
			ADD COLUMN `memberful_member_id` INT UNSIGNED NULL DEFAULT NULL,
			ADD COLUMN `memberful_refresh_token` VARCHAR(45) NULL DEFAULT NULL,
			ADD UNIQUE INDEX `memberful_member_id_UNIQUE` (`memberful_member_id` ASC),
			ADD UNIQUE INDEX `memberful_refresh_token_UNIQUE` (`memberful_refresh_token` ASC)');

		// If for some reason the plugin could not be activated
		if($result === FALSE)
		{
			echo 'Could not create the necessary modifications to the users table\n';
			$wpdb->print_error();
			exit();
		}
	}

	// When index.php is not the endpoint the rule goes in htaccess
	// This may cause problems if .htaccess is not writable
	//
	// Facepress gets around this by rewriting to index.php then hooking into
	// the template redirect hook to call the oauth callback
	add_rewrite_rule('oauth', 'wp-login.php?memberful_auth=1', 'top');
	flush_rewrite_rules(true);
}

function memberful_wp_render($template, array $vars = array())
{
	extract($vars);

	include MEMBERFUL_DIR.'/views/'.$template.'.php';
}
