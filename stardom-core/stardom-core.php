<?php
/**
 * Plugin Name: Stardom Core
 * Description: Unified identity and project authorization foundation for Stardom.
 * Version: 0.1.0-dev
 * Requires PHP: 8.1
 * Text Domain: stardom-core
 */
defined('ABSPATH') || exit;
define('STARDOM_CORE_VERSION', '0.1.0-dev');
require_once __DIR__ . '/includes/class-stardom-core.php';
add_action('plugins_loaded', array('Stardom_Core', 'boot'));
register_activation_hook(__FILE__, array('Stardom_Core', 'activate'));
