<?php

declare(strict_types=1);

namespace Reluem\ContaoNcFileGatewayBundle\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpKernel\KernelInterface;

class CsvFileWriter
{
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly Filesystem $filesystem,
    ) {
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function write(string $relativeDirectory, string $fileName, string $line, string $mode): string
    {
        $directory = $this->resolveDirectory($relativeDirectory);
        $path = Path::join($directory, $this->sanitizeFileName($fileName));

        if (!$this->filesystem->exists($directory)) {
            $this->filesystem->mkdir($directory);
        }

        $line = rtrim($line, "\r\n")."\n";

        if ('overwrite' === $mode || !$this->filesystem->exists($path)) {
            $this->filesystem->dumpFile($path, $line);
        } else {
            $this->filesystem->appendToFile($path, $line);
        }

        return $path;
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function resolveDirectory(string $relativeDirectory): string
    {
        $relativeDirectory = trim(str_replace('\\', '/', $relativeDirectory), '/');

        if ('' === $relativeDirectory) {
            throw new \InvalidArgumentException('The file path must not be empty.');
        }

        if (str_starts_with($relativeDirectory, '/')) {
            throw new \InvalidArgumentException('The file path must be relative to the Contao files directory.');
        }

        // Normalize legacy values such as "/files/exports/...".
        $relativeDirectory = preg_replace('#^files/#', '', $relativeDirectory) ?? $relativeDirectory;

        $filesRoot = Path::join($this->kernel->getProjectDir(), 'files');
        $directory = Path::join($filesRoot, $relativeDirectory);
        $baseDir = $this->kernel->getProjectDir();
        $normalizedRoot = Path::canonicalize(Path::makeAbsolute($filesRoot, $baseDir));
        $normalizedDirectory = Path::canonicalize(Path::makeAbsolute($directory, $baseDir));

        if (!str_starts_with($normalizedDirectory, $normalizedRoot)) {
            throw new \InvalidArgumentException('The file path must stay inside the Contao files directory.');
        }

        return $directory;
    }

    private function sanitizeFileName(string $fileName): string
    {
        $fileName = trim(str_replace('\\', '/', $fileName), '/');

        if ('' === $fileName || str_contains($fileName, '/')) {
            throw new \InvalidArgumentException('The file name must not contain directory separators.');
        }

        return $fileName;
    }
}
