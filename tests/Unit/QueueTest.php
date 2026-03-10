<?php

declare(strict_types=1);

namespace Tests\Unit;

use Basis\Nats\Client;
use Basis\Nats\Configuration;
use Basis\Nats\Message\Msg;
use Basis\Nats\Queue;
use InvalidArgumentException;
use OverflowException;
use Tests\TestCase;
use UnderflowException;

class QueueTest extends TestCase
{
    public function testSetMaxQueueSizeRejectsNegativeValues(): void
    {
        $queue = $this->createQueue();

        $this->expectException(InvalidArgumentException::class);
        $queue->setMaxQueueSize(-1);
    }

    public function testHandleThrowsOverflowWhenQueueLimitIsReached(): void
    {
        $queue = $this->createQueue()->setMaxQueueSize(1);

        $queue->handle($this->createMessage('first'));

        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Queue for subject test.subject exceeded 1 messages');
        $queue->handle($this->createMessage('second'));
    }

    public function testNextThrowsUnderflowWhenQueueIsEmpty(): void
    {
        $queue = new class(new Client(new Configuration(timeout: 0.001, reconnect: false)), 'test.subject') extends Queue {
            public function fetch(): ?Msg
            {
                return null;
            }
        };

        $this->expectException(UnderflowException::class);
        $this->expectExceptionMessage('Subject test.subject is empty');
        $queue->next(0.001);
    }

    private function createQueue(): Queue
    {
        $client = new Client(new Configuration(timeout: 0.001, reconnect: false));

        return new Queue($client, 'test.subject');
    }

    private function createMessage(string $body): Msg
    {
        return Msg::create('MSG test.subject sid1 ' . strlen($body))->parse($body);
    }
}