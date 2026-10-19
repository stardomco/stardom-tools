<?php
defined('ABSPATH') || exit;
final class Stardom_Core {
    public static function boot(): void {
        add_shortcode('stardom_account_status', array(__CLASS__, 'account_status'));
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));
    }
    public static function activate(): void {
        foreach (array('stardom_customer' => 'Stardom Customer', 'stardom_supplier' => 'Stardom Supplier', 'stardom_builder' => 'Stardom Builder') as $slug => $label) {
            if (!get_role($slug)) {
                add_role($slug, $label, array('read' => true));
            }
        }
    }
    public static function account_status(): string {
        if (!is_user_logged_in()) {
            return '<p>Please sign in to access your Stardom workspace.</p>' . wp_login_form(array('echo' => false, 'redirect' => home_url('/my-stardom/')));
        }
        return '<p>Signed in to My Stardom.</p>';
    }
    public static function register_routes(): void {
        register_rest_route('stardom/v1', '/me', array(
            'methods' => 'GET',
            'permission_callback' => function () { return is_user_logged_in(); },
            'callback' => function () {
                $user = wp_get_current_user();
                return rest_ensure_response(array(
                    'id' => (int) $user->ID,
                    'display_name' => $user->display_name,
                    'roles' => array_values($user->roles),
                    'core_version' => STARDOM_CORE_VERSION
                ));
            }
        ));
    }
}
