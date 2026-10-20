<?php
defined('ABSPATH') || exit;
/** Production may start only after explicit administrative PO release. */
final class Stardom_Production {
    public static function register_routes(): void {
        register_rest_route('stardom/v1', '/admin/purchase-orders/(?P<po_id>\\d+)/release', array(
            'methods' => 'POST',
            'permission_callback' => function () { return current_user_can('manage_options'); },
            'callback' => array(__CLASS__, 'release'),
            'args' => array('reference' => array('required' => true, 'type' => 'string', 'minLength' => 3, 'maxLength' => 120)),
        ));
    }
    public static function release(WP_REST_Request $r) {
        // Production release remains disabled until engineering and compliance gates are verified.
        return new WP_Error('release_disabled', 'Production release is unavailable pending engineering and compliance approval integration.', array('status' => 503));
        $id = absint($r['po_id']);
        $po = get_post($id);
        if (!$po || $po->post_type !== 'stardom_po' || $po->post_status !== 'private') {
            return new WP_Error('invalid_po', 'Purchase order not found.', array('status' => 404));
        }
        if (get_post_meta($id, '_stardom_po_status', true) !== 'approved') {
            return new WP_Error('invalid_state', 'PO requires independent approval before release.', array('status' => 409));
        }
        $ref = sanitize_text_field($r->get_param('reference'));
        if (mb_strlen($ref) < 3 || mb_strlen($ref) > 120) {
            return new WP_Error('invalid_reference', 'Release reference required.', array('status' => 400));
        }
        if (!update_post_meta($id, '_stardom_po_status', 'released', 'approved')) {
            return new WP_Error('conflict', 'PO state changed.', array('status' => 409));
        }
        add_post_meta($id, '_stardom_po_release_event', array(
            'reference' => $ref, 'released_by' => get_current_user_id(),
            'released_at_gmt' => current_time('mysql', true),
        ));
        return rest_ensure_response(array('po_id' => $id, 'status' => 'released'));
    }
}
