# Stardom Unified Platform — Production acceptance criteria

Do not publish or activate the current 0.1.0-dev as a finished system.

## Functional scope
- Single WordPress identity and email verification; customer, supplier, builder, staff and admin role assignment.
- Project intake with ownership and access grants.
- Revision-controlled drawing uploads, BOM snapshots, SKU catalogues and product compliance evidence.
- Supplier registration approval; RFQ, quotation comparison, PO approvals, production, QC and shipping.
- Cost engine integration with budget, committed cost, landed cost and actuals.
- Builder tasks, evidence, inspections, defects and occupancy certificate milestones.
- Notifications, audit logs and project-specific dashboards.

## Non-negotiable security
- Every read/write enforces server-side object-level authorization.
- No cross-customer, cross-supplier or cross-project leakage by ID enumeration.
- Private attachments are not directly publicly accessible.
- CSRF/nonces for browser writes, validated inputs, safe file uploads, audit events.
- Payment and banking changes require separately authorised approval.
- No secrets or customer data committed to GitHub.

## Compatibility
- Audit existing WordPress roles, WooCommerce, Stardom Project Platform, supplier shortcode handlers, Canton Fair Assistant, Import Cost Engine and Delivery Control.
- Use adapters rather than writing to other plugins' private tables without contracts.
- Migration is versioned, idempotent and reversible.

## Release requirements
- Automated PHP tests and WordPress integration tests.
- Permission matrix tested for anonymous/customer/supplier/builder/staff/admin.
- Staging test of end-to-end S60 project workflow.
- Verified backups and rollback drill.
- Explicit production deployment path and post-deploy health checks.

Current status: requirements only; not yet implemented.
