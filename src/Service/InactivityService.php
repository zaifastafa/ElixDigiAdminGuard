<?php declare(strict_types=1);

namespace ElixentDigital\ElixDigiAdminGuard\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class InactivityService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly SystemConfigService $systemConfigService,
    ) {
    }

    public function getAdminUsersWithStatus(): array
    {
        $warningDays = $this->systemConfigService->getInt('ElixDigiAdminGuard.config.warningDays') ?: 90;
        $dangerDays = $this->systemConfigService->getInt('ElixDigiAdminGuard.config.dangerDays') ?: 180;

        $sql = '
            SELECT
                u.id,
                u.username,
                u.first_name,
                u.last_name,
                u.email,
                u.active,
                u.admin,
                u.created_at,
                t.last_login_at,
                t.status as tracking_status,
                GROUP_CONCAT(ar.name SEPARATOR \', \') as role_names
            FROM `user` u
            LEFT JOIN `elixdigi_admin_guard_user_tracking` t ON t.user_id = u.id
            LEFT JOIN `acl_user_role` aur ON aur.user_id = u.id
            LEFT JOIN `acl_role` ar ON ar.id = aur.acl_role_id
            GROUP BY u.id
            ORDER BY u.last_name, u.first_name
        ';

        $rows = $this->connection->fetchAllAssociative($sql);
        $now = new \DateTimeImmutable();
        $result = [];

        foreach ($rows as $row) {
            $lastLogin = $row['last_login_at'] ? new \DateTimeImmutable($row['last_login_at']) : null;
            $createdAt = new \DateTimeImmutable($row['created_at']);
            $referenceDate = $lastLogin ?? $createdAt;
            $daysInactive = (int) $now->diff($referenceDate)->days;

            if (!$row['active']) {
                $status = 'disabled';
            } elseif ($lastLogin === null) {
                $status = $daysInactive >= $dangerDays ? 'danger' : ($daysInactive >= $warningDays ? 'warning' : 'never_logged_in');
            } elseif ($daysInactive >= $dangerDays) {
                $status = 'danger';
            } elseif ($daysInactive >= $warningDays) {
                $status = 'warning';
            } else {
                $status = 'active';
            }

            $result[] = [
                'id' => Uuid::fromBytesToHex($row['id']),
                'username' => $row['username'],
                'firstName' => $row['first_name'],
                'lastName' => $row['last_name'],
                'email' => $row['email'],
                'active' => (bool) $row['active'],
                'admin' => (bool) $row['admin'],
                'createdAt' => $createdAt->format('c'),
                'lastLoginAt' => $lastLogin?->format('c'),
                'daysInactive' => $daysInactive,
                'status' => $status,
                'roles' => $row['admin'] ? 'Super Admin' : ($row['role_names'] ?: 'No roles'),
            ];
        }

        return $result;
    }

}
