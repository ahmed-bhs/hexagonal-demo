<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Request ID Listener - Adds correlation ID to requests and responses.
 *
 * This listener demonstrates using Symfony's Kernel Events (NOT Domain Events).
 * It's a cross-cutting infrastructure concern for request tracing.
 *
 * Purpose:
 * - Generate unique request ID (correlation ID) for every HTTP request
 * - Add to request attributes (accessible in controllers, services)
 * - Add to response headers (for API clients, debugging)
 * - Add to logs (trace requests across microservices)
 *
 * Use cases:
 * - Distributed tracing (track request across services)
 * - Debugging (correlate logs with specific requests)
 * - API clients (track request/response pairs)
 * - Error reporting (include request ID in error messages)
 *
 * Kernel Events vs Domain Events:
 * - Kernel Events: Infrastructure concerns (HTTP, routing, exceptions)
 * - Domain Events: Business operations (GiftAttributed, GiftRequestSubmitted)
 *
 * Priority: 255 (very high)
 * - Executed early in request lifecycle
 * - Available to all subsequent listeners and controllers
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 255)]
#[AsEventListener(event: KernelEvents::RESPONSE, priority: 255)]
final readonly class RequestIdListener
{
    private const REQUEST_ID_HEADER = 'X-Request-ID';
    private const REQUEST_ID_ATTRIBUTE = 'request_id';

    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    /**
     * Handle kernel.request event.
     *
     * Generates or retrieves request ID and adds it to:
     * - Request attributes (for controllers/services)
     * - Logger context (for log correlation)
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        // Only process main requests (not sub-requests)
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Check if client provided request ID (for distributed tracing)
        $requestId = $request->headers->get(self::REQUEST_ID_HEADER);

        if (!$requestId) {
            // Generate new request ID if not provided
            $requestId = $this->generateRequestId();
        }

        // Store in request attributes (accessible in controllers)
        $request->attributes->set(self::REQUEST_ID_ATTRIBUTE, $requestId);

        // Add to logger context for all subsequent logs
        $this->logger->info('Request started', [
            'request_id' => $requestId,
            'method' => $request->getMethod(),
            'uri' => $request->getRequestUri(),
            'ip' => $request->getClientIp(),
        ]);
    }

    /**
     * Handle kernel.response event.
     *
     * Adds request ID to response headers so clients can track requests.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        // Only process main requests
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // Retrieve request ID from attributes
        $requestId = $request->attributes->get(self::REQUEST_ID_ATTRIBUTE);

        if ($requestId) {
            // Add to response headers
            $response->headers->set(self::REQUEST_ID_HEADER, $requestId);

            $this->logger->info('Request completed', [
                'request_id' => $requestId,
                'status_code' => $response->getStatusCode(),
            ]);
        }
    }

    /**
     * Generate unique request ID (UUID v7 for time-ordering).
     */
    private function generateRequestId(): string
    {
        return \Symfony\Component\Uid\Uuid::v7()->toRfc4122();
    }
}
