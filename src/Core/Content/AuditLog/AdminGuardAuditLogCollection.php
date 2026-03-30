<?php declare(strict_types=1);

namespace ElixentDigital\ElixDigiAdminGuard\Core\Content\AuditLog;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void add(AdminGuardAuditLogEntity $entity)
 * @method void set(string $key, AdminGuardAuditLogEntity $entity)
 * @method AdminGuardAuditLogEntity[] getIterator()
 * @method AdminGuardAuditLogEntity[] getElements()
 * @method AdminGuardAuditLogEntity|null get(string $key)
 * @method AdminGuardAuditLogEntity|null first()
 * @method AdminGuardAuditLogEntity|null last()
 */
class AdminGuardAuditLogCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return AdminGuardAuditLogEntity::class;
    }
}
