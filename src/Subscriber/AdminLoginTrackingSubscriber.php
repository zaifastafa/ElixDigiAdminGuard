<?php declare(strict_types=1);

namespace ElixentDigital\ElixDigiAdminGuard\Subscriber;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AdminLoginTrackingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -100],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if ($request->getPathInfo() !== '/api/oauth/token') {
            return;
        }

        if ($response->getStatusCode() !== 200) {
            return;
        }

        $grantType = $request->request->get('grant_type');
        if ($grantType !== 'password') {
            return;
        }

        $username = $request->request->get('username');
        if (empty($username)) {
            return;
        }

        try {
            $user = $this->connection->fetchAssociative(
                'SELECT id, first_name, last_name, email FROM `user` WHERE `username` = :username',
                ['username' => $username]
            );

            if (!$user) {
                return;
            }

            $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s.v');
            $userId = $user['id'];

            $existing = $this->connection->fetchOne(
                'SELECT id FROM `elixdigi_admin_guard_user_tracking` WHERE `user_id` = :userId',
                ['userId' => $userId]
            );

            if ($existing) {
                $this->connection->executeStatement(
                    'UPDATE `elixdigi_admin_guard_user_tracking` SET `last_login_at` = :now, `updated_at` = :now WHERE `user_id` = :userId',
                    ['now' => $now, 'userId' => $userId]
                );
            } else {
                $this->connection->executeStatement(
                    'INSERT INTO `elixdigi_admin_guard_user_tracking` (`id`, `user_id`, `last_login_at`, `status`, `created_at`) VALUES (:id, :userId, :now, :status, :now)',
                    [
                        'id' => Uuid::randomBytes(),
                        'userId' => $userId,
                        'now' => $now,
                        'status' => 'active',
                    ]
                );
            }

            $this->connection->executeStatement(
                'INSERT INTO `elixdigi_admin_guard_audit_log` (`id`, `user_id`, `user_name`, `user_email`, `action`, `created_at`) VALUES (:id, :userId, :userName, :userEmail, :action, :now)',
                [
                    'id' => Uuid::randomBytes(),
                    'userId' => $userId,
                    'userName' => $user['first_name'] . ' ' . $user['last_name'],
                    'userEmail' => $user['email'],
                    'action' => 'login',
                    'now' => $now,
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->warning('AdminGuard: Failed to track login', ['error' => $e->getMessage()]);
        }
    }
}
