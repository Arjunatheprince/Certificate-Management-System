<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class SessionRoleManipulationTest extends TestCase
{
    public function test_admin_route_rejects_manual_session_role_escalation(): void
    {
        $user = new User([
            'name' => 'Viewer User',
            'email' => 'viewer@example.com',
            'password' => bcrypt('secret'),
            'role' => 'viewer',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['role' => 'superadmin'])
            ->get(route('admin.users.index'));

        $response->assertStatus(403);
    }

    public function test_superadmin_route_uses_authenticated_user_role_not_session_role(): void
    {
        $user = new User([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['role' => 'superadmin'])
            ->get(route('admin.activity-logs'));

        $response->assertStatus(403);
    }
}
