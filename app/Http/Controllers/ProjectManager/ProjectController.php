<?php

namespace App\Http\Controllers\ProjectManager;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        $projects = Project::query()
            ->where('created_by', Auth::id())
            ->latest()
            ->paginate(10);

        return view(
            'project-manager.projects.index',
            compact('projects')
        );
    }

    public function create(): View
    {
        return view('project-manager.projects.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'start_date' => [
                'nullable',
                'date',
            ],

            'deadline' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'status' => [
                'required',
                'in:draft,active,completed,cancelled',
            ],

            'workflow_type' => [
                'required',
                'in:default,custom',
            ],

            'stages' => [
                'required_if:workflow_type,custom',
                'nullable',
                'array',
                'min:1',
            ],

            'stages.*' => [
                'nullable',
                'string',
                'max:100',
                'distinct',
            ],
        ]);

        DB::transaction(function () use ($validated): void {
            $project = Project::create([
                'created_by' => Auth::id(),
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'deadline' => $validated['deadline'] ?? null,
                'status' => $validated['status'],
            ]);

            $stages = $validated['workflow_type'] === 'default'
                ? [
                    'Perencanaan',
                    'Development',
                    'Testing',
                    'Deployment',
                ]
                : collect($validated['stages'] ?? [])
                    ->map(fn ($stage) => trim($stage))
                    ->filter()
                    ->values()
                    ->all();

            foreach ($stages as $index => $stage) {
                $project->stages()->create([
                    'name' => $stage,
                    'position' => $index + 1,
                ]);
            }
        });

        return redirect()
            ->route('project-manager.projects.index')
            ->with(
                'success',
                'Project berhasil dibuat.'
            );
    }

    public function show(Project $project): View
    {
        $this->authorizeProject($project);

        $project->load([
            'stages',
            'members',
            'currentStage',
        ]);

        return view(
            'project-manager.projects.show',
            compact('project')
        );
    }

    public function edit(Project $project): View
    {
        $this->authorizeProject($project);

        return view(
            'project-manager.projects.edit',
            compact('project')
        );
    }

    public function update(
        Request $request,
        Project $project
    ): RedirectResponse {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'start_date' => [
                'nullable',
                'date',
            ],

            'deadline' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'status' => [
                'required',
                'in:draft,active,completed,cancelled',
            ],
        ]);

        $project->update($validated);

        return redirect()
            ->route(
                'project-manager.projects.show',
                $project
            )
            ->with(
                'success',
                'Project berhasil diperbarui.'
            );
    }

    public function destroy(
        Project $project
    ): RedirectResponse {
        $this->authorizeProject($project);

        $project->delete();

        return redirect()
            ->route('project-manager.projects.index')
            ->with(
                'success',
                'Project berhasil dihapus.'
            );
    }

    private function authorizeProject(
        Project $project
    ): void {
        abort_unless(
            (int) $project->created_by === (int) Auth::id(),
            403
        );
    }
}
