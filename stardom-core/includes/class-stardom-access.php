<?php
defined('ABSPATH') || exit;

/**
 * Explicit resource access checks. Fail closed until a verified resource
 * ownership/assignment adapter is supplied by the integrating module.
 */
final class Stardom_Access {
    public static function can_access_project(int $user_id, int $project_id, string $action = 'read'): bool {
        if ($user_id <= 0 || $project_id <= 0) {
            return false;
        }
        if (!in_array($action, array('read', 'write', 'approve'), true)) {
            return false;
        }
        // No cross-plugin resource adapter has been audited yet.
        // Deny access rather than trusting a supplied project ID.
        return false;
    }

    public static function can_access_supplier(int $user_id, int $supplier_id, string $action = 'read'): bool {
        if ($user_id <= 0 || $supplier_id <= 0) {
            return false;
        }
        if (!in_array($action, array('read', 'write', 'approve'), true)) {
            return false;
        }
        // Supplier data must never be exposed without verified ownership.
        return false;
    }
}
