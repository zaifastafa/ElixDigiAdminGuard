<?php declare(strict_types=1);

namespace ElixentDigital\ElixDigiAdminGuard\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1711800000CreateAdminGuardTables extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1711800000;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `elixdigi_admin_guard_user_tracking` (
                `id` BINARY(16) NOT NULL,
                `user_id` BINARY(16) NOT NULL,
                `last_login_at` DATETIME(3) NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT \'unknown\',
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq.user_id` (`user_id`),
                CONSTRAINT `fk.elixdigi_ag_tracking.user_id` FOREIGN KEY (`user_id`)
                    REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `elixdigi_admin_guard_audit_log` (
                `id` BINARY(16) NOT NULL,
                `user_id` BINARY(16) NULL,
                `user_name` VARCHAR(255) NOT NULL,
                `user_email` VARCHAR(255) NOT NULL,
                `action` VARCHAR(50) NOT NULL,
                `details` JSON NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                KEY `idx.user_id` (`user_id`),
                KEY `idx.action` (`action`),
                KEY `idx.created_at` (`created_at`),
                CONSTRAINT `fk.elixdigi_ag_audit.user_id` FOREIGN KEY (`user_id`)
                    REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
