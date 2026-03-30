<?php declare(strict_types=1);

namespace ElixentDigital\ElixDigiAdminGuard\ScheduledTask;

use Doctrine\DBAL\Connection;
use ElixentDigital\ElixDigiAdminGuard\Service\AuditLogService;
use ElixentDigital\ElixDigiAdminGuard\Service\InactivityService;
use ElixentDigital\ElixDigiAdminGuard\Service\UserDisableService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: InactivityCheckTask::class)]
class InactivityCheckTaskHandler extends ScheduledTaskHandler
{
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        LoggerInterface $logger,
        private readonly InactivityService $inactivityService,
        private readonly UserDisableService $userDisableService,
        private readonly AuditLogService $auditLogService,
        private readonly SystemConfigService $systemConfigService,
        private readonly Connection $connection,
    ) {
        parent::__construct($scheduledTaskRepository, $logger);
    }

    public function run(): void
    {
        $users = $this->inactivityService->getAdminUsersWithStatus();
        $autoDisableEnabled = $this->systemConfigService->getBool('ElixDigiAdminGuard.config.autoDisableEnabled');

        foreach ($users as $user) {
            if ($user['status'] === 'disabled') {
                continue;
            }

            $this->updateTrackingStatus($user);

            if ($autoDisableEnabled && $user['active'] && !$user['admin']) {
                $autoDisableDays = $this->systemConfigService->getInt('ElixDigiAdminGuard.config.autoDisableDays') ?: 180;
                if ($user['daysInactive'] >= $autoDisableDays) {
                    $this->userDisableService->disableUser($user['id'], $user['firstName'] . ' ' . $user['lastName'], $user['email']);
                }
            }
        }
    }

    private function updateTrackingStatus(array $user): void
    {
        $userId = Uuid::fromHexToBytes($user['id']);
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s.v');
        $newStatus = $user['status'];

        $existing = $this->connection->fetchAssociative(
            'SELECT id, status FROM `elixdigi_admin_guard_user_tracking` WHERE `user_id` = :userId',
            ['userId' => $userId]
        );

        if ($existing) {
            $oldStatus = $existing['status'];
            if ($oldStatus !== $newStatus) {
                $this->connection->executeStatement(
                    'UPDATE `elixdigi_admin_guard_user_tracking` SET `status` = :status, `updated_at` = :now WHERE `user_id` = :userId',
                    ['status' => $newStatus, 'now' => $now, 'userId' => $userId]
                );

                if (in_array($newStatus, ['warning', 'danger'], true)) {
                    $this->auditLogService->log(
                        'flagged_' . $newStatus,
                        $user['id'],
                        $user['firstName'] . ' ' . $user['lastName'],
                        $user['email'],
                        ['previous_status' => $oldStatus, 'days_inactive' => $user['daysInactive']]
                    );
                }
            }
        } else {
            $this->connection->executeStatement(
                'INSERT INTO `elixdigi_admin_guard_user_tracking` (`id`, `user_id`, `status`, `created_at`) VALUES (:id, :userId, :status, :now)',
                [
                    'id' => Uuid::randomBytes(),
                    'userId' => $userId,
                    'status' => $newStatus,
                    'now' => $now,
                ]
            );

            if (in_array($newStatus, ['warning', 'danger'], true)) {
                $this->auditLogService->log(
                    'flagged_' . $newStatus,
                    $user['id'],
                    $user['firstName'] . ' ' . $user['lastName'],
                    $user['email'],
                    ['days_inactive' => $user['daysInactive']]
                );
            }
        }
    }
}
