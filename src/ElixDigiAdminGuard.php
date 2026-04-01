<?php declare(strict_types=1);

namespace ElixentDigital\ElixDigiAdminGuard;

use Doctrine\DBAL\Connection;
use ElixentDigital\ElixDigiAdminGuard\Service\BootstrapSeederService;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;

class ElixDigiAdminGuard extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);

        $seeder = new BootstrapSeederService($this->container->get(Connection::class));
        $seeder->seedTrackingFromExistingData();
    }
}
