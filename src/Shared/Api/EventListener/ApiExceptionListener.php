<?php

declare(strict_types=1);

namespace App\Shared\Api\EventListener;

use App\Shared\Api\Response\ApiResponse;
use App\Shared\Domain\DomainException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

//#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class ApiExceptionListener
{
    public function __construct(
        #[\Symfony\Component\DependencyInjection\Attribute\Autowire(env: 'APP_ENV')]
        private readonly string $environment,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $response = match (true) {
            $exception instanceof DomainException => ApiResponse::error(
                $exception->getMessage(),
                422,
            ),

            $exception instanceof HttpExceptionInterface => $this->handleHttpException($exception),

            default => $this->handleUnexpected($exception),
        };

        $event->setResponse($response);
    }

    private function handleHttpException(HttpExceptionInterface $exception): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $previous = $exception->getPrevious();

        if ($previous instanceof ValidationFailedException) {
            $errors = [];
            foreach ($previous->getViolations() as $violation) {
                $errors[] = ($violation->getPropertyPath() ? $violation->getPropertyPath() . ': ' : '') . $violation->getMessage();
            }

            return ApiResponse::error('Validation failed', 422, $errors);
        }

        return ApiResponse::error($exception->getMessage(), $exception->getStatusCode());
    }

    private function handleUnexpected(\Throwable $exception): \Symfony\Component\HttpFoundation\JsonResponse
    {
        if ($this->environment !== 'prod') {
            return ApiResponse::error($exception->getMessage(), 500);
        }

        return ApiResponse::error('Internal server error', 500);
    }
}
