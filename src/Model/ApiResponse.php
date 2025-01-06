<?php

namespace App\Model;

use Symfony\Component\Serializer\Attribute\Groups;

class ApiResponse
{
    #[Groups(['default'])]
    private string $status;

    #[Groups(['success'])]
    private mixed $data;

    #[Groups(['default'])]
    private ?string $message;

    #[Groups(['error'])]
    private ?array $errors;

    #[Groups(['successMeta'])]
    private ?array $meta;

    public function __construct(
        string $status,
        mixed $data = null,
        ?string $message = null,
        ?array $errors = null,
        ?array $meta = null
    ) {
        $this->status = $status;
        $this->data = $data;
        $this->message = $message;
        $this->errors = $errors;
        $this->meta = $meta;
    }

    public static function successResponse(
        mixed $data = null,
        ?string $message = 'Request successful',
        ?array $meta = null
    ): self {
        return new self(
            status: 'success',
            data: $data,
            message: $message,
            errors: null,
            meta: $meta
        );
    }

    public static function errorResponse(
        ?string $message = 'Request failed',
        ?array $errors = null
    ): self {
        return new self(
            status: 'error',
            data: null,
            message: $message,
            errors: $errors
        );
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }

    public function getMeta(): ?array
    {
        return $this->meta;
    }
}
