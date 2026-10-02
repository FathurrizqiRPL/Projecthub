<?php

namespace App\Http\Controllers\ProjectManager;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectMemberController extends Controller
{
    public function edit(Project $project): View
    {
        $this->authorizeProject($project);

        $employees = User::query()
            ->where('role', 'employee')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $selectedMembers = $project
            ->members()
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return view(
            'project-manager.projects.members',
            compact(
                'project',
                'employees',
                'selectedMembers'
            )
        );
    }

    public function update(
        Request $request,
        Project $project
    ): RedirectResponse {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'members' => [
                'nullable',
                'array',
            ],

            'members.*' => [
                'integer',
                'distinct',

                Rule::exists('users', 'id')
                    ->where(
                        fn ($query) => $query
                            ->where('role', 'employee')
                            ->where('status', 'active')
                    ),
            ],
        ]);

        $project->members()->sync(
            $validated['members'] ?? []
        );

        return redirect()
            ->route(
                'project-manager.projects.show',
                $project
            )
            ->with(
                'success',
                'Anggota project berhasil diperbarui.'
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
