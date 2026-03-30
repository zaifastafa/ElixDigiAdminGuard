# ElixDigiAdminGuard

**Shopware 6 Admin User Audit & Inactivity Management Plugin**

Shopware 6 has no built-in mechanism to flag or disable stale admin accounts. Shop owners and agencies routinely forget to remove access for former employees, freelancers, or agency staff. A compromised or misused dormant admin account can result in data breaches, fraudulent orders, unauthorized configuration changes, or GDPR violations.

ElixDigiAdminGuard addresses this gap with zero friction for shop owners.

## Features

### Admin User Dashboard
- Dedicated admin page listing all admin users with their inactivity status
- Columns: Name, Email, Roles, Created At, Last Login, Status, Days Inactive
- Visual status indicators: Active (green), Warning (yellow), Danger (red), Disabled (grey), Never Logged In (blue)
- One-click disable/enable actions via context menu
- Super admin accounts are protected from disabling

### Login Tracking
- Automatically tracks admin login timestamps via OAuth token interception
- No modifications to Shopware core tables -- uses a separate tracking table
- Works transparently without any configuration

### Inactivity Flagging
- Configurable warning threshold (default: 90 days)
- Configurable danger threshold (default: 180 days)
- Separate handling for accounts that have never logged in
- Daily automated checks via Shopware's scheduled task system

### Auto-Disable (Optional)
- Automatically disable accounts that exceed the inactivity threshold
- Super admin accounts are always excluded (safety guardrail)
- Last active admin protection -- prevents accidental lockout
- All auto-disable actions are logged in the audit trail

### Email Notifications
- Scheduled inactivity reports sent to a configurable recipient
- Supports daily or weekly frequency
- Summarizes warning, danger, never-logged-in, and disabled accounts
- Uses the system's configured mail transport -- no external dependencies
- Falls back to all super admin emails if no recipient is configured

### Audit Log
- Every plugin action is recorded: logins, status changes, disables, enables, reminders
- Viewable directly in the admin panel
- Exportable as CSV for compliance documentation (GDPR, ISO)
- Configurable retention period (default: 365 days)
- Automatic cleanup of old entries via scheduled task

## Requirements

- Shopware 6.7+
- PHP 8.2+

## Installation

### Via Composer (Recommended)

```bash
composer require elixent-digital/admin-guard
bin/console plugin:install --activate ElixDigiAdminGuard
bin/console cache:clear
```

### Manual Installation

1. Download or clone this repository into `custom/plugins/ElixDigiAdminGuard/`
2. Run:

```bash
bin/console plugin:refresh
bin/console plugin:install --activate ElixDigiAdminGuard
bin/console cache:clear
bin/build-administration.sh
```

## Configuration

After installation, navigate to **Settings > Extensions > ElixDigiAdminGuard** in the Shopware admin to configure the plugin.

All settings are accessible via the standard plugin configuration UI:

| Setting | Default | Description |
|---------|---------|-------------|
| Warning threshold | 90 days | Days since last login to trigger warning status |
| Danger threshold | 180 days | Days since last login to trigger danger status |
| Auto-disable enabled | Off | Whether to automatically disable inactive accounts |
| Auto-disable days | 180 days | Days of inactivity before auto-disable kicks in |
| Email notifications | Off | Send scheduled inactivity reports |
| Recipient email | (super admins) | Override the notification recipient |
| Email frequency | Weekly | Daily or Weekly report schedule |
| Audit log retention | 365 days | How long to keep audit log entries |

## Usage

### Viewing Admin User Status

Navigate to **Settings > Plugins > Admin Guard** in the admin panel. The **Users** tab displays all admin users with their current inactivity status.

### Audit Log

Switch to the **Audit Log** tab to view all plugin actions. Use the **Export CSV** button to download the log for compliance documentation.

### Scheduled Tasks

The plugin registers three scheduled tasks that run automatically:

