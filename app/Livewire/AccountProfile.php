<?php

namespace App\Livewire;

use App\Models\ConsoleAudit;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * "My Account" — the signed-in user's own profile and password (PLAN A6).
 *
 * Available to every user, not just administrators: before this existed a
 * non-admin had no route to their own settings at all, which also made the
 * "require 2FA" setting unusable for them (enforcement redirected to a screen
 * nothing linked to).
 *
 * Scope is deliberately narrow — display name, email, password. Anything that
 * grants privilege (admin flag, group membership, enable/disable) stays in the
 * admin-only Users screen so a user can never widen their own access here.
 */
class AccountProfile extends Component
{
    public string $name = '';

    public string $email = '';

    public string $currentPassword = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public bool $profileSaved = false;

    public bool $passwordSaved = false;

    public function mount(): void
    {
        $user = $this->user();

        $this->name = (string) ($user->name ?? '');
        $this->email = (string) ($user->email ?? '');
    }

    public function saveProfile(): void
    {
        $user = $this->user();

        $validated = $this->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ], [
            'name.max' => __('auth.validation.max', ['attribute' => __('auth.validation.attributes.name'), 'max' => 255]),
            'email.email' => __('auth.validation.email'),
            'email.max' => __('auth.validation.max', ['attribute' => __('auth.validation.attributes.email'), 'max' => 255]),
            'email.unique' => __('auth.validation.unique_email'),
        ]);

        $user->forceFill([
            'name' => $validated['name'] !== '' ? $validated['name'] : null,
            'email' => $validated['email'] !== '' ? $validated['email'] : null,
        ])->save();

        ConsoleAudit::record('account.update', 'Updated own profile', 'user', (string) $user->id);

        $this->profileSaved = true;
    }

    public function updatePassword(): void
    {
        $user = $this->user();

        // Provisioned SSO accounts have no usable password to change, and the
        // form isn't rendered for them — guard the action too, since a Livewire
        // call doesn't have to come from the rendered UI.
        if ($user->isSsoProvisioned()) {
            $this->addError('currentPassword', __('auth.login.sso_account'));

            return;
        }

        $this->validate([
            'currentPassword' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'same:passwordConfirmation'],
            'passwordConfirmation' => ['required', 'string'],
        ], [
            'currentPassword.required' => __('auth.validation.required', ['attribute' => __('auth.validation.attributes.current_password')]),
            'password.required' => __('auth.validation.required', ['attribute' => __('auth.validation.attributes.new_password')]),
            'password.min' => __('auth.validation.min', ['attribute' => __('auth.validation.attributes.new_password'), 'min' => 8]),
            'password.same' => __('auth.account.passwords_mismatch'),
            'passwordConfirmation.required' => __('auth.account.confirm_required'),
        ]);

        // Proving knowledge of the current password is what stops a hijacked
        // session from locking the real owner out.
        if (! Hash::check($this->currentPassword, $user->password)) {
            $this->addError('currentPassword', __('auth.account.current_password_incorrect'));

            return;
        }

        $user->forceFill(['password' => $this->password])->save();

        // A password change is the standard "someone else may have had access"
        // response, so every browser previously trusted to skip the emailed
        // sign-in code (PLAN D1) has to earn that trust again.
        $user->trustedDevices()->delete();

        // Rotate the session id after a credential change, and keep this
        // session signed in as the same user.
        Auth::setUser($user);

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        ConsoleAudit::record('account.password', 'Changed own password', 'user', (string) $user->id);

        $this->reset('currentPassword', 'password', 'passwordConfirmation');
        $this->passwordSaved = true;
    }

    private function user(): User
    {
        return Auth::user();
    }

    public function render()
    {
        return view('livewire.account-profile', [
            'user' => $this->user(),
        ]);
    }
}
