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
        'UserAccess',
    ];

    /**
     * Existing dependencies that must be removed through a dedicated refactoring.
     *
     * @var list<string>
     */
    private const KNOWN_VIOLATIONS = [
        'src/Announcements/Application/AddAnnouncementVariant/AddAnnouncementVariantHandler.php -> App\\AudioCatalog\\Domain\\Exception\\AudioAssetUnavailableException',
        'src/Announcements/Application/AddAnnouncementVariant/AddAnnouncementVariantHandler.php -> App\\AudioCatalog\\Domain\\Repository\\AudioAssetRepositoryInterface',
        'src/Announcements/Application/UpdateAnnouncementVariant/UpdateAnnouncementVariantHandler.php -> App\\AudioCatalog\\Domain\\Exception\\AudioAssetUnavailableException',
        'src/Announcements/Application/UpdateAnnouncementVariant/UpdateAnnouncementVariantHandler.php -> App\\AudioCatalog\\Domain\\Repository\\AudioAssetRepositoryInterface',
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

        $knownViolations = self::KNOWN_VIOLATIONS;
        sort($actualViolations);
        sort($knownViolations);

        self::assertSame($knownViolations, $actualViolations, sprintf(
            "Bounded contexts must interact through ports and integration adapters.\n"
            ."Update the known-violations baseline only when intentionally removing existing debt.\n"
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
