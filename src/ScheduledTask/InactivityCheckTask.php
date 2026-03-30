<?php declare(strict_types=1);

namespace ElixentDigital\ElixDigiAdminGuard\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class InactivityCheckTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'elixdigi_admin_guard.inactivity_check';
    }

    public static function getDefaultInterval(): int
    {
        return self::DAILY;
    }
}
