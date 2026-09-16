<?php

namespace Tests\Feature;

use App\Livewire\AddressBookManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AddressBookLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_address_book_catalogs_have_complete_key_and_placeholder_parity(): void
    {
        $english = require lang_path('en/address_books.php');
        $slovak = require lang_path('sk/address_books.php');

        $flatten = function (array $messages, string $prefix = '') use (&$flatten): array {
            $result = [];
            foreach ($messages as $key => $value) {
                $path = $prefix === '' ? $key : $prefix.'.'.$key;
                if (is_array($value)) {
                    $result += $flatten($value, $path);
                } else {
                    $result[$path] = $value;
                }
            }

            return $result;
        };

        $en = $flatten($english);
        $sk = $flatten($slovak);
        ksort($en);
        ksort($sk);

        $this->assertNotEmpty($en);
        $this->assertSame(array_keys($en), array_keys($sk));

        foreach ($en as $key => $value) {
            $this->assertIsString($value, $key);
            $this->assertNotSame('', trim($value), $key);
            $this->assertNotSame('', trim($sk[$key]), $key);
            preg_match_all('/:[A-Za-z_][A-Za-z0-9_]*/', $value, $enPlaceholders);
            preg_match_all('/:[A-Za-z_][A-Za-z0-9_]*/', $sk[$key], $skPlaceholders);
            $enPlaceholders[0] = array_values(array_unique($enPlaceholders[0]));
            $skPlaceholders[0] = array_values(array_unique($skPlaceholders[0]));
            sort($enPlaceholders[0]);
            sort($skPlaceholders[0]);
            $this->assertSame($enPlaceholders[0], $skPlaceholders[0], $key);
        }
    }

    public function test_every_address_book_translation_reference_exists(): void
    {
        $catalog = require lang_path('en/address_books.php');
        $files = [
            resource_path('views/address-books/index.blade.php'),
            resource_path('views/livewire/address-book-manager.blade.php'),
            app_path('Livewire/AddressBookManager.php'),
        ];

        foreach ($files as $file) {
            $source = file_get_contents($file);
            preg_match_all("/(?:__|trans_choice)\\(['\"]address_books\\.([A-Za-z0-9_.]+)['\"] /x", $source, $matches);
            foreach ($matches[1] as $key) {
                $value = $catalog;
                foreach (explode('.', $key) as $segment) {
                    $this->assertIsArray($value, "$file references missing address_books.$key");
                    $this->assertArrayHasKey($segment, $value, "$file references missing address_books.$key");
                    $value = $value[$segment];
                }
            }
        }
    }

    public function test_scoped_address_book_ui_has_no_unlocalized_visible_english(): void
    {
        $files = [
            resource_path('views/address-books/index.blade.php'),
            resource_path('views/livewire/address-book-manager.blade.php'),
        ];
        $forbidden = [
            'Address Books', 'New shared', 'No entries in this address book',
            'Sharing rules', 'Add sharing rule', 'Choose a user', 'Delete rule',
            'Connect with RustDesk', 'Search device, ID or alias', 'Read only',
            'Remove :id from this address book', 'No tags in this address book yet',
            '>My address book<',
        ];

        foreach ($files as $file) {
            $source = preg_replace('/{{--.*?--}}/s', '', file_get_contents($file));
            foreach ($forbidden as $literal) {
                $this->assertStringNotContainsString($literal, $source, "$file contains visible literal: $literal");
            }
        }
    }

    public function test_slovak_catalog_uses_natural_domain_terms_and_preserves_technical_identifiers(): void
    {
        app()->setLocale('sk');

        $this->assertSame('Adresáre', __('address_books.page.title'));
        $this->assertSame('Záznamy', __('address_books.entries.title'));
        $this->assertSame('Pravidlá zdieľania', __('address_books.rules.title'));
        $this->assertSame('RustDesk ID', __('address_books.fields.rustdesk_id'));
        $this->assertStringContainsString('RustDesk', __('address_books.entries.sync_help'));
        $this->assertStringContainsString(':id', __('address_books.confirm.remove_entry'));
    }

    public function test_validation_messages_and_attribute_names_are_localized_by_the_component(): void
    {
        $source = file_get_contents(app_path('Livewire/AddressBookManager.php'));

        foreach ([
            "__('address_books.validation.name')",
            "__('address_books.validation.note')",
            "__('address_books.validation.tag_name')",
            "__('address_books.validation.color')",
            "__('address_books.validation.alias')",
            "__('address_books.validation.subject')",
            "__('address_books.validation.permission')",
        ] as $translation) {
            $this->assertStringContainsString($translation, $source);
        }

        $this->assertSame(6, substr_count($source, "__('address_books.validation.messages')"));

        app()->setLocale('sk');
        $this->assertSame('Pole „:attribute“ je povinné.', __('address_books.validation.messages.required'));
        $this->assertSame('Hodnota poľa „:attribute“ sa už používa.', __('address_books.validation.messages.unique'));
    }

    public function test_slovak_validation_is_rendered_for_address_book_forms(): void
    {
        $user = User::factory()->admin()->create();
        app()->setLocale('sk');

        Livewire::actingAs($user)
            ->test(AddressBookManager::class)
            ->call('openNewBook')
            ->set('bookName', '')
            ->call('createBook')
            ->assertHasErrors(['bookName' => 'required'])
            ->assertSee('Pole „názov“ je povinné.')
            ->assertSee('Môj adresár')
            ->assertDontSee('My address book');
    }

    public function test_address_book_views_never_render_stored_password_fields(): void
    {
        $source = file_get_contents(resource_path('views/livewire/address-book-manager.blade.php'));

        $this->assertStringNotContainsString('password_enc', $source);
        $this->assertStringNotContainsString('password', strtolower($source));
    }
}
