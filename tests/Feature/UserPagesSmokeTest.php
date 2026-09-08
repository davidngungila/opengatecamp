<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'Super Administrator']);
        $user = User::factory()->create(['name' => 'Big Leader', 'role_id' => $role->id]);
        $this->actingAs($user);

        return $user;
    }

    public function test_users_page_renders(): void
    {
        $this->adminUser();

        $resp = $this->get(route('users.index'));

        $resp->assertOk();
        $resp->assertSee('System Users');
        $resp->assertSee('Add User');
    }

    public function test_roles_page_renders(): void
    {
        $this->adminUser();
        $role = Role::firstOrCreate(['name' => 'Super Administrator']);

        $resp = $this->get(route('users.roles'));

        $resp->assertOk();
        $resp->assertSee('Roles &amp; Permissions', false);
        $resp->assertSee('Super Administrator');
        $resp->assertSee('role-details-row');
        $resp->assertSee('roleDetails1');
        $resp->assertSee('Permissions granted');
        $resp->assertSee('Users with this role');
    }

    public function test_roles_table_shows_permissions(): void
    {
        $this->adminUser();
        $role = Role::create(['name' => 'Media Officer', 'permissions' => ['finance.view', 'finance.manage']]);

        $resp = $this->get(route('users.roles'));

        $resp->assertOk();
        $resp->assertSee('Media Officer');
        $resp->assertSee('finance.view');
        $resp->assertSee('finance.manage');
        $resp->assertSee('View Finance');
    }

    public function test_permissions_page_renders(): void
    {
        $this->adminUser();

        $resp = $this->get(route('users.permissions'));

        $resp->assertOk();
        $resp->assertSee('Role Permission Matrix');
        $resp->assertSee('Save Permissions');
    }

    public function test_sidebar_lists_users_roles_permissions(): void
    {
        $this->adminUser();

        $resp = $this->get(route('users.index'));

        $resp->assertSee('/users', false);
        $resp->assertSee('/users/roles', false);
        $resp->assertSee('/users/permissions', false);
    }

    public function test_admin_only_management(): void
    {
        $plain = User::factory()->create(['name' => 'Plain Member']);
        $this->actingAs($plain);

        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('users.roles'))->assertForbidden();
        $this->get(route('users.permissions'))->assertForbidden();
    }
}