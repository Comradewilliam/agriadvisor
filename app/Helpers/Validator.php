<?php

namespace App\Helpers;

class Validator
{
    private array $errors = [];
    private array $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function make(array $data): self
    {
        return new self($data);
    }

    public function validate(array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;
            foreach (explode('|', $ruleString) as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }

        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors[array_key_first($this->errors)] ?? null;
    }

    public function get(string $field, mixed $default = null): mixed
    {
        return $this->data[$field] ?? $default;
    }

    public function sanitized(string $field, mixed $default = null): mixed
    {
        $value = $this->get($field, $default);
        if ($value === null) {
            return null;
        }
        return is_string($value) ? Sanitizer::string($value) : $value;
    }

    private function applyRule(string $field, mixed $value, string $rule): void
    {
        if ($rule === 'nullable' && ($value === null || $value === '')) {
            return;
        }

        if (str_starts_with($rule, 'max:')) {
            $max = (int)substr($rule, 4);
            if (is_string($value) && mb_strlen($value) > $max) {
                $this->errors[$field] = "{$field} must not exceed {$max} characters.";
            }
            return;
        }

        if (str_starts_with($rule, 'min:')) {
            $min = (float)substr($rule, 4);
            if (is_numeric($value) && (float)$value < $min) {
                $this->errors[$field] = "{$field} must be at least {$min}.";
            }
            return;
        }

        if (str_starts_with($rule, 'in:')) {
            $allowed = explode(',', substr($rule, 3));
            if (!in_array((string)$value, $allowed, true)) {
                $this->errors[$field] = "{$field} has an invalid value.";
            }
            return;
        }

        match ($rule) {
            'required' => $this->ruleRequired($field, $value),
            'email' => $this->ruleEmail($field, $value),
            'phone' => $this->rulePhone($field, $value),
            'int' => $this->ruleInt($field, $value),
            'float' => $this->ruleFloat($field, $value),
            'array' => $this->ruleArray($field, $value),
            default => null,
        };
    }

    private function ruleRequired(string $field, mixed $value): void
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->errors[$field] = "{$field} is required.";
        }
    }

    private function ruleEmail(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "{$field} must be a valid email.";
        }
    }

    private function rulePhone(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && !preg_match('/^\+?[0-9]{9,15}$/', preg_replace('/\s+/', '', (string)$value))) {
            $this->errors[$field] = "{$field} must be a valid phone number.";
        }
    }

    private function ruleInt(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->errors[$field] = "{$field} must be an integer.";
        }
    }

    private function ruleFloat(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->errors[$field] = "{$field} must be a number.";
        }
    }

    private function ruleArray(string $field, mixed $value): void
    {
        if ($value !== null && !is_array($value)) {
            $this->errors[$field] = "{$field} must be an array.";
        }
    }
}
