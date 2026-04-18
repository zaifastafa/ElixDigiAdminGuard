# ElixDigiAdminGuard

Admin user audit and inactivity management for Shopware 6.

Shopware has no built-in mechanism to flag or disable stale admin accounts. Dormant accounts from former employees, freelancers, or agency staff are a common attack vector for data breaches, unauthorized changes, and GDPR violations. ElixDigiAdminGuard closes this gap.

## Features

- **Dashboard** -- Overview of all admin users with inactivity status, days inactive, roles, and one-click disable/enable actions
- **Login tracking** -- Automatic, zero-config tracking via OAuth token interception (no core table modifications)
- **Inactivity flagging** -- Configurable warning and danger thresholds with daily automated checks
- **Auto-disable** -- Optionally disable accounts exceeding the inactivity threshold, with last-active-super-admin protection to prevent lockout
- **Email notifications** -- Scheduled inactivity reports (daily or weekly) to a configurable recipient
- **Audit log** -- Full trail of logins, status changes, and disable/enable actions with CSV export and configurable retention
- **Bootstrap seeding** -- On install, seeds tracking data from existing Shopware login evidence so pre-existing stale accounts are detected immediately

## Requirements

- Shopware 6.7+
- PHP 8.2+

## Installation

```bash
composer require elixent-digital/admin-guard
bin/console plugin:install --activate ElixDigiAdminGuard
bin/console cache:clear
```

For manual installation, place the plugin in `custom/plugins/ElixDigiAdminGuard/`, then run `plugin:refresh` before `plugin:install`.

## Configuration

Navigate to **Settings > Extensions > ElixDigiAdminGuard** in the admin panel.

| Setting | Default | Description |
|---|---|---|
| Warning threshold | 90 days | Days of inactivity to trigger warning |
| Danger threshold | 180 days | Days of inactivity to trigger danger |
| Auto-disable | Off | Automatically disable inactive accounts |
| Auto-disable days | 180 days | Inactivity threshold for auto-disable |
| Email notifications | Off | Send scheduled inactivity reports |
| Recipient email | Super admins | Override the notification recipient |
| Email frequency | Weekly | Daily or weekly reports |
| Audit log retention | 365 days | How long to keep audit entries |

## Known Limitations

- Only password-based admin logins via the standard OAuth flow are tracked. SSO and integration logins are not detected.
- Requires the plugin system and scheduled tasks (not available on Shopware Cloud).
- Targets Shopware 6.7+ only.

## Changelog

### 1.2.0

- Register reverse OneToMany associations on `UserDefinition` via entity extension, resolving `dal:validate` errors and enabling `user.adminGuardTracking` / `user.adminGuardAuditLogs` association paths.
- Verified compatibility with Shopware 6.7.9.0.

### 1.1.0

- Allow disabling stale super admins, protecting only the last active one.
- Seed tracking table from existing login evidence on plugin install.

### 1.0.0

- Initial release.

## License

MIT -- see [LICENSE](LICENSE).

## Author

**Huzaifa Mustafa** -- [huzaifamustafa.com](https://huzaifamustafa.com)

Developed by **[Elixent Digital](https://elixentdigital.com)**
