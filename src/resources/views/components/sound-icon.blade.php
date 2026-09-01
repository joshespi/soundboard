@props(['sound'])
@php($imageUrl = $sound->image_url)
@if ($imageUrl)
    <img src="{{ $imageUrl }}" alt="" {{ $attributes->merge(['class' => 'object-cover']) }}>
@else
    <span {{ $attributes->merge(['class' => 'flex items-center justify-center leading-none']) }}>{{ $sound->emoji ?: '🔊' }}</span>
@endif
