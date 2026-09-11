<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_bare_root_redirects_to_default_locale(): void
    {
        $this->get('/')
            ->assertRedirect('/en');
    }

    public function test_bare_root_redirects_to_cookie_remembered_locale(): void
    {
        $this->withCookie('app_locale', 'id')
            ->get('/')
            ->assertRedirect('/id');
    }

    public function test_unprefixed_legacy_url_redirects_to_prefixed_equivalent(): void
    {
        $this->get('/login')
            ->assertRedirect('/en/login');
    }

    public function test_unsupported_locale_segment_404s_immediately(): void
    {
        $this->get('/fr/login')->assertNotFound();
    }

    public function test_genuinely_missing_prefixed_page_404s(): void
    {
        $this->get('/en/this-page-does-not-exist')->assertNotFound();
    }

    public function test_visiting_a_locale_prefixed_page_shares_that_locale_and_its_translations(): void
    {
        $this->get('/id/login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('locale', 'id')
                ->where('translations.auth_ui.login_title', 'Masuk ke akun Anda'));

        $this->get('/en/login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('locale', 'en')
                ->where('translations.auth_ui.login_title', 'Log in to your account'));
    }

    public function test_visiting_a_locale_prefixed_page_remembers_it_via_cookie(): void
    {
        $this->get('/id/login')->assertCookie('app_locale', 'id');
    }

    public function test_locale_switch_preserves_the_current_route_and_its_parameters(): void
    {
        // The login page has no extra route parameters, so switching locale
        // should land back on the same named route under the new prefix.
        $this->get('/en/login')
            ->assertInertia(fn ($page) => $page->where('locale', 'en'));

        $this->get('/id/login')
            ->assertInertia(fn ($page) => $page->where('locale', 'id'));
    }

    public function test_identity_and_competition_modules_share_their_translations(): void
    {
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->get('/id/users')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('locale', 'id')
                ->where('translations.identity.index_description', 'Kelola pengguna di organisasi Anda'));

        $this->actingAs($organizer)
            ->get('/en/users')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('locale', 'en')
                ->where('translations.identity.index_description', 'Manage users in your organization'));

        $this->actingAs($organizer)
            ->get('/id/competitions/create')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('locale', 'id')
                ->where('translations.competition.create_title', 'Buat kompetisi'));

        $this->actingAs($organizer)
            ->get('/en/competitions/create')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('locale', 'en')
                ->where('translations.competition.create_title', 'Create competition'));
    }
}
