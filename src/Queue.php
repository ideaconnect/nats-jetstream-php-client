<?php

declare(strict_types=1);

namespace Basis\Nats;

use Basis\Nats\Message\Msg;
use Basis\Nats\Message\Publish;
use InvalidArgumentException;
use OverflowException;
use UnderflowException;

class Queue
{
    private array $queue = [];
    private float $timeout;
    private int $maxQueueSize = 0;
    private ?Publish $launcher = null;
    private ?Msg $lastMessage = null;

    public function __construct(
        public readonly Client $client,
        public readonly string $subject,
    ) {
        $this->timeout = $client->configuration->timeout;
    }

    public function fetch(): ?Msg
    {
        $messages = $this->fetchAll(1);
        return array_shift($messages);
    }

    public function fetchAll(int $limit = 0): array
    {
        if ($this->launcher) {
            $this->client->connection->sendMessage($this->launcher);
        }
        $max = microtime(true) + $this->timeout;
        while (true) {
            $now = microtime(true);
            if ($limit && count($this->queue) >= $limit) {
                // optional limit reached
                break;
            }

            $now = microtime(true);
            $processingTimeout = $this->timeout ? $max - $now : 0;
            if ($processingTimeout < 0) {
                // optional timeout reached
                break;
            }

            if ($this->client->process($processingTimeout) !== $this) {
                // stop when clients got message for another handler or there are no more messages
                break;
            }

            if (
                $this->lastMessage?->payload->isEmpty()
                && $this->lastMessage->payload->getHeader('Status-Code') === '404'
            ) {
                break;
            }
        }

        $result = [];
        while (count($this->queue) && (!$limit || count($result) < $limit)) {
            $message = array_shift($this->queue);
            $result[] = $message;
        }

        return $result;
    }

    public function handle(Msg $message): void
    {
        if ($this->maxQueueSize > 0 && count($this->queue) >= $this->maxQueueSize) {
            throw new OverflowException("Queue for subject {$this->subject} exceeded {$this->maxQueueSize} messages");
        }

        $this->queue[] = $this->lastMessage = $message;
    }

    public function next(float $timeout = 0): Msg
    {
        $start = microtime(true);
        while (true) {
            $message = $this->fetch();
            if ($message) {
                return $message;
            }
            if ($timeout && ($start + $timeout < microtime(true))) {
                throw new UnderflowException("Subject {$this->subject} is empty");
            }
        }
    }

    public function setMaxQueueSize(int $value): self
    {
        if ($value < 0) {
            throw new InvalidArgumentException('Queue max size must be zero or greater');
        }

        $this->maxQueueSize = $value;

        return $this;
    }

    public function getMaxQueueSize(): int
    {
        return $this->maxQueueSize;
    }

    public function setLauncher(Publish $message): void
    {
        $this->launcher = $message;
    }

    public function setTimeout(float $value): void
    {
        $this->timeout = $value;
    }
}
