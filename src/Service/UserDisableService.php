<?php declare(strict_types=1);

namespace ElixentDigital\ElixDigiAdminGuard\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

class UserDisableService
{
    public function __construct(
        private readonly EntityRepository $userRepository,
        private readonly AuditLogService $auditLogService,
        private readonly InactivityService $inactivityService,
    ) {
    }

    public function disableUser(string $userId, string $userName, string $userEmail, string $triggeredBy = 'system', bool $isSuperAdmin = false): bool
    {
        if ($isSuperAdmin) {
            return false;
        }

        if ($this->inactivityService->getActiveAdminCount() <= 1) {
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
