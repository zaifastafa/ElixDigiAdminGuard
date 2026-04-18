<?php declare(strict_types=1);

namespace ElixentDigital\ElixDigiAdminGuard\Core\Extension;

use ElixentDigital\ElixDigiAdminGuard\Core\Content\AuditLog\AdminGuardAuditLogDefinition;
use ElixentDigital\ElixDigiAdminGuard\Core\Content\UserTracking\AdminGuardUserTrackingDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Extension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\User\UserDefinition;

class UserExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToManyAssociationField(
                'adminGuardTracking',
                AdminGuardUserTrackingDefinition::class,
                'user_id'
            ))->addFlags(new Extension())
        );

        $collection->add(
            (new OneToManyAssociationField(
                'adminGuardAuditLogs',
                AdminGuardAuditLogDefinition::class,
                'user_id'
            ))->addFlags(new Extension())
        );
    }

    public function getEntityName(): string
    {
        return UserDefinition::ENTITY_NAME;
    }
}
