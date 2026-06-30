@php
    $size = $size ?? 'h-10 w-10';
    $textSize = $textSize ?? 'text-sm';
    $bg = $bg ?? 'bg-blue-100';
    $text = $text ?? 'text-blue-700';
    $initials = $user
        ? strtoupper(substr($user->firstname ?? '', 0, 1) . substr($user->lastname ?? '', 0, 1))
        : '?';
@endphp

@if($user?->profile_picture_path)
    <img src="{{ route('profile.picture', $user) }}?v={{ $user->updated_at?->timestamp }}"
         alt="{{ $user->name }}"
         class="{{ $size }} shrink-0 rounded-full object-cover border border-gray-200">
@else
    <div class="{{ $size }} {{ $bg }} {{ $text }} {{ $textSize }} shrink-0 rounded-full flex items-center justify-center font-semibold">
        {{ $initials ?: '?' }}
    </div>
@endif
