<?php

declare(strict_types=1);

namespace App\Shared\Api\Exception;

use App\Announcements\Domain\Exception\AnnouncementNotFoundException;
use App\Announcements\Domain\Exception\AnnouncementVariantNotFoundException;
use App\Announcements\Domain\Exception\AudioAssetUnavailableException;
use App\Announcements\Domain\Exception\DuplicateFlightAnnouncementConfigException;
use App\Announcements\Domain\Exception\FlightAnnouncementConfigNotFoundException;
use App\Announcements\Domain\Exception\FlightDefinitionNotFoundException as AnnouncementFlightDefinitionNotFoundException;
use App\AudioCatalog\Domain\Exception\AudioPromptAssetUnavailableException;
use App\AudioCatalog\Domain\Exception\AudioPromptNotFoundException;
use App\AudioCatalog\Domain\Exception\DuplicateAudioPromptException;
use App\AudioCatalog\Domain\Exception\InvalidAudioAssetUploadException;
use App\FlightOperations\Domain\Exception\AirportNotFoundException;
use App\FlightOperations\Domain\Exception\DuplicateAirportException;
use App\FlightOperations\Domain\Exception\DuplicateFlightDefinitionException;
use App\FlightOperations\Domain\Exception\DuplicateOperationalResourceException;
use App\FlightOperations\Domain\Exception\FlightDefinitionNotFoundException as FlightOperationsFlightDefinitionNotFoundException;
use App\FlightOperations\Domain\Exception\OperationalResourceNotFoundException;
use App\Shared\Domain\DomainException;
use App\UserAccess\Domain\Exception\InvalidCredentialsException;
use App\UserAccess\Domain\Exception\InvalidRefreshTokenException;
use App\UserAccess\Domain\Exception\UserAlreadyExistsException;
use Symfony\Component\HttpFoundation\Response;

final class DomainExceptionHttpStatusMapper
{
    /**
     * @var array<class-string<DomainException>, int>
     */
    private const STATUS_BY_EXCEPTION = [
        InvalidCredentialsException::class => Response::HTTP_UNAUTHORIZED,
        InvalidRefreshTokenException::class => Response::HTTP_UNAUTHORIZED,
        UserAlreadyExistsException::class => Response::HTTP_CONFLICT,
        DuplicateFlightDefinitionException::class => Response::HTTP_CONFLICT,
        FlightOperationsFlightDefinitionNotFoundException::class => Response::HTTP_NOT_FOUND,
        DuplicateAirportException::class => Response::HTTP_CONFLICT,
        AirportNotFoundException::class => Response::HTTP_NOT_FOUND,
        AnnouncementNotFoundException::class => Response::HTTP_NOT_FOUND,
        AnnouncementFlightDefinitionNotFoundException::class => Response::HTTP_NOT_FOUND,
        DuplicateFlightAnnouncementConfigException::class => Response::HTTP_CONFLICT,
        FlightAnnouncementConfigNotFoundException::class => Response::HTTP_NOT_FOUND,
        AnnouncementVariantNotFoundException::class => Response::HTTP_NOT_FOUND,
        AudioAssetUnavailableException::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        InvalidAudioAssetUploadException::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        AudioPromptAssetUnavailableException::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        AudioPromptNotFoundException::class => Response::HTTP_NOT_FOUND,
        DuplicateAudioPromptException::class => Response::HTTP_CONFLICT,
        DuplicateOperationalResourceException::class => Response::HTTP_CONFLICT,
        OperationalResourceNotFoundException::class => Response::HTTP_NOT_FOUND,
    ];

    public function statusFor(DomainException $exception): int
    {
        foreach (self::STATUS_BY_EXCEPTION as $class => $status) {
            if ($exception instanceof $class) {
                return $status;
            }
        }

        return Response::HTTP_UNPROCESSABLE_ENTITY;
    }
}
