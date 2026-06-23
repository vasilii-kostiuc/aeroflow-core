<?php

declare(strict_types=1);

namespace App\Tests\Unit\Architecture;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class AnnouncementsBoundaryTest extends TestCase
{
    public function testAnnouncementsOnlyAccessesFlightOperationsThroughIntegrationAdapter(): void
    {
        $announcementsDirectory = dirname(__DIR__, 3).'/src/Announcements';
        $allowedDirectory = $announcementsDirectory.'/Infrastructure/Integration/FlightOperations/';
        $forbiddenNamespace = 'App\\FlightOperations\\';
        $violations = [];
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($announcementsDirectory, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            if (str_starts_with($path, $allowedDirectory)) {
                continue;
            }

            $contents = file_get_contents($path);
            if ($contents !== false && str_contains($contents, $forbiddenNamespace)) {
                $violations[] = substr($path, strlen(dirname(__DIR__, 3)) + 1);
            }
        }

        self::assertSame([], $violations, sprintf(
            "Announcements must access Flight Operations only through its integration adapter.\nViolations:\n%s",
            implode("\n", $violations),
        ));
    }
}
