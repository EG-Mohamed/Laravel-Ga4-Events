<?php

namespace MohamedSaid\LaravelGa4Events\Exceptions;

use InvalidArgumentException;

class InvalidGa4EventPayloadException extends InvalidArgumentException
{
    public static function fromErrors(array $errors): self
    {
        $message = implode(' ', array_values(array_filter($errors, fn (mixed $error): bool => is_string($error) && $error !== '')));

        if ($message === '') {
            $message = (string) __('The GA4 event payload is invalid.');
        }

        return new self($message);
    }
}
