@props(['log'])

{{-- "Updated page Home": one ActivityLog entry in words (history page, dashboards). --}}
<span {{ $attributes }}>
    <span class="font-medium">{{ \Illuminate\Support\Str::headline($log->event) }}</span>
    <span class="text-secondary-foreground">{{ strtolower(\App\Models\ActivityLog::typeLabel($log->subject_type)) }}</span>
    <span class="font-medium">{{ $log->subject_label }}</span>
</span>
