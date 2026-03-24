@props(['id', 'maxWidth'])

@php
$id = $id ?? md5($attributes->wire('model'));

$maxWidth = [
    'sm' => 'modal-sm',
    'md' => '',
    'lg' => 'modal-lg',
    'xl' => 'modal-xl',
    '2xl' => 'modal-xl',
][$maxWidth ?? '2xl'];
@endphp

<div
    x-data="{ show: @entangle($attributes->wire('model')) }"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    id="{{ $id }}"
    class="modal fade"
    :class="{ 'show d-block': show }"
    style="display: none;"
    tabindex="-1"
    role="dialog"
>
    <div class="modal-dialog {{ $maxWidth }} modal-dialog-centered" x-trap.inert.noscroll="show">
        <div class="modal-content">
            {{ $slot }}
        </div>
    </div>

    <div x-show="show" class="modal-backdrop fade show" x-on:click="show = false"></div>
</div>

