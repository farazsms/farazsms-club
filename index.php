<?php
/*
Plugin Name: farazsms-club
Plugin URI: https://farazsms.com
Description: Add user meta phone number to farazsms-club phonebook
Version: 1.0
Author: Seyyed Mahmood Ghaffari and <a href="https://twitter.com/sae13">Saeb Molaee</a>
Author URI: https://farazsms.com/api
License: GPL3
Domain Path: /languages
*/


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'FARAZSMS_CLUB_VERSION' ) ) {
	define( 'FARAZSMS_CLUB_VERSION', '1.0.0' );
}


if ( ! defined( 'FARAZSMS_CLUB_DIR' ) ) {
	define( 'FARAZSMS_CLUB_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'FARAZSMS_CLUB_INDEX_FILE' ) ) {
	define( 'FARAZSMS_CLUB_INDEX_FILE', __FILE__ );
}

if ( ! defined( 'FARAZSMS_CLUB_URL' ) ) {
	define( 'FARAZSMS_CLUB_URL', plugins_url( '', __FILE__ ) . '/' );
}

if(!class_exists('FARAZSMS_CLUB_BASE'))
	require_once 'includes/class-base.php';
if(!class_exists('FARAZSMS_CLUB'))
	require_once 'includes/class-core.php';

/**
 **/
