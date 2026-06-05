<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly \Redis $redis,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('/api/health', name: 'api_health', methods: [Request::METHOD_GET])]
    public function __invoke(): JsonResponse
    {
        $healthy = true;

        try {
            $this->connection->executeQuery('SELECT 1');
        } catch (\Throwable $e) {
            $this->logger->error('Health check DB failure: {message}', ['message' => $e->getMessage()]);
            $healthy = false;
        }

        try {
            $result = $this->redis->ping();
            if ($result === false) {
                $this->logger->error('Health check Redis failure: ping returned false');
                $healthy = false;
            }
        } catch (\Throwable $e) {
            $this->logger->error('Health check Redis failure: {message}', ['message' => $e->getMessage()]);
            $healthy = false;
        }

        if ($healthy) {
            return $this->json(['status' => 'ok']);
        }

        return $this->json(['status' => 'degraded'], JsonResponse::HTTP_SERVICE_UNAVAILABLE);
    }
}
