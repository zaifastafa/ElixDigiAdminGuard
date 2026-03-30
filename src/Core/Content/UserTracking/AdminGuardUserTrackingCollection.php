<?php declare(strict_types=1);

namespace ElixentDigital\ElixDigiAdminGuard\Core\Content\UserTracking;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void add(AdminGuardUserTrackingEntity $entity)
 * @method void set(string $key, AdminGuardUserTrackingEntity $entity)
 * @method AdminGuardUserTrackingEntity[] getIterator()
 * @method AdminGuardUserTrackingEntity[] getElements()
 * @method AdminGuardUserTrackingEntity|null get(string $key)
 * @method AdminGuardUserTrackingEntity|null first()
 * @method AdminGuardUserTrackingEntity|null last()
 */
class AdminGuardUserTrackingCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return AdminGuardUserTrackingEntity::class;
    }
}
