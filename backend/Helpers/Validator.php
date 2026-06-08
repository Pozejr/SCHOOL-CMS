<?php

namespace App\Helpers;

class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    public function validate(): bool
    {
        foreach ($this->rules as $field => $fieldRules) {
            $fieldRules = explode('|', $fieldRules);
            foreach ($fieldRules as $rule) {
                $this->applyRule($field, $rule);
            }
        }
        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getValidated(): array
    {
        $validated = [];
        foreach ($this->rules as $field => $rule) {
            if (isset($this->data[$field])) {
                $validated[$field] = $this->data[$field];
            }
        }
        return $validated;
    }

    private function applyRule(string $field, string $rule): void
    {
        $value = $this->data[$field] ?? null;
        $params = [];

        if (str_contains($rule, ':')) {
            [$rule, $paramStr] = explode(':', $rule, 2);
            $params = explode(',', $paramStr);
        }

        switch ($rule) {
            case 'required':
                if ($value === null || $value === '') {
                    $this->addError($field, "{$field} is required");
                }
                break;
            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "{$field} must be a valid email address");
                }
                break;
            case 'min':
                if ($value && strlen((string)$value) < (int)$params[0]) {
                    $this->addError($field, "{$field} must be at least {$params[0]} characters");
                }
                break;
            case 'max':
                if ($value && strlen((string)$value) > (int)$params[0]) {
                    $this->addError($field, "{$field} must not exceed {$params[0]} characters");
                }
                break;
            case 'in':
                if ($value && !in_array($value, $params)) {
                    $allowed = implode(', ', $params);
                    $this->addError($field, "{$field} must be one of: {$allowed}");
                }
                break;
            case 'alpha_num':
                if ($value && !preg_match('/^[a-zA-Z0-9_-]+$/', $value)) {
                    $this->addError($field, "{$field} must contain only letters, numbers, hyphens, and underscores");
                }
                break;
            case 'string':
                if ($value !== null && !is_string($value)) {
                    $this->addError($field, "{$field} must be a string");
                }
                break;
        }
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[] = ['field' => $field, 'message' => $message];
    }
}
