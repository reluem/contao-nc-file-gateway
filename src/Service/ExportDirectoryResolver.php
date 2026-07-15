<?php

declare(strict_types=1);

namespace Reluem\ContaoNcFileGatewayBundle\Service;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FilesModel;
use Contao\Validator;

class ExportDirectoryResolver
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function resolve(string $value): ?string
    {
        $value = trim($value);

        if ('' === $value) {
            return null;
        }

        if (Validator::isStringUuid($value) || $this->isBinaryUuid($value)) {
            return $this->resolveFromUuid($value);
        }

        return $this->normalizeLegacyPath($value);
    }

    private function resolveFromUuid(string $uuid): ?string
    {
        $this->framework->initialize();

        /** @var FilesModel|null $folder */
        $folder = $this->framework
            ->getAdapter(FilesModel::class)
            ->findByUuid($uuid);

        if (null === $folder || 'folder' !== $folder->type) {
            return null;
        }

        return $this->normalizeLegacyPath($folder->path);
    }

    private function normalizeLegacyPath(string $path): ?string
    {
        $path = trim(str_replace('\\', '/', $path), '/');
        $path = preg_replace('#^files/#', '', $path) ?? $path;

        return '' === $path ? null : $path;
    }

    private function isBinaryUuid(string $value): bool
    {
        return 16 === \strlen($value);
    }
}
