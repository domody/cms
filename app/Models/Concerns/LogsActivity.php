<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Writes an ActivityLog entry when the model is created, changed or deleted.
 * Models override activityLabel() (what the entry calls the record) and
 * activityIgnored() (columns not worth logging). A model that is part of
 * another (a page's block) returns that one from activityParent(): entries
 * are filed under it as "updated", with the changed fields prefixed by
 * activityName().
 *
 * Only changes made through Eloquent models are seen: database cascades and
 * mass query updates are not.
 */
trait LogsActivity
{
    protected static function bootLogsActivity(): void
    {
        static::created(fn (Model $model) => $model->logActivity('created'));
        static::updated(fn (Model $model) => $model->logActivity('updated', $model->activityChanges()));
        static::deleted(fn (Model $model) => $model->logActivity('deleted'));
    }

    public function activityType(): string
    {
        return Str::snake(class_basename($this));
    }

    public function activityLabel(): string
    {
        return $this->title ?? $this->name ?? class_basename($this).' #'.$this->getKey();
    }

    /**
     * @return array<int, string>
     */
    public function activityIgnored(): array
    {
        return [];
    }

    public function activityParent(): ?Model
    {
        return null;
    }

    /**
     * Name of this record inside its parent (the block's type for a page block).
     */
    public function activityName(): string
    {
        return class_basename($this);
    }

    /**
     * @return array<string, array{old: mixed, new: mixed}>
     */
    protected function activityChanges(): array
    {
        $keys = collect($this->getChanges())
            ->except(['updated_at', 'created_at', ...$this->activityIgnored()])
            ->keys();

        $changes = ActivityLog::diff(
            $keys->mapWithKeys(fn ($key) => [$key => $this->getOriginal($key)])->all(),
            $keys->mapWithKeys(fn ($key) => [$key => $this->getAttribute($key)])->all(),
        );

        if ($this->activityParent() === null) {
            return $changes;
        }

        return collect($changes)
            ->mapWithKeys(fn ($change, $field) => [$this->activityName().' → '.$field => $change])
            ->all();
    }

    /**
     * @param  array<string, array{old: mixed, new: mixed}>|null  $changes
     */
    protected function logActivity(string $event, ?array $changes = null): void
    {
        $parent = $this->activityParent();

        if ($parent === null) {
            ActivityLog::record($this, $event, $changes);

            return;
        }

        $changes ??= [$this->activityName() => $event === 'created'
            ? ['old' => null, 'new' => 'block added']
            : ['old' => 'block present', 'new' => 'block removed']];

        ActivityLog::record($parent, 'updated', $changes);
    }
}
