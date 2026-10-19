# Stardom Core (development only)

Initial WordPress plugin foundation for unified accounts and project authorization.

## Current scope
- Reuses WordPress user identities and login.
- On activation creates three low-privilege roles: customer, supplier and builder.
- Provides authenticated GET /wp-json/stardom/v1/me.
- Provides [stardom_account_status] shortcode.
- Does not expose projects, supplier records, purchase orders, or customer files.
- Does not migrate data, enable public registration, or alter existing administrator roles.

## Release gates
1. Inspect existing supplier, project and delivery plugin code and storage.
2. Verify WordPress staging and deployment target mapping.
3. Implement server-side project/supplier resource authorization with tests.
4. Test login, CSRF, access isolation, and rollback on staging.
5. Review before merge to main.

Never commit secrets or customer records.
