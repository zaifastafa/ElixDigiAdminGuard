<?php declare(strict_types=1);

namespace ElixentDigital\ElixDigiAdminGuard\ScheduledTask;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: AuditLogCleanupTask::class)]
class AuditLogCleanupTaskHandler extends ScheduledTaskHandler
{
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        LoggerInterface $logger,
        private readonly Connection $connection,
        private readonly SystemConfigService $systemConfigService,
    ) {
        parent::__construct($scheduledTaskRepository, $logger);
    }

    public function run(): void
    {
        $retentionDays = $this->systemConfigService->getInt('ElixDigiAdminGuard.config.auditLogRetentionDays') ?: 365;

        $this->connection->executeStatement(
            'DELETE FROM `elixdigi_admin_guard_audit_log` WHERE `created_at` < DATE_SUB(NOW(), INTERVAL :days DAY)',
            ['days' => $retentionDays]
        );
    }
}
