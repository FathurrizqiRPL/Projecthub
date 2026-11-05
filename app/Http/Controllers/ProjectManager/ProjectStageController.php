<?php

namespace App\Http\Controllers\ProjectManager;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProjectStageController extends Controller
{
    public function addMaintenance(Project $project): RedirectResponse
    {
        $this->authorizeProject($project);

        DB::transaction(function () use ($project): void {
            $project = Project::query()
                ->lockForUpdate()
                ->with('currentStage')
                ->findOrFail($project->id);

            $this->authorizeProject($project);

            abort_unless(
                $project->status === 'active' && $project->currentStage,
                422,
                'Maintenance hanya dapat ditambahkan pada project aktif yang sudah dimulai.'
            );

            $hasNextStage = $project->stages()
                ->where('position', '>', $project->currentStage->position)
                ->exists();

            abort_if(
                $hasNextStage,
                422,
                'Tahap Maintenance hanya dapat ditambahkan setelah tahap terakhir workflow.'
            );

            $hasMaintenanceStage = $project->stages()
                ->whereRaw('LOWER(name) = ?', ['maintenance'])
                ->exists();

            abort_if(
                $hasMaintenanceStage,
                422,
                'Tahap Maintenance sudah tersedia pada workflow.'
            );

            $lastPosition = $project->stages()->max('position') ?? 0;

            $project->stages()->create([
                'name' => 'Maintenance',
                'position' => $lastPosition + 1,
            ]);
        });

        return redirect()
            ->route('project-manager.projects.show', $project)
            ->with('success', 'Tahap Maintenance ditambahkan. Buat task maintenance sebelum melanjutkan workflow.');
    }

    public function edit(Project $project): View
    {
        $this->authorizeProject($project);

        $project->load('stages');

        return view(
            'project-manager.projects.workflow',
            compact('project')
        );
    }

    public function update(
        Request $request,
        Project $project
    ): RedirectResponse {
        $this->authorizeProject($project);

        abort_if(
            $project->status === 'completed',
            422,
            'Workflow project yang sudah selesai tidak dapat diedit.'
        );

        $validated = $request->validate([
            'stages' => [
                'required',
                'array',
                'min:1',
            ],
            'stages.*.id' => [
                'nullable',
                'integer',
            ],
            'stages.*.name' => [
                'required',
                'string',
                'max:100',
            ],
        ]);

        DB::transaction(function () use ($project, $validated): void {
            $submittedIds = collect($validated['stages'])
                ->pluck('id')
                ->filter()
                ->map(fn ($id) => (int) $id);

            $existingStages = $project
                ->stages()
                ->get();

            if ($project->status === 'active' || $project->current_stage_id !== null) {
                $existingStageIds = $existingStages
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values();
                $submittedExistingStageIds = $submittedIds
                    ->filter(fn ($id) => $existingStageIds->contains($id))
                    ->values();

                abort_unless(
                    $submittedExistingStageIds->all() === $existingStageIds->all(),
                    422,
                    'Stage yang sudah ada tidak dapat dihapus dan urutannya tidak dapat diedit saat project aktif.'
                );
            }

            foreach ($existingStages as $existingStage) {
                if (! $submittedIds->contains($existingStage->id)) {
                    if (
                        (int) $project->current_stage_id ===
                        (int) $existingStage->id
                    ) {
                        abort(
                            422,
                            'Stage yang sedang aktif tidak dapat dihapus.'
                        );
                    }

                    abort_if(
                        $existingStage->tasks()->exists(),
                        422,
                        'Stage yang sudah memiliki task tidak dapat dihapus.'
                    );

                    $existingStage->delete();
                }
            }

            foreach ($validated['stages'] as $index => $stageData) {
                $stageId = $stageData['id'] ?? null;

                if ($stageId) {
                    $stage = $project
                        ->stages()
                        ->whereKey($stageId)
                        ->firstOrFail();

                    $stage->update([
                        'name' => trim($stageData['name']),
                        'position' => $index + 1,
                    ]);

                    continue;
                }

                $project->stages()->create([
                    'name' => trim($stageData['name']),
                    'position' => $index + 1,
                ]);
            }
        });

        return redirect()
            ->route(
                'project-manager.projects.show',
                $project
            )
            ->with(
                'success',
                'Workflow project berhasil diperbarui.'
            );
    }

    private function authorizeProject(Project $project): void
    {
        abort_unless(
            (int) $project->created_by === (int) Auth::id(),
            403
        );
    }
}
