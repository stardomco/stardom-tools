<?php
defined('ABSPATH') || exit;
/**
 * Administrator-only supplier approval. Does not modify WordPress user roles.
 */
final class Stardom_Supplier_Approval {
    public static function register_routes(): void {
        register_rest_route('stardom/v1', '/admin/suppliers/(?P<user_id>\\d+)/approval', array(
            'methods' => 'POST',
            'permission_callback' => function () { return current_user_can('manage_options'); },
            'callback' => array(__CLASS__, 'decide'),
        ));
    }
    public static function decide(WP_REST_Request $r) {
        $uid = absint($r['user_id']);
        $user = get_user_by('id', $uid);
        if (!$user || !in_array('stardom_supplier', (array) $user->roles, true)) {
            return new WP_Error('invalid_supplier', 'Supplier account not found.', array('status' => 404));
        }
        $decision = sanitize_key((string) $r->get_param('decision'));
        $reason = sanitize_textarea_field((string) $r->get_param('reason'));
        if (!in_array($decision, array('approved', 'rejected'), true) || mb_strlen($reason) < 3 || mb_strlen($reason) > 1000) {
            return new WP_Error('invalid_decision', 'Valid decision and reason required.', array('status' => 400));
        }
        $new_status = $decision === 'approved' ? 'yes' : 'no';
        update_user_meta($uid, '_stardom_supplier_approved', $new_status);
        add_user_meta($uid, '_stardom_supplier_approval_event', array(
            'decision' => $decision,
            'reason' => $reason,
            'reviewed_by' => get_current_user_id(),
            'reviewed_at_gmt' => current_time('mysql', true),
        ), false);
        return rest_ensure_response(array('supplier_user_id' => $uid, 'approval' => $decision));
    }
}
