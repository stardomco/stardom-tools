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
require_once __DIR__ . '/includes/class-stardom-projects.php';
require_once __DIR__ . '/includes/class-stardom-access.php';
require_once __DIR__ . '/includes/class-stardom-dashboard.php';
require_once __DIR__ . '/includes/class-stardom-bom.php';
require_once __DIR__ . '/includes/class-stardom-rfq.php';
require_once __DIR__ . '/includes/class-stardom-supplier-rfq.php';
require_once __DIR__ . '/includes/class-stardom-supplier-approval.php';
add_action('rest_api_init', array('Stardom_Supplier_Approval', 'register_routes'));
require_once __DIR__ . '/includes/class-stardom-quotes.php';
require_once __DIR__ . '/includes/class-stardom-quote-review.php';
require_once __DIR__ . '/includes/class-stardom-po.php';
require_once __DIR__ . '/includes/class-stardom-production.php';
require_once __DIR__ . '/includes/class-stardom-po-approval.php';
require_once __DIR__ . '/includes/class-stardom-qc.php';
add_action('rest_api_init', array('Stardom_QC', 'register_routes'));
add_action('rest_api_init', array('Stardom_PO_Approval', 'register_routes'));
add_action('rest_api_init', array('Stardom_Production', 'register_routes'));
add_action('init', array('Stardom_PO', 'register_type'));
add_action('rest_api_init', array('Stardom_PO', 'register_routes'));
add_action('rest_api_init', array('Stardom_Quote_Review', 'register_routes'));
add_action('init', array('Stardom_Quotes', 'register_type'));
add_action('rest_api_init', array('Stardom_Quotes', 'register_routes'));
add_action('rest_api_init', array('Stardom_Supplier_RFQ', 'register_routes'));
add_action('init', array('Stardom_RFQ', 'register_type'));
add_action('rest_api_init', array('Stardom_RFQ', 'register_routes'));
add_action('init', array('Stardom_BOM', 'register_types'));
add_action('rest_api_init', array('Stardom_BOM', 'register_routes'));
add_action('init', array('Stardom_Dashboard', 'register'));
add_action('init', array('Stardom_Projects', 'register_post_type_only'));
add_action('rest_api_init', array('Stardom_Projects', 'register_routes'));
add_action('plugins_loaded', array('Stardom_Core', 'boot'));
register_activation_hook(__FILE__, array('Stardom_Core', 'activate'));
