<?php

declare(strict_types=1);

namespace App\Shared\Api\Response;

final readonly class ApiError
{
    public function __construct(
        public string $message,
        public ?string $field = null,
        public ?string $code = null,
    ) {
    }

    /**
     * @return array{message: string, field?: string, code?: string}
     */
    public function toArray(): array
    {
        $error = [
            'message' => $this->message,
        ];

        if ($this->field !== null) {
            $error['field'] = $this->field;
        }

        if ($this->code !== null) {
            $error['code'] = $this->code;
        }

        return $error;
    }
}
