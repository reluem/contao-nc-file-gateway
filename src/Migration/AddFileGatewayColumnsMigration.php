<?php

declare(strict_types=1);

namespace Reluem\ContaoNcFileGatewayBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class AddFileGatewayColumnsMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_nc_gateway', 'tl_nc_language'])) {
            return false;
        }

        $gatewayColumns = $schemaManager->listTableColumns('tl_nc_gateway');
        $languageColumns = $schemaManager->listTableColumns('tl_nc_language');

        return !isset($gatewayColumns['file_path'])
            || !isset($languageColumns['file_name']);
    }

    public function run(): MigrationResult
    {
        $schemaManager = $this->connection->createSchemaManager();
        $gatewayColumns = $schemaManager->listTableColumns('tl_nc_gateway');
        $languageColumns = $schemaManager->listTableColumns('tl_nc_language');

        if (!isset($gatewayColumns['file_type'])) {
            $this->connection->executeStatement("ALTER TABLE tl_nc_gateway ADD file_type VARCHAR(4) NOT NULL DEFAULT ''");
        }

        if (!isset($gatewayColumns['file_path'])) {
            $this->connection->executeStatement('ALTER TABLE tl_nc_gateway ADD file_path BINARY(16) DEFAULT NULL');
        }

        if (!isset($languageColumns['file_name'])) {
            $this->connection->executeStatement("ALTER TABLE tl_nc_language ADD file_name VARCHAR(255) NOT NULL DEFAULT ''");
        }

        if (!isset($languageColumns['file_storage_mode'])) {
            $this->connection->executeStatement("ALTER TABLE tl_nc_language ADD file_storage_mode VARCHAR(8) NOT NULL DEFAULT 'append'");
        }

        if (!isset($languageColumns['file_content'])) {
            $this->connection->executeStatement("ALTER TABLE tl_nc_language ADD file_content TEXT NULL DEFAULT NULL");
        }

        return $this->createResult(true);
    }
}
