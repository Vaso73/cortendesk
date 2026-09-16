<form method="POST" action="{{ route('locale.update') }}" class="d-flex align-items-center gap-2">
    @csrf
    <input type="hidden" name="redirect" value="{{ request()->getRequestUri() }}">
    <label for="locale-{{ $localePickerId ?? 'default' }}" class="visually-hidden">{{ __('ui.language') }}</label>
    <select id="locale-{{ $localePickerId ?? 'default' }}" name="locale" class="form-select form-select-sm" aria-label="{{ __('ui.language') }}" onchange="this.form.submit()">
        @foreach (config('locales.supported') as $code => $metadata)
            <option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $metadata['native_name'] }}{{ ($metadata['community'] ?? false) ? ' — '.__('ui.locale.community_translation') : '' }}</option>
        @endforeach
    </select>
    <noscript><button type="submit" class="btn btn-sm btn-primary">{{ __('ui.language') }}</button></noscript>
</form>
