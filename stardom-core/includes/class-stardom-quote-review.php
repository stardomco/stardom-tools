<?php
defined('ABSPATH') || exit;
/**
 * Explicit administrator quote decision.
 * Approval does not generate a purchase order or release production.
 */
final class Stardom_Quote_Review {
    public static function register_routes(): void {
        register_rest_route('stardom/v1', '/admin/quotes/(?P<quote_id>\\d+)/decision', array(
            'methods' => 'POST',
            'permission_callback' => function () { return current_user_can('manage_options'); },
            'callback' => array(__CLASS__, 'decide'),
            'args' => array(
                'decision' => array('required' => true, 'type' => 'string', 'enum' => array('approved','rejected')),
                'reason' => array('required' => true, 'type' => 'string', 'minLength' => 3, 'maxLength' => 1000),
            ),
        ));
    }
    public static function decide(WP_REST_Request $r) {
        $id = absint($r['quote_id']);
        $quote = get_post($id);
        if (!$quote || $quote->post_type !== 'stardom_quote' || $quote->post_status !== 'private') {
            return new WP_Error('not_found', 'Quote not found.', array('status' => 404));
        }
        $current = get_post_meta($id, '_stardom_quote_status', true);
        if ($current !== 'submitted') {
            return new WP_Error('invalid_state', 'Quote is not awaiting review.', array('status' => 409));
        }
        $decision = sanitize_key($r->get_param('decision'));
        $reason = sanitize_textarea_field($r->get_param('reason'));
        if (!in_array($decision, array('approved','rejected'), true) || mb_strlen($reason) < 3 || mb_strlen($reason) > 1000) {
            return new WP_Error('invalid_decision', 'Valid decision and reason required.', array('status' => 400));
        }
        if (!update_post_meta($id, '_stardom_quote_status', $decision, 'submitted')) {
            return new WP_Error('state_conflict', 'Quote status changed; reload and retry.', array('status' => 409));
        }
        add_post_meta($id, '_stardom_review_event', array(
            'decision' => $decision, 'reason' => $reason,
            'reviewer_user_id' => get_current_user_id(),
            'reviewed_at_gmt' => current_time('mysql', true),
        ));
        return rest_ensure_response(array('quote_id' => $id, 'status' => $decision, 'production_released' => false));
    }
}
