<?php
/**
 * Plugin Name: What We're Building
 * Description: Import a GitHub Pages newsletter into WordPress as an exact standalone landing page — no theme header, footer, or navigation.
 * Version: 1.0.0
 * Author: Rick Johnston
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * Text Domain: what-were-building
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'QNL_VERSION', '1.0.0' );
define( 'QNL_FILE', __FILE__ );
define( 'QNL_DIR', plugin_dir_path( __FILE__ ) );
define( 'QNL_URL', plugin_dir_url( __FILE__ ) );

require_once QNL_DIR . 'includes/class-source.php';
require_once QNL_DIR . 'includes/class-importer.php';
require_once QNL_DIR . 'includes/class-landing.php';
require_once QNL_DIR . 'includes/class-admin.php';

add_action(
	'plugins_loaded',
	static function () {
		QNL_Landing::init();
		if ( is_admin() ) {
			QNL_Admin::init();
		}
	}
);

register_activation_hook(
	__FILE__,
	static function () {
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die(
				esc_html__( 'What We\'re Building requires PHP 7.4 or newer.', 'what-were-building' ),
				esc_html__( 'Plugin activation failed', 'what-were-building' ),
				array( 'back_link' => true )
			);
		}
	}
);