| Task | Frequency | Purpose |
|------|-----------|---------|
| `elixdigi_admin_guard.inactivity_check` | Daily | Scan users, update flags, auto-disable if enabled |
| `elixdigi_admin_guard.notification` | Daily* | Send summary email (* respects frequency config) |
| `elixdigi_admin_guard.audit_log_cleanup` | Daily | Remove old audit log entries |

You can manually trigger a task via CLI:

```bash
bin/console scheduled-task:run-single elixdigi_admin_guard.inactivity_check
```

## Technical Details

### Database Tables

The plugin creates two tables (no modifications to core tables):

- `elixdigi_admin_guard_user_tracking` -- Stores last login timestamps and computed status per user
- `elixdigi_admin_guard_audit_log` -- Immutable audit trail of all plugin actions

### Login Detection

Since Shopware 6 does not provide a dedicated admin login event, the plugin uses a `KernelEvents::RESPONSE` subscriber to intercept successful OAuth token responses on `/api/oauth/token`. This is the only reliable hook point for tracking admin logins without modifying core code.

### Super Admin Detection

Users with `admin = true` in the user table are treated as super admins. These accounts are protected from auto-disable and manual disable via the plugin UI.

### GDPR Compliance

- The audit log stores user IDs as foreign keys with `ON DELETE SET NULL`
- User names and emails are denormalized at write time so audit entries survive user deletion
- When a user is deleted, their audit entries show the original name/email but the FK becomes NULL
- Configurable retention period allows compliance with data minimization requirements

## Plugin Structure

```
ElixDigiAdminGuard/
  composer.json
  src/
    ElixDigiAdminGuard.php              # Plugin bootstrap
    Controller/
      AdminGuardController.php          # API endpoints
    Core/Content/
      AuditLog/                         # Audit log entity (Definition, Entity, Collection)
      UserTracking/                     # User tracking entity (Definition, Entity, Collection)
    Migration/
      Migration1711800000CreateAdminGuardTables.php
    ScheduledTask/
      InactivityCheckTask.php           # Daily inactivity scan
      InactivityCheckTaskHandler.php
      NotificationTask.php              # Email report sender
      NotificationTaskHandler.php
      AuditLogCleanupTask.php           # Old entry cleanup
      AuditLogCleanupTaskHandler.php
    Service/
      AuditLogService.php               # Audit log CRUD + CSV export
      InactivityService.php             # User status computation
      NotificationService.php           # Email report builder
      UserDisableService.php            # Safe user enable/disable
    Subscriber/
      AdminLoginTrackingSubscriber.php  # OAuth login interceptor
    Resources/
      config/
        config.xml                      # Plugin settings schema
        routes.xml                      # Route registration
        services.xml                    # DI container config
      app/administration/src/           # Vue.js admin module
        main.js
        api/admin-guard.api.js
        module/admin-guard/
          index.js                      # Module registration
          acl/index.js                  # ACL privileges
          snippet/en-GB.json
          snippet/de-DE.json
          page/index/                   # Main page with tabs
          component/
            admin-guard-user-list/      # User list with data grid
            admin-guard-audit-log/      # Audit log with pagination
```

## Known Limitations

- **SSO/Integration logins are not tracked.** Only password-based admin logins via the standard OAuth flow are detected. If your shop uses external authentication, those logins will not appear in the tracking data.
- **Shopware Cloud is not supported.** This plugin requires access to the plugin system and scheduled tasks, which are not available on managed Shopware Cloud hosting.
- **No Shopware 6.4/6.5 support.** The plugin targets Shopware 6.7+ and uses features (Vite admin build, `#[AsMessageHandler]` attribute) not available in older versions.

## License

MIT License. See [LICENSE](LICENSE) for details.

## Author

**Huzaifa Mustafa**
[huzaifamustafa.com](https://huzaifamustafa.com)

Developed and maintained by **[Elixent Digital](https://elixentdigital.com)**
