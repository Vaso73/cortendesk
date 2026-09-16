<?php

namespace App\Http\Controllers;

use App\Support\LocaleNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LocaleController extends Controller
{
    public function __invoke(Request $request, LocaleNormalizer $normalizer): RedirectResponse
    {
        $request->validate([
            'locale' => ['required', 'string'],
            'redirect' => ['nullable', 'string'],
        ]);

        $locale = $normalizer->normalize($request->string('locale')->toString());
        if ($locale === null) {
            throw ValidationException::withMessages(['locale' => __('ui.locale.invalid')]);
        }

        $request->session()->put('locale', $locale);
        if ($user = $request->user()) {
            $user->forceFill(['locale' => $locale])->save();
        }

        app()->setLocale($locale);
        $target = $request->string('redirect')->toString();
        if (! str_starts_with($target, '/')
            || str_starts_with($target, '//')
            || str_contains($target, '\\')
            || preg_match('/[\x00-\x1F\x7F]/', $target)
        ) {
            $target = route('login', absolute: false);
        }

        return redirect()->to($target)->with('status', __('ui.locale.updated'));
    }
}
