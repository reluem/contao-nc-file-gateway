<?php

declare(strict_types=1);

namespace Reluem\ContaoNcFileGatewayBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Column;

class ConvertFilePathToFileTreeMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_nc_gateway', 'tl_files'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_nc_gateway');

        if (!isset($columns['file_path'])) {
            return false;
        }

        return !$this->isBinaryColumn($columns['file_path']);
    }

    public function run(): MigrationResult
    {
        $gateways = $this->connection->fetchAllAssociative(
            "SELECT id, file_path FROM tl_nc_gateway WHERE type = 'file' AND file_path IS NOT NULL AND file_path != ''",
        );

        $resolved = [];

        foreach ($gateways as $gateway) {
            $path = (string) $gateway['file_path'];
            $normalized = preg_replace('#^/?files/#', '', str_replace('\\', '/', trim($path))) ?? '';
            $fullPath = 'files/'.ltrim($normalized, '/');

            $uuid = $this->connection->fetchOne(
                'SELECT uuid FROM tl_files WHERE path = ? AND type = ?',
                [$fullPath, 'folder'],
            );

            $resolved[(int) $gateway['id']] = \is_string($uuid) && '' !== $uuid ? $uuid : null;
        }

        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof MySQLPlatform) {
            $this->connection->executeStatement(
                'ALTER TABLE tl_nc_gateway CHANGE file_path file_path BINARY(16) DEFAULT NULL',
            );
        } else {
            $this->connection->executeStatement(
                'ALTER TABLE tl_nc_gateway ALTER COLUMN file_path DROP NOT NULL',
            );
            $this->connection->executeStatement(
                'ALTER TABLE tl_nc_gateway ALTER COLUMN file_path TYPE BINARY(16)',
            );
        }

        foreach ($resolved as $id => $uuid) {
            $this->connection->executeStatement(
                'UPDATE tl_nc_gateway SET file_path = ? WHERE id = ?',
                [$uuid, $id],
                [\PDO::PARAM_LOB, \PDO::PARAM_INT],
            );
        }

        return $this->createResult(true);
    }

    private function isBinaryColumn(Column $column): bool
    {
        $type = $column->getType()->getName();

        return 'binary' === $type || 'blob' === $type;
    }
}
