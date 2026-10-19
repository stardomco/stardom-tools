# Stardom Core staging deployment runbook

Status: development only; production release prohibited until acceptance gates pass.

## Prerequisites
1. Confirm staging URL, access rights and backup/restore status.
2. Verify WordPress GitHub deployment target mapping; the current wpcom workflow publishes a repository artifact, not an explicitly scoped plugin installation.
3. Snapshot the existing plugins, user roles, pages and site settings.
4. Ensure no production secrets or real customer data are copied into test fixtures.

## Staging installation
1. Build a ZIP with stardom-core/ as the archive root.
2. Install through WordPress staging Plugins > Add New > Upload Plugin.
3. Activate only on staging.
4. Check /wp-json/stardom/v1/me as anonymous and authenticated test users.
5. Test shortcode [stardom_account_status] on a private staging page.
6. Verify role creation is read-only and does not modify existing administrators.

## Release gates
- PHP CI green
- Integration and authorization tests green
- Supplier/project object-level permission checks implemented
- Rollback rehearsed
- Admin approves production promotion

## Rollback
Deactivate Stardom Core on staging and restore the snapshot if necessary.
Never delete user records or existing supplier/project data as part of rollback.
