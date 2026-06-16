<?php

declare(strict_types=1);

namespace App\Shared\Api\Exception;

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
