<?php declare(strict_types=1);

namespace ElixentDigital\ElixDigiAdminGuard\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class NotificationTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'elixdigi_admin_guard.notification';
    }

    public static function getDefaultInterval(): int
    {
        return self::DAILY;
    }
}
