<?php

declare(strict_types=1);

namespace App\Tests\Integration\Announcements\Infrastructure\Persistence\Doctrine;

use App\Announcements\Domain\Entity\Announcement;
use App\Announcements\Domain\Repository\AnnouncementRepositoryInterface;
use App\Announcements\Domain\ValueObject\AnnouncementLanguages;
use App\Shared\Domain\ValueObject\LanguageCode;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

final class AnnouncementRepositoryTest extends KernelTestCase
{
    public function testPersistsFlightReferenceAndOrderedLanguages(): void
    {
        self::bootKernel();
        $repository = self::getContainer()->get(AnnouncementRepositoryInterface::class);
        self::assertInstanceOf(AnnouncementRepositoryInterface::class, $repository);
        $flightDefinitionId = Uuid::v7()->toRfc4122();
        $announcement = Announcement::announceArrival(
            $flightDefinitionId,
            AnnouncementLanguages::fromCodes(
                LanguageCode::fromString('en'),
                LanguageCode::fromString('ro'),
                LanguageCode::fromString('ru'),
            ),
        );
        $announcement->pullEvents();

        $repository->save($announcement);
        $loaded = $repository->findById($announcement->getId());

        self::assertNotNull($loaded);
        self::assertSame($flightDefinitionId, $loaded->getFlightDefinitionId()->toRfc4122());
        self::assertSame(['en', 'ro', 'ru'], $loaded->getLanguages()->toStrings());
    }
}
