<?php

declare(strict_types=1);

namespace Tests\Unit\Message;

use Basis\Nats\Message\Payload;
use Tests\TestCase;

class PayloadTest extends TestCase
{
    // --- parse() ---

    public function testParseFromPayload(): void
    {
        $original = new Payload('hello');
        $result = Payload::parse($original);
        $this->assertSame($original, $result);
    }

    public function testParseFromString(): void
    {
        $result = Payload::parse('hello');
        $this->assertInstanceOf(Payload::class, $result);
        $this->assertSame('hello', $result->body);
    }

    public function testParseFromArray(): void
    {
        $result = Payload::parse(['key' => 'value']);
        $this->assertInstanceOf(Payload::class, $result);
        $this->assertSame('{"key":"value"}', $result->body);
    }

    public function testParseFromOtherTypeReturnsEmpty(): void
    {
        $result = Payload::parse(null);
        $this->assertInstanceOf(Payload::class, $result);
        $this->assertSame('', $result->body);
    }

    // --- __construct() with hdrs ---

    public function testConstructorParsesHdrsFromBody(): void
    {
        $headers = "NATS/1.0\r\nFoo: bar\r\n";
        $encoded = base64_encode($headers);
        $body = json_encode(['message' => ['hdrs' => $encoded]]);

        $payload = new Payload($body);

        $this->assertSame('bar', $payload->getHeader('Foo'));
    }

    // --- __get() ---

    public function testMagicGetReturnsJsonProperty(): void
    {
        $payload = new Payload(json_encode(['name' => 'nekufa']));
        $this->assertSame('nekufa', $payload->name);
    }

    public function testMagicGetReturnsNullForMissingProperty(): void
    {
        $payload = new Payload(json_encode(['name' => 'nekufa']));
        $this->assertNull($payload->missing);
    }

    public function testMagicGetReturnsNullForNonJsonBody(): void
    {
        $payload = new Payload('plain text');
        $this->assertNull($payload->anything);
    }

    // --- __toString() ---

    public function testToStringReturnBody(): void
    {
        $payload = new Payload('hello world');
        $this->assertSame('hello world', (string) $payload);
    }

    // --- header methods ---

    public function testHasHeaderWhenPresent(): void
    {
        $payload = new Payload('', ['X-Custom' => 'yes']);
        $this->assertTrue($payload->hasHeader('X-Custom'));
    }

    public function testHasHeaderWhenAbsent(): void
    {
        $payload = new Payload('');
        $this->assertFalse($payload->hasHeader('X-Custom'));
    }

    public function testHasHeadersWhenNone(): void
    {
        $this->assertFalse((new Payload(''))->hasHeaders());
    }

    public function testHasHeadersWhenPresent(): void
    {
        $this->assertTrue((new Payload('', ['k' => 'v']))->hasHeaders());
    }

    public function testGetHeaderReturnsValue(): void
    {
        $payload = new Payload('', ['Status' => '404']);
        $this->assertSame('404', $payload->getHeader('Status'));
    }

    public function testGetHeaderReturnsNullWhenAbsent(): void
    {
        $payload = new Payload('');
        $this->assertNull($payload->getHeader('Missing'));
    }

    // --- getValues() / getValue() ---

    public function testGetValuesReturnsDecodedJson(): void
    {
        $payload = new Payload(json_encode(['a' => 1]));
        $values = $payload->getValues();
        $this->assertIsObject($values);
        $this->assertSame(1, $values->a);
    }

    public function testGetValuesReturnsNullForNonJson(): void
    {
        $payload = new Payload('not json');
        $this->assertNull($payload->getValues());
    }

    public function testGetValueReturnsNestedProperty(): void
    {
        $payload = new Payload(json_encode(['a' => ['b' => 42]]));
        $this->assertSame(42, $payload->getValue('a.b'));
    }

    public function testGetValueReturnsNullForMissingPath(): void
    {
        $payload = new Payload(json_encode(['a' => 1]));
        $this->assertNull($payload->getValue('a.b.c'));
    }

    // --- isEmpty() ---

    public function testIsEmptyWhenBodyIsEmpty(): void
    {
        $this->assertTrue((new Payload(''))->isEmpty());
    }

    public function testIsEmptyWhenBodyIsNotEmpty(): void
    {
        $this->assertFalse((new Payload('data'))->isEmpty());
    }

    // --- render() ---

    public function testRenderWithoutHeaders(): void
    {
        $payload = new Payload('hello');
        $this->assertSame("5\r\nhello", $payload->render());
    }

    public function testRenderWithHeaders(): void
    {
        $payload = new Payload('body', ['Status' => '404']);
        $rendered = $payload->render();

        $this->assertStringContainsString("NATS/1.0\r\n", $rendered);
        $this->assertStringContainsString("Status: 404\r\n", $rendered);
        $this->assertStringContainsString('body', $rendered);
    }

    // --- Stringable interface ---

    public function testImplementsStringable(): void
    {
        $this->assertInstanceOf(\Stringable::class, new Payload(''));
    }
}
