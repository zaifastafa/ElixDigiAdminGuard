<?php declare(strict_types=1);

namespace ElixentDigital\ElixDigiAdminGuard\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class AuditLogCleanupTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'elixdigi_admin_guard.audit_log_cleanup';
    }

    public static function getDefaultInterval(): int
    {
        return self::DAILY;
    }
}
