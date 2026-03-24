@props(['style' => session('flash.bannerStyle', 'success'), 'message' => session('flash.banner')])

<div
    x-data="{{ json_encode(['show' => true, 'style' => $style, 'message' => $message]) }}"
    x-cloak
    x-show="show && message"
    x-on:banner-message.window="
        style = event.detail.style;
        message = event.detail.message;
        show = true;
    "
    class="w-100">
    <div
        class="alert mb-0 rounded-0"
        role="alert"
        :class="{
            'alert-success': style === 'success',
            'alert-danger':  style === 'danger',
            'alert-warning': style === 'warning',
            'alert-secondary': style !== 'success' && style !== 'danger' && style !== 'warning'
        }">
        <div class="container py-1 d-flex align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                <i x-show="style === 'success'" class="fas fa-check-circle"></i>
                <i x-show="style === 'danger'"  class="fas fa-exclamation-triangle"></i>
                <i x-show="style === 'warning'" class="fas fa-exclamation-circle"></i>
                <i x-show="style !== 'success' && style !== 'danger' && style !== 'warning'" class="fas fa-info-circle"></i>
                <p class="mb-0 text-truncate" x-text="message"></p>
            </div>
            <button type="button" class="btn-close" aria-label="{{ __('Dismiss') }}" @click="show = false"></button>
        </div>
    </div>
</div>
