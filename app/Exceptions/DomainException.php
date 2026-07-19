<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Business-rule failure for School ERP domains (enrollment, fees, capacity, etc.).
 * Rendered as HTTP 422 with a stable error_code for clients.
 */
class DomainException extends RuntimeException
{
    /**
     * @param  array<string, list<string>>  $errors
     */
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly array $errors = [],
        public readonly int $status = 422,
    ) {
        parent::__construct($message);
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    public static function make(string $errorCode, string $message, array $errors = [], int $status = 422): self
    {
        return new self($message, $errorCode, $errors, $status);
    }

    /**
     * @return array{message: string, error_code: string, errors: array<string, list<string>>}
     */
    public function toArray(): array
    {
        $errors = $this->errors !== []
            ? $this->errors
            : ['domain' => [$this->getMessage()]];

        return [
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'errors' => $errors,
        ];
    }
}
