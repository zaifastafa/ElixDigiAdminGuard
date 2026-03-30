<?php declare(strict_types=1);

namespace ElixentDigital\ElixDigiAdminGuard\ScheduledTask;

use ElixentDigital\ElixDigiAdminGuard\Service\AuditLogService;
use ElixentDigital\ElixDigiAdminGuard\Service\NotificationService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: NotificationTask::class)]
class NotificationTaskHandler extends ScheduledTaskHandler
{
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        LoggerInterface $logger,
        private readonly NotificationService $notificationService,
        private readonly SystemConfigService $systemConfigService,
        private readonly AuditLogService $auditLogService,
    ) {
        parent::__construct($scheduledTaskRepository, $logger);
    }

    public function run(): void
    {
        if (!$this->systemConfigService->getBool('ElixDigiAdminGuard.config.emailNotificationsEnabled')) {
            return;
        }

        $frequency = $this->systemConfigService->getString('ElixDigiAdminGuard.config.emailFrequency') ?: 'weekly';

        if ($frequency === 'weekly' && (int) date('N') !== 1) {
            // Weekly mode: only send on Mondays
            return;
        }

        $this->notificationService->sendInactivityReport();
        $this->auditLogService->log('reminder_sent', null, 'System', 'system', ['type' => 'inactivity_report', 'frequency' => $frequency]);
    }
}
