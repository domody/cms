<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\BlockDefinitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A block type declared by the frontend code (e.g. "hero") and its
 * editable fields. Synced from the code's manifest, never created by users.
 */
#[Fillable(['tenant_id', 'key', 'label', 'version', 'schema'])]
class BlockDefinition extends Model
{
    /** @use HasFactory<BlockDefinitionFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'version' => 'integer',
            'schema' => 'array',
        ];
    }

    /**
     * The schema's fields with defaults filled in (label, required, options).
     *
     * @return array<int, array{name: string, type: string, label: string, required: bool, options: array<int, string>}>
     */
    public function fields(): array
    {
        return array_map(fn (array $field) => [
            'name' => $field['name'],
            'type' => $field['type'],
            'label' => $field['label'] ?? Str::headline($field['name']),
            'required' => (bool) ($field['required'] ?? false),
            'options' => $field['options'] ?? [],
        ], $this->schema ?? []);
    }

    /**
     * Validation rules for a block's content, keyed by full dotted path
     * under $prefix (e.g. "blocks.0.content").
     *
     * @return array<string, array<int, string>>
     */
    public function contentRules(string $prefix): array
    {
        $rules = [];

        foreach ($this->fields() as $field) {
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
                    $rules[$path] = [$presence, 'string', 'max:2048'];

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
     * A blank value for a field type, used to fill content keys the block
     * doesn't have yet (e.g. after a field was added in code).
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
}
