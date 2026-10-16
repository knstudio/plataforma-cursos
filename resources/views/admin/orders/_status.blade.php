@php
    $badge = match ($status) {
        \App\Enums\OrderStatus::Approved => 'badge-success',
        \App\Enums\OrderStatus::Pending => 'badge-warning',
        default => 'badge-danger',
    };
@endphp
<span class="badge {{ $badge }}">{{ $status->label() }}</span>
