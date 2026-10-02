<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalUsers = User::count();

        $totalProjectManagers = User::where('role', 'project_manager')->count();

        $totalEmployees = User::where('role', 'employee')->count();

        $totalActiveUsers = User::where('status', 'active')->count();

        $recentUsers = User::latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', [
            'totalUsers' => $totalUsers,
            'totalProjectManagers' => $totalProjectManagers,
            'totalEmployees' => $totalEmployees,
            'totalActiveUsers' => $totalActiveUsers,
            'recentUsers' => $recentUsers,
        ]);
    }
}
