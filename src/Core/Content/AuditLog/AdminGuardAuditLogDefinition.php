<?php declare(strict_types=1);

namespace ElixentDigital\ElixDigiAdminGuard\Core\Content\AuditLog;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\User\UserDefinition;

class AdminGuardAuditLogDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'elixdigi_admin_guard_audit_log';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return AdminGuardAuditLogEntity::class;
    }

    public function getCollectionClass(): string
    {
        return AdminGuardAuditLogCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            new FkField('user_id', 'userId', UserDefinition::class),
            (new StringField('user_name', 'userName'))->addFlags(new Required()),
            (new StringField('user_email', 'userEmail'))->addFlags(new Required()),
            (new StringField('action', 'action'))->addFlags(new Required()),
            new JsonField('details', 'details'),
            new ManyToOneAssociationField('user', 'user_id', UserDefinition::class, 'id', false),
        ]);
    }
}
