# Stardom Core WordPress integration acceptance tests

## Identity and roles
- Anonymous GET /wp-json/stardom/v1/me returns 401/403.
- Authenticated user sees only their own user ID, display name, and roles.
- Existing administrator roles and credentials are unchanged on activation.
- Customer, supplier, and builder roles have read only until explicit capabilities are granted.
- Deactivation leaves existing users intact.

## Supplier/project authorization (not implemented yet)
- Supplier A cannot read Supplier B's RFQ, quote, PO or files, including via ID enumeration.
- Customer A cannot read Customer B's project, BOM, quotation, or delivery records.
- Builder only accesses explicitly assigned projects.
- Admin access is audited; sensitive changes require role and object checks.

## Operational release gates
- Back up and test restore on staging.
- Verify exact deployment directory and whether GitHub artifact deploys a plugin or entire site.
- Never deploy to production before integration tests and security review.
- Verify idempotent migrations, audit logs, and rollback.
- No live financial or supplier data in automated tests.

## Known limitation
Current CI only validates PHP syntax. WordPress integration tests require a provisioned test site.
