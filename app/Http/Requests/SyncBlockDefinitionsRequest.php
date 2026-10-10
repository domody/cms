<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncBlockDefinitionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'blocks' => ['required', 'array'],
            'blocks.*.key' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_-]+$/'],
            'blocks.*.label' => ['required', 'string', 'max:255'],
            'blocks.*.version' => ['sometimes', 'integer', 'min:1'],
            'blocks.*.fields' => ['required', 'array'],
            'blocks.*.fields.*.name' => ['required', 'string', 'max:64'],
            'blocks.*.fields.*.type' => ['required', 'string', 'max:32'],
            'blocks.*.fields.*.label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'blocks.*.fields.*.required' => ['sometimes', 'boolean'],
            'blocks.*.fields.*.options' => ['sometimes', 'array'],
            'blocks.*.fields.*.options.*' => ['string', 'max:255'],
            'pages' => ['sometimes', 'array'],
            'pages.*.slug' => ['required', 'string', 'max:255'],
            'pages.*.title' => ['required', 'string', 'max:255'],
            'pages.*.blocks' => ['required', 'array'],
            'pages.*.blocks.*.type' => ['required', 'string', 'max:64'],
            'pages.*.blocks.*.content' => ['sometimes', 'array'],
        ];
    }
}
