<?php

declare(strict_types=1);

namespace Tests\Unit;

use Basis\Nats\Configuration;
use Tests\TestCase;

class ConfigurationTest extends TestCase
{
    public function testExponentialDelay(): void
    {
        $configuration = new Configuration(
            delayMode: Configuration::DELAY_EXPONENTIAL,
        );

        $this->assertSame($configuration->getDelayMode(), Configuration::DELAY_EXPONENTIAL);

        $start = microtime(true);
        $configuration->delay(0);
        $this->assertLessThan(0.01, microtime(true) - $start);
    }

    public function testExponentialDelayUsesPowerOfTenBackoff(): void
    {
        $configuration = new Configuration(
            delay: 0.01,
            delayMode: Configuration::DELAY_EXPONENTIAL,
        );

        $start = microtime(true);
        $configuration->delay(1);
        $elapsed = microtime(true) - $start;

        $this->assertGreaterThan(0.08, $elapsed);
        $this->assertLessThan(0.25, $elapsed);
    }

    public function testLinearDelayUsesBaseDelayOnFirstRetry(): void
    {
        $configuration = new Configuration(
            delay: 0.01,
            delayMode: Configuration::DELAY_LINEAR,
        );

        $start = microtime(true);
        $configuration->delay(0);
        $elapsed = microtime(true) - $start;

        $this->assertGreaterThan(0.008, $elapsed);
        $this->assertLessThan(0.05, $elapsed);
    }

    public function testInvalidDelayConfiguration(): void
    {
        $configuration = new Configuration();
        $this->expectExceptionMessage("Invalid mode: dreaming");
        $configuration->setDelay(1, 'dreaming');
    }

    public function testInvalidConfiguration(): void
    {
        $this->expectException(\Error::class);
        eval('new \\Basis\\Nats\\Configuration(hero: true);');
    }

    public function testClientConfigurationToken(): void
    {
        $connection = new Configuration(token: 'zzz');
        $this->assertArrayHasKey('auth_token', $connection->getOptions());
    }

    public function testClientConfigurationJwt(): void
    {
        $connection = new Configuration(jwt: random_bytes(16));
        $this->assertArrayHasKey('jwt', $connection->getOptions());
    }

    public function testClientConfigurationBasicAuth(): void
    {
        $connection = new Configuration(user: 'nekufa', pass: 't0p53cr3t');
        $this->assertArrayHasKey('user', $connection->getOptions());
        $this->assertArrayHasKey('pass', $connection->getOptions());
    }
}
