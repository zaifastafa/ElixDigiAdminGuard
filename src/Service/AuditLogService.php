<?php declare(strict_types=1);

namespace ElixentDigital\ElixDigiAdminGuard\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;

class AuditLogService
{
    public function __construct(
        private readonly EntityRepository $elixdigiAdminGuardAuditLogRepository,
    ) {
    }

    public function log(string $action, ?string $userId, string $userName, string $userEmail, ?array $details = null): void
    {
        $context = Context::createCLIContext();
        $this->elixdigiAdminGuardAuditLogRepository->create([
            [
                'id' => Uuid::randomHex(),
                'userId' => $userId,
                'userName' => $userName,
                'userEmail' => $userEmail,
                'action' => $action,
                'details' => $details,
            ],
        ], $context);
    }

    public function getLogEntries(int $page, int $limit, Context $context): EntitySearchResult
    {
        $criteria = new Criteria();
        $criteria->setOffset(($page - 1) * $limit);
        $criteria->setLimit($limit);
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->addAssociation('user');

        return $this->elixdigiAdminGuardAuditLogRepository->search($criteria, $context);
    }

    public function exportCsv(Context $context): string
    {
        $criteria = new Criteria();
        $criteria->setLimit(10000);
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));

        $result = $this->elixdigiAdminGuardAuditLogRepository->search($criteria, $context);

        $lines = [];
        $lines[] = implode(',', ['Date', 'User Name', 'User Email', 'Action', 'Details']);

        foreach ($result->getEntities() as $entry) {
            $lines[] = implode(',', [
                '"' . $entry->getCreatedAt()->format('Y-m-d H:i:s') . '"',
                '"' . str_replace('"', '""', $entry->getUserName()) . '"',
                '"' . str_replace('"', '""', $entry->getUserEmail()) . '"',
                '"' . $entry->getAction() . '"',
                '"' . str_replace('"', '""', $entry->getDetails() ? json_encode($entry->getDetails()) : '') . '"',
            ]);
        }

        return implode("\n", $lines);
    }
}
