<?php

declare(strict_types=1);

namespace Basis\Nats\Exception;

class ApiException extends NatsException
{
    public function __construct(string $message, int $code = 0, public readonly ?object $error = null)
    {
        parent::__construct($message, $code);
    }

    public static function fromError(object $error): self
    {
        return new self(
            message: (string) ($error->description ?? 'JetStream API error'),
            code: (int) ($error->err_code ?? 0),
            error: $error,
        );
    }
}