<?php

declare(strict_types=1);

namespace Tests\Unit\Stream;

use Basis\Nats\Stream\ConsumerLimits;
use DomainException;
use Tests\TestCase;

class ConsumerLimitsTest extends TestCase
{
    public function testValidateReturnsArrayUnchanged(): void
    {
        $limits = [
            ConsumerLimits::MAX_ACK_PENDING    => 100,
            ConsumerLimits::INACTIVE_THRESHOLD => 5_000_000_000,
        ];

        $this->assertSame($limits, ConsumerLimits::validate($limits));
    }

    public function testValidateEmptyArrayIsValid(): void
    {
        $this->assertSame([], ConsumerLimits::validate([]));
    }

    public function testValidateThrowsOnInvalidMaxAckPendingType(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid param: ' . ConsumerLimits::MAX_ACK_PENDING);
        ConsumerLimits::validate([ConsumerLimits::MAX_ACK_PENDING => 'not-an-int']);
    }

    public function testValidateThrowsOnInvalidInactiveThresholdType(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid param: ' . ConsumerLimits::INACTIVE_THRESHOLD);
        ConsumerLimits::validate([ConsumerLimits::INACTIVE_THRESHOLD => 1.5]);
    }

    public function testValidateAllowsUnknownParamsPassthrough(): void
    {
        // Unknown param keys currently fall through as valid (return true default).
        $limits = ['custom_param' => 'anything'];
        $this->assertSame($limits, ConsumerLimits::validate($limits));
    }
}
