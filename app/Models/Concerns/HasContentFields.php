<?php

namespace App\Models\Concerns;

use App\Models\Media;
use App\Models\Scopes\CurrentSite;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * For definitions (block types, collections) whose `schema` is a list of
 * editable fields declared by the frontend code. Knows how to turn the schema
 * into form state, validation rules and storable values.
 *
 * Field types: text, textarea, richtext, image, link, boolean, select, and
 * `collection` (blocks only) - stores the key of a collection the editor
 * picked; the API swaps it for that collection's published entries.
 *
 * @property array<int, array<string, mixed>>|null $schema
 */
trait HasContentFields
{
    /**
     * The schema's fields with defaults filled in.
     *
     * @return array<int, array{name: string, type: string, label: string, required: bool, options: array<int, string>, limit: int|null}>
     */
    public function fields(): array
    {
        return array_map(fn (array $field) => [
            'name' => $field['name'],
            'type' => $field['type'],
            'label' => $field['label'] ?? Str::headline($field['name']),
            'required' => (bool) ($field['required'] ?? false),
            'options' => $field['options'] ?? [],
            'limit' => isset($field['limit']) ? (int) $field['limit'] : null,
        ], $this->schema ?? []);
    }

    /**
     * Fields that hold a value the editor can change (all of them).
     *
     * @return array<int, array{name: string, type: string, label: string, required: bool, options: array<int, string>, limit: int|null}>
     */
    public function editableFields(): array
    {
        return $this->fields();
    }

    /**
     * Names of the fields holding a media id.
     *
     * @return array<int, string>
     */
    public function imageFieldNames(): array
    {
        return array_column(array_filter($this->fields(), fn (array $field) => $field['type'] === 'image'), 'name');
    }

    /**
     * Validation rules for content, keyed by full dotted path under $prefix
     * (e.g. "blocks.0.content").
     *
     * @return array<string, array<int, mixed>>
     */
    public function contentRules(string $prefix): array
    {
        $rules = [];

        foreach ($this->editableFields() as $field) {
            $path = "$prefix.{$field['name']}";
            $presence = $field['required'] ? 'required' : 'nullable';

            switch ($field['type']) {
                case 'link':
                    $rules["$path.label"] = [$presence, 'string', 'max:255'];
                    $rules["$path.url"] = [$presence, 'string', 'max:2048'];

                    break;
                case 'textarea':
                case 'richtext':
                    $rules[$path] = [$presence, 'string', 'max:10000'];

                    break;
                case 'image':
                    // The exists rule queries the table directly, ignoring
                    // SiteScope, so the site is constrained explicitly.
                    $rules[$path] = [
                        $presence,
                        'integer',
                        Rule::exists('media', 'id')->where('site_id', app(CurrentSite::class)->id()),
                    ];

                    break;
                case 'collection':
                    // Like images, the exists rule ignores SiteScope.
                    $rules[$path] = [
                        $presence,
                        'string',
                        Rule::exists('collection_definitions', 'key')->where('site_id', app(CurrentSite::class)->id()),
                    ];

                    break;
                case 'boolean':
                    $rules[$path] = ['boolean'];

                    break;
                case 'select':
                    $rules[$path] = [$presence, 'string', ...($field['options'] === [] ? [] : ['in:'.implode(',', $field['options'])])];

                    break;
                default:
                    $rules[$path] = [$presence, 'string', 'max:255'];
            }
        }

        return $rules;
    }

    /**
     * Stored content with a key for every editable field, so a form always
     * has something to bind to (e.g. after a field was added in code).
     *
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    public function contentWithBlanks(array $content): array
    {
        foreach ($this->editableFields() as $field) {
            $value = $content[$field['name']] ?? self::blankValue($field['type']);

            $content[$field['name']] = match ($field['type']) {
                'link' => ['label' => $value['label'] ?? null, 'url' => $value['url'] ?? null],
                'image' => Media::idFrom($value),
                default => $value,
            };
        }

        return $content;
    }

    /**
     * Merge submitted form state over stored content. Keys the schema no
     * longer declares are kept.
     *
     * @param  array<string, mixed>  $stored
     * @param  array<string, mixed>  $submitted
     * @return array<string, mixed>
     */
    public function normalizedContent(array $stored, array $submitted): array
    {
        foreach ($this->editableFields() as $field) {
            $stored[$field['name']] = self::normalizeValue(
                $field['type'],
                $submitted[$field['name']] ?? self::blankValue($field['type']),
            );
        }

        return $stored;
    }

    /**
     * A blank value for a field type.
     *
     * @return array<string, mixed>|bool|string|null
     */
    public static function blankValue(string $type): array|bool|string|null
    {
        return match ($type) {
            'link' => ['label' => null, 'url' => null],
            'boolean' => false,
            default => null,
        };
    }

    /**
     * A submitted value in the form it is stored.
     */
    public static function normalizeValue(string $type, mixed $value): mixed
    {
        if ($type === 'boolean') {
            return (bool) $value;
        }

        if ($type === 'image') {
            return Media::idFrom($value);
        }

        if ($type === 'link') {
            $label = filled($value['label'] ?? null) ? trim($value['label']) : null;
            $url = filled($value['url'] ?? null) ? trim($value['url']) : null;

            return $label === null && $url === null ? null : ['label' => $label, 'url' => $url];
        }

        return filled($value) ? Str::of($value)->trim()->toString() : null;
    }
}
