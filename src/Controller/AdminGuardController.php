<?php declare(strict_types=1);

namespace ElixentDigital\ElixDigiAdminGuard\Controller;

use Doctrine\DBAL\Connection;
use ElixentDigital\ElixDigiAdminGuard\Service\AuditLogService;
use ElixentDigital\ElixDigiAdminGuard\Service\InactivityService;
use ElixentDigital\ElixDigiAdminGuard\Service\UserDisableService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/_action/admin-guard', defaults: ['_routeScope' => ['api'], '_acl' => ['elixdigi_admin_guard:read']])]
class AdminGuardController extends AbstractController
{
    public function __construct(
        private readonly InactivityService $inactivityService,
        private readonly UserDisableService $userDisableService,
        private readonly AuditLogService $auditLogService,
        private readonly Connection $connection,
    ) {
    }

    #[Route(path: '/users', name: 'api.action.admin_guard.users', methods: ['GET'])]
    public function listUsers(): JsonResponse
    {
        return new JsonResponse([
            'data' => $this->inactivityService->getAdminUsersWithStatus(),
        ]);
    }

    #[Route(path: '/users/{userId}/disable', name: 'api.action.admin_guard.user.disable', methods: ['POST'], defaults: ['_acl' => ['elixdigi_admin_guard:manage']])]
    public function disableUser(string $userId): JsonResponse
    {
        if (!Uuid::isValid($userId)) {
            return new JsonResponse(['error' => 'Invalid user ID'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUserInfo($userId);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $success = $this->userDisableService->disableUser(
            $userId,
            $user['first_name'] . ' ' . $user['last_name'],
            $user['email'],
            'admin',
            (bool) $user['admin']
        );

        if (!$success) {
            return new JsonResponse(['error' => 'Cannot disable this user (super admin or last active admin)'], Response::HTTP_CONFLICT);
        }

        return new JsonResponse(['success' => true]);
    }

    #[Route(path: '/users/{userId}/enable', name: 'api.action.admin_guard.user.enable', methods: ['POST'], defaults: ['_acl' => ['elixdigi_admin_guard:manage']])]
    public function enableUser(string $userId): JsonResponse
    {
        if (!Uuid::isValid($userId)) {
            return new JsonResponse(['error' => 'Invalid user ID'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUserInfo($userId);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $this->userDisableService->enableUser(
            $userId,
            $user['first_name'] . ' ' . $user['last_name'],
            $user['email'],
            'admin'
        );

        return new JsonResponse(['success' => true]);
    }

    #[Route(path: '/audit-log', name: 'api.action.admin_guard.audit_log', methods: ['GET'])]
    public function listAuditLog(Request $request, Context $context): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 25)));

        $result = $this->auditLogService->getLogEntries($page, $limit, $context);

        $entries = [];
        foreach ($result->getEntities() as $entry) {
            $entries[] = [
                'id' => $entry->getId(),
                'userId' => $entry->getUserId(),
                'userName' => $entry->getUserName(),
                'userEmail' => $entry->getUserEmail(),
                'action' => $entry->getAction(),
                'details' => $entry->getDetails(),
                'createdAt' => $entry->getCreatedAt()?->format('c'),
            ];
        }

        return new JsonResponse([
            'data' => $entries,
            'total' => $result->getTotal(),
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    #[Route(path: '/audit-log/export', name: 'api.action.admin_guard.audit_log.export', methods: ['GET'])]
    public function exportAuditLog(Context $context): Response
    {
        $csv = $this->auditLogService->exportCsv($context);

        return new Response($csv, Response::HTTP_OK, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="admin-guard-audit-log.csv"',
        ]);
    }

    private function getUserInfo(string $userId): ?array
    {
        return $this->connection->fetchAssociative(
            'SELECT first_name, last_name, email, admin FROM `user` WHERE `id` = :id',
            ['id' => Uuid::fromHexToBytes($userId)]
        ) ?: null;
    }
}
