<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\DefaultAdminSeeder;
use Database\Seeders\InternalStaffSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_default_admin_seeder_credentials_authenticate(): void
    {
        $this->seed(DefaultAdminSeeder::class);

        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'Admin@Jarvis2026!',
        ]);

        $this->assertAuthenticated();
        $admin = auth()->user();
        $this->assertEquals('admin', $admin->username);
        $this->assertEquals('admin', $admin->role);
    }

    public function test_internal_staff_accounts_authenticate_and_require_selfie_before_billing(): void
    {
        $this->seed(InternalStaffSeeder::class);

        foreach (['staff1', 'staff2'] as $username) {
            auth()->logout();

            $response = $this->post('/login', [
                'username' => $username,
                'password' => '5starnewlook',
                'latitude' => '12.8821489',
                'longitude' => '80.0808760',
                'gps_accuracy_meters' => '15',
            ]);

            $this->assertAuthenticated();
            $response->assertRedirect(route('staff.selfie.create', absolute: false));

            $this->get(route('staff.billing.create'))
                ->assertRedirect(route('staff.selfie.create'));
        }
    }

    public function test_staff1_and_staff2_keep_the_fixed_hashed_password_and_cannot_change_it(): void
    {
        $this->seed(InternalStaffSeeder::class);

        foreach (['staff1', 'staff2'] as $username) {
            $staff = User::query()->where('username', $username)->firstOrFail();
            self::assertTrue(Hash::check('5starnewlook', $staff->password));
            self::assertFalse($staff->must_change_password);

            auth()->logout();
            $response = $this->post('/login', [
                'username' => $username,
                'password' => '5starnewlook',
                'latitude' => '12.8821489',
                'longitude' => '80.0808760',
                'gps_accuracy_meters' => '15',
            ]);

            $this->assertAuthenticatedAs($staff);
            $response->assertDontSee('5starnewlook');

            $this->put(route('password.force.update'), [
                'current_password' => '5starnewlook',
                'password' => 'AnotherPassword2026!',
                'password_confirmation' => 'AnotherPassword2026!',
            ])->assertForbidden();
            $this->put(route('password.update'), [
                'current_password' => '5starnewlook',
                'password' => 'AnotherPassword2026!',
                'password_confirmation' => 'AnotherPassword2026!',
            ])->assertForbidden();

            $staff->refresh();
            self::assertTrue(Hash::check('5starnewlook', $staff->password));
        }
    }

    public function test_administrator_cannot_reset_fixed_staff_password_but_can_reset_other_staff(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'must_change_password' => false]);
        $this->seed(InternalStaffSeeder::class);
        $fixedStaff = User::query()->where('username', 'staff1')->firstOrFail();
        $otherStaff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);

        $this->actingAs($admin)
            ->put(route('admin.staff.update-password', $fixedStaff), [
                'password' => 'AnotherPassword2026!',
                'password_confirmation' => 'AnotherPassword2026!',
            ])
            ->assertForbidden();

        $this->put(route('admin.staff.update-password', $otherStaff), [
            'password' => 'AnotherPassword2026!',
            'password_confirmation' => 'AnotherPassword2026!',
        ])->assertRedirect(route('admin.staff.index'));
    }

    public function test_fixed_password_migration_updates_only_staff1_and_staff2_credentials(): void
    {
        $staff1 = User::factory()->create([
            'username' => 'staff1', 'role' => 'staff', 'password' => Hash::make('legacy-password'), 'must_change_password' => true,
        ]);
        $staff2 = User::factory()->create([
            'username' => 'staff2', 'role' => 'staff', 'password' => Hash::make('legacy-password'), 'must_change_password' => true,
        ]);
        $otherStaff = User::factory()->create([
            'role' => 'staff', 'password' => Hash::make('other-password'), 'must_change_password' => true,
        ]);

        $migration = require database_path('migrations/2026_11_14_000001_set_staff1_staff2_fixed_password.php');
        $migration->up();

        self::assertTrue(Hash::check('5starnewlook', $staff1->fresh()->password));
        self::assertTrue(Hash::check('5starnewlook', $staff2->fresh()->password));
        self::assertFalse($staff1->fresh()->must_change_password);
        self::assertFalse($staff2->fresh()->must_change_password);
        self::assertTrue(Hash::check('other-password', $otherStaff->fresh()->password));
        self::assertTrue($otherStaff->fresh()->must_change_password);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'username' => $user->username,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_inactive_users_can_not_authenticate(): void
    {
        $user = User::factory()->create(['status' => 'inactive']);

        $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_first_login_password_change_is_enforced(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('password.force.edit', absolute: false));
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false));
    }
}
