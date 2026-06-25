<?php

declare(strict_types=1);

namespace App\Tests\Unit\Architecture;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class BoundedContextDependenciesTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const BOUNDED_CONTEXTS = [
        'Announcements',
        'AudioCatalog',
        'FlightOperations',
        'Localization',
        'UserAccess',
    ];

    public function testBoundedContextsDoNotImportEachOtherOutsideIntegrationAdapters(): void
    {
        $projectDirectory = dirname(__DIR__, 3);
        $actualViolations = [];

        foreach (self::BOUNDED_CONTEXTS as $context) {
            $contextDirectory = $projectDirectory.'/src/'.$context;
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($contextDirectory, FilesystemIterator::SKIP_DOTS),
            );

            foreach ($files as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $path = $file->getPathname();
                if (str_contains($path, '/Infrastructure/Integration/')) {
                    continue;
                }

                $contents = file_get_contents($path);
                if ($contents === false) {
                    continue;
                }

                foreach ($this->importedBoundedContextClasses($contents, $context) as $import) {
                    $violation = sprintf(
                        '%s -> %s',
                        substr($path, strlen($projectDirectory) + 1),
                        $import,
                    );

                    $actualViolations[] = $violation;
                }
            }
        }

        sort($actualViolations);

        self::assertSame([], $actualViolations, sprintf(
            "Bounded contexts must interact through ports and integration adapters.\n"
            ."Actual dependencies:\n%s",
            implode("\n", $actualViolations),
        ));
    }

    /**
     * @return list<string>
     */
    private function importedBoundedContextClasses(string $contents, string $currentContext): array
    {
        preg_match_all(
            '/^use (App\\\\(?:'.implode('|', self::BOUNDED_CONTEXTS).'\\\\)[^;]+);$/m',
            $contents,
            $matches,
        );

        return array_values(array_filter(
            $matches[1],
            static fn (string $import): bool => !str_starts_with($import, 'App\\'.$currentContext.'\\'),
        ));
    }
}
