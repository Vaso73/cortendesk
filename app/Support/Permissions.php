<?php

namespace App\Support;

/**
 * The single permission vocabulary shared by API tokens (PLAN B1) and
 * delegated console roles (PLAN D4).
 *
 * Levels are stored as `none|r|rw` — the same three strings the api_tokens
 * matrix has always used — and are ordered by increasing capability, so a
 * required level is satisfied by anything ranked at least as high. The console
 * UI *labels* them None / View / Manage; the strings 'view' and 'manage' are
 * never stored.
 *
 * Console roles cover two areas the automation API has no endpoints for
 * (`setting`, `token`), which is why CONSOLE_RESOURCES is a superset of
 * ApiToken::RESOURCES rather than the same list. Widening the API matrix would
 * invent permissions no route reads.
 */
final class Permissions
{
    /** Permission levels, ordered by increasing capability. */
    public const LEVELS = ['none', 'r', 'rw'];

    /** Console areas a role can grant. Superset of ApiToken::RESOURCES. */
    public const CONSOLE_RESOURCES = [
        'device', 'user', 'group', 'address_book', 'audit', 'strategy', 'setting', 'token',
    ];

    /** Localized labels for stable permission resource identifiers. */
    public static function resourceLabels(): array
    {
        return collect(self::CONSOLE_RESOURCES)
            ->mapWithKeys(fn (string $resource) => [$resource => __("identity.permissions.resources.{$resource}")])
            ->all();
    }

    /** Localized explanations of what each permission unlocks. */
    public static function resourceHints(): array
    {
        return collect(self::CONSOLE_RESOURCES)
            ->mapWithKeys(fn (string $resource) => [$resource => __("identity.permissions.hints.{$resource}")])
            ->all();
    }

    /** Localized labels for stable permission level identifiers. */
    public static function levelLabels(): array
    {
        return collect(self::LEVELS)
            ->mapWithKeys(fn (string $level) => [$level => __("identity.permissions.levels.{$level}")])
            ->all();
    }

    /**
     * The capability a user with no role has always had, and still has: full
     * self-service over the devices and address books already visible to them,
     * read access to the operational logs, and nothing else. `users.role_id IS
     * NULL` resolves here, which is what makes D4 a no-op for existing installs.
     */
    public const LEGACY_USER = [
        'device' => 'rw',
        'user' => 'none',
        'group' => 'none',
        'address_book' => 'rw',
        'audit' => 'r',
        'strategy' => 'none',
        'setting' => 'none',
        'token' => 'none',
    ];

    public static function rank(string $level): int
    {
        return match ($level) {
            'rw' => 2,
            'r' => 1,
            default => 0,
        };
    }

    /** Does `$have` cover `$required`? rw covers r and rw; r covers only r. */
    public static function satisfies(string $have, string $required): bool
    {
        return self::rank($have) >= self::rank($required);
    }

    /**
     * Fill in every known resource (missing => 'none') and drop unknown keys,
     * so a matrix written before a resource existed fails closed rather than
     * inheriting whatever the caller happened to post.
     *
     * @param  array<string,mixed>  $matrix
     * @param  array<int,string>  $resources
     * @return array<string,string>
     */
    public static function normalize(array $matrix, array $resources): array
    {
        $out = [];
        foreach ($resources as $resource) {
            $level = $matrix[$resource] ?? 'none';
            $out[$resource] = in_array($level, self::LEVELS, true) ? $level : 'none';
        }

        return $out;
    }
}
