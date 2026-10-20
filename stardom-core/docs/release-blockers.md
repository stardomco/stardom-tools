# Stardom Core — remaining work and release blockers

Status: **NOT production-ready**.

## Critical defects to resolve first
1. Existing CI previously checked only two PHP files; expanded syntax coverage is now committed.
2. Private custom post types use generic WordPress post capabilities; define dedicated capabilities and test role isolation.
3. BOM revision numbering relies on a non-atomic object-cache lock; implement transactional or unique database constraints.
4. PO draft creation may race under concurrent requests; enforce uniqueness at storage level.
5. Quote/PO status changes and audit events are not atomic; implement durable state transitions.
6. Supplier role membership is not proof of business approval; implement explicit supplier onboarding and approval.
7. Production release must verify signed drawings, compliance gates, independent PO approval, payment terms, and QC plan.
8. The public GitHub repository must contain no private customer, supplier, financial or credential information.

## Remaining modules
- Supplier onboarding and company verification
- Product catalogue and evidence metadata
- Revision-controlled drawings and BOM
- RFQ dispatch, quote lines, comparison and supplier response
- Approved PO with independent authorisation
- Production milestones and QC corrective-action closure
- Private document uploads with antivirus/content checks and access control
- Container consolidation, packing list, shipment and receiving reconciliation
- Payment schedule, invoice verification and bank-change controls
- Builder assignments, site inspections, defects and occupancy certificate
- Project cost reconciliation and notifications
- Integration adapters for existing Stardom plugins

## Release sequence
1. Stabilize storage and authorization model.
2. Add WordPress integration test environment.
3. Test role isolation and all state transitions.
4. Verify WordPress.com deployment target and rollback.
5. Deploy to staging and complete S60 end-to-end acceptance.
6. Promote a reviewed release to production.

Do not claim completion until all release gates have evidence.
