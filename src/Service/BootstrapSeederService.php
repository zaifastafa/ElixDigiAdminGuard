<?php declare(strict_types=1);

namespace ElixentDigital\ElixDigiAdminGuard\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Uuid\Uuid;

class BootstrapSeederService
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function seedTrackingFromExistingData(): void
    {
        $existingCount = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM `elixdigi_admin_guard_user_tracking`'
        );

        if ($existingCount > 0) {
            return;
        }

        $users = $this->connection->fetchAllAssociative('
            SELECT
                u.id AS user_id,
                u.active,
                u.created_at,
                GREATEST(
                    COALESCE(MAX(rt.issued_at), \'1970-01-01\'),
                    COALESCE(MAX(uak.last_usage_at), \'1970-01-01\')
                ) AS best_last_login
            FROM `user` u
            LEFT JOIN `refresh_token` rt ON rt.user_id = u.id
            LEFT JOIN `user_access_key` uak ON uak.user_id = u.id
            GROUP BY u.id, u.active, u.created_at
        ');

        $now = new \DateTimeImmutable();
        $epoch = '1970-01-01';

        // Hardcoded defaults: at install time, plugin config values are not yet written
        // to system_config. The scheduled task corrects any status drift on its next run
        // using the configured thresholds from SystemConfigService.
        $warningDays = 90;
        $dangerDays = 180;

        $this->connection->transactional(function () use ($users, $now, $epoch, $warningDays, $dangerDays): void {
            foreach ($users as $user) {
                $lastLogin = ($user['best_last_login'] > $epoch)
                    ? new \DateTimeImmutable($user['best_last_login'])
                    : null;

                $createdAt = new \DateTimeImmutable($user['created_at']);
                $referenceDate = $lastLogin ?? $createdAt;
                $daysInactive = (int) $now->diff($referenceDate)->days;

                if (!$user['active']) {
                    $status = 'disabled';
                } elseif ($lastLogin === null) {
                    $status = $daysInactive >= $dangerDays
                        ? 'danger'
                        : ($daysInactive >= $warningDays ? 'warning' : 'never_logged_in');
                } elseif ($daysInactive >= $dangerDays) {
                    $status = 'danger';
                } elseif ($daysInactive >= $warningDays) {
                    $status = 'warning';
                } else {
                    $status = 'active';
                }

                $this->connection->executeStatement(
                    'INSERT INTO `elixdigi_admin_guard_user_tracking` (`id`, `user_id`, `last_login_at`, `status`, `created_at`)
                     VALUES (:id, :userId, :lastLogin, :status, :now)',
                    [
                        'id' => Uuid::randomBytes(),
                        'userId' => $user['user_id'],
                        'lastLogin' => $lastLogin?->format('Y-m-d H:i:s.v'),
                        'status' => $status,
                        'now' => $now->format('Y-m-d H:i:s.v'),
                    ]
                );
            }
        });
    }
}
