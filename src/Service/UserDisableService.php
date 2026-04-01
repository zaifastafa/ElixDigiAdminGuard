<?php declare(strict_types=1);

namespace ElixentDigital\ElixDigiAdminGuard\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;

class UserDisableService
{
    public function __construct(
        private readonly EntityRepository $userRepository,
        private readonly AuditLogService $auditLogService,
        private readonly Connection $connection,
    ) {
    }

    public function disableUser(string $userId, string $userName, string $userEmail, string $triggeredBy = 'system', bool $isSuperAdmin = false): bool
    {
        if ($isSuperAdmin && $this->isLastActiveSuperAdmin($userId)) {
            return false;
        }

        $context = Context::createCLIContext();
        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($userId): void {
            $this->userRepository->update([
                ['id' => $userId, 'active' => false],
            ], $context);
        });

        $action = $triggeredBy === 'system' ? 'auto_disabled' : 'manually_disabled';
        $this->auditLogService->log($action, $userId, $userName, $userEmail, ['triggered_by' => $triggeredBy]);

        return true;
    }

    private function isLastActiveSuperAdmin(string $userId): bool
    {
        // FOR UPDATE prevents concurrent disable requests from both passing the check
        // before either disable is committed (TOCTOU race condition).
        $activeSuperAdminCount = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM `user` WHERE `admin` = 1 AND `active` = 1 AND `id` != :userId FOR UPDATE',
            ['userId' => Uuid::fromHexToBytes($userId)]
        );

        return $activeSuperAdminCount === 0;
    }

    public function enableUser(string $userId, string $userName, string $userEmail, string $triggeredBy = 'admin'): void
    {
        $context = Context::createCLIContext();
        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($userId): void {
            $this->userRepository->update([
                ['id' => $userId, 'active' => true],
            ], $context);
        });

        $this->auditLogService->log('manually_enabled', $userId, $userName, $userEmail, ['triggered_by' => $triggeredBy]);
    }
}
