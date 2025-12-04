<?php

use App\Models\ActivityLog;
use App\Models\Store;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('redirects non-admin users from admin dashboard', function () {
    $user = User::factory()->create(['is_admin' => false]);

    actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

it('allows admin users to access admin dashboard', function () {
    $user = User::factory()->create(['is_admin' => true]);

    actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertViewIs('admin.dashboard')
        ->assertViewHas(['stats', 'recentActivity']);
});

it('displays correct statistics on admin dashboard', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->count(5)->create();
    Store::factory()->count(3)->create(['is_active' => true]);
    Store::factory()->count(2)->create(['is_active' => false]);

    $response = actingAs($admin)->get(route('admin.dashboard'));

    $stats = $response->viewData('stats');
    expect($stats['total_users'])->toBe(6) // 5 + admin
        ->and($stats['total_stores'])->toBe(5)
        ->and($stats['active_stores'])->toBe(3);
});

it('displays recent activity on admin dashboard', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $store = Store::factory()->create();

    ActivityLog::factory()->count(15)->create([
        'store_id' => $store->id,
        'created_at' => now(),
    ]);

    $response = actingAs($admin)->get(route('admin.dashboard'));

    $recentActivity = $response->viewData('recentActivity');
    expect($recentActivity)->toHaveCount(10); // Limited to 10
});

it('redirects non-admin users from activity logs', function () {
    $user = User::factory()->create(['is_admin' => false]);

    actingAs($user)
        ->get(route('admin.activity-logs'))
        ->assertForbidden();
});

it('allows admin users to view activity logs', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    actingAs($admin)
        ->get(route('admin.activity-logs'))
        ->assertSuccessful()
        ->assertViewIs('admin.activity-logs');
});

it('filters activity logs by level', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    ActivityLog::factory()->create(['log_level' => 'error']);
    ActivityLog::factory()->create(['log_level' => 'info']);

    actingAs($admin)
        ->get(route('admin.activity-logs', ['level' => 'error']))
        ->assertSuccessful()
        ->assertSee('ERROR');
});

it('filters activity logs by search term', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    ActivityLog::factory()->create([
        'event' => 'user.login',
        'description' => 'User logged in successfully',
    ]);

    ActivityLog::factory()->create([
        'event' => 'order.created',
        'description' => 'New order created',
    ]);

    actingAs($admin)
        ->get(route('admin.activity-logs', ['search' => 'login']))
        ->assertSuccessful()
        ->assertSee('user.login')
        ->assertDontSee('order.created');
});


