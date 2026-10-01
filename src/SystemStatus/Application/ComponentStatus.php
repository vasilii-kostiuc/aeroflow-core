<?php

declare(strict_types=1);

namespace App\SystemStatus\Application;

use OpenApi\Attributes as OA;

/** One line of the «Состояние системы» screen. */
#[OA\Schema(schema: 'ComponentStatus', required: ['key', 'name', 'status', 'detail'])]
final readonly class ComponentStatus
{
    public const string OK = 'ok';
    public const string WARNING = 'warning';
    public const string DOWN = 'down';
    /** The check itself could not run (e.g. the broker is unreachable). */
    public const string UNKNOWN = 'unknown';

    public function __construct(
        public string $key,
        public string $name,
        #[OA\Property(enum: ['ok', 'warning', 'down', 'unknown'])]
        public string $status,
        public string $detail,
    ) {
    }
}
