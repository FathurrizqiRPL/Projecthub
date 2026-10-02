<?php

namespace App\Http\Controllers\ProjectManager;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $projects = Project::query()
            ->where('created_by', Auth::id());

        $totalProjects = (clone $projects)->count();

        $activeProjects = (clone $projects)
            ->where('status', 'active')
            ->count();

        $recentProjects = (clone $projects)
            ->latest()
            ->take(5)
            ->get();

        return view('project-manager.dashboard', compact(
            'totalProjects',
            'activeProjects',
            'recentProjects'
        ));
    }
}
