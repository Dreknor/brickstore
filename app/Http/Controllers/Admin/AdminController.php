<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Show admin dashboard
     */
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'total_stores' => Store::count(),
            'active_stores' => Store::where('is_active', true)->count(),
            'recent_logs' => ActivityLog::recent(1)->count(),
        ];

        $recentActivity = ActivityLog::with(['user', 'store'])
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentActivity'));
    }

    /**
     * Show activity logs
     */
    public function activityLogs(Request $request)
    {
        $query = ActivityLog::with(['user', 'store'])->latest();

        // Filter by level
        if ($request->filled('level')) {
            $query->where('log_level', $request->level);
        }

        // Filter by event
        if ($request->filled('event')) {
            $query->where('event', 'like', '%'.$request->event.'%');
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by store
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->to_date.' 23:59:59');
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('event', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(50);

        $users = User::all();
        $stores = Store::all();

        return view('admin.activity-logs', compact('logs', 'users', 'stores'));
    }

    /**
     * Clear old activity logs
     */
    public function clearOldLogs(Request $request)
    {
        $days = $request->input('days', 30);

        $deleted = ActivityLog::where('created_at', '<', now()->subDays($days))->delete();

        return redirect()->back()->with('success', "Deleted {$deleted} old log entries");
    }
}
