<?php

namespace App\Http\Controllers\ProjectManager;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectCompletionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectProgressController extends Controller
{
    public function start(Project $project): RedirectResponse
    {
        $this->authorizeProject($project);

        abort_unless(
            $project->status === 'active',
            422,
            'Project harus berstatus aktif.'
        );

        abort_if(
            $project->current_stage_id !== null,
            422,
            'Project sudah memiliki stage aktif.'
        );

        $firstStage = $project->stages()
            ->orderBy('position')
            ->first();

        abort_if(
            ! $firstStage,
            422,
            'Project belum memiliki workflow.'
        );

        $project->update([
            'current_stage_id' => $firstStage->id,
        ]);

        return back()->with(
            'success',
            "Project berhasil dimulai pada tahap {$firstStage->name}."
        );
    }

    public function next(Project $project): RedirectResponse
    {
        $this->authorizeProject($project);

        $successMessage = DB::transaction(function () use ($project): string {
            $project = Project::query()
                ->lockForUpdate()
                ->with('currentStage')
                ->findOrFail($project->id);

            $this->authorizeProject($project);

            abort_unless(
                $project->status === 'active',
                422,
                'Project harus berstatus aktif.'
            );

            abort_if(
                ! $project->currentStage,
                422,
                'Project belum memiliki stage aktif.'
            );

            $currentStage = $project->currentStage;
            $currentStageTasks = $project->tasks()
                ->where('project_stage_id', $currentStage->id);
            $currentStageTaskCount = (clone $currentStageTasks)->count();
            $incompleteCurrentStageTaskCount = (clone $currentStageTasks)
                ->where('status', '!=', 'completed')
                ->count();

            abort_if(
                $currentStageTaskCount === 0,
                422,
                'Stage ini harus memiliki minimal satu task sebelum dapat diselesaikan.'
            );

            abort_if(
                $incompleteCurrentStageTaskCount > 0,
                422,
                "Selesaikan semua task pada stage {$currentStage->name} sebelum melanjutkan."
            );

            $nextStage = $project->stages()
                ->where('position', '>', $currentStage->position)
                ->orderBy('position')
                ->first();

            if ($nextStage) {
                $currentStage->update([
                    'completed_at' => now(),
                ]);

                $project->update([
                    'current_stage_id' => $nextStage->id,
                ]);

                return "Tahap {$currentStage->name} selesai. Project sekarang masuk ke tahap {$nextStage->name}.";
            }

            abort(422, 'Gunakan aksi Selesaikan Project untuk menutup tahap terakhir.');
        });

        return back()->with('success', $successMessage);
    }

    public function complete(Request $request, Project $project): RedirectResponse
    {
        $successMessage = DB::transaction(function () use ($project, $request): string {
            $project = Project::query()
                ->lockForUpdate()
                ->with('currentStage')
                ->findOrFail($project->id);

            $this->authorizeProject($project);

            abort_unless(
                $project->status === 'active' && $project->currentStage,
                422,
                'Project aktif dengan stage berjalan diperlukan untuk menyelesaikan project.'
            );

            $currentStage = $project->currentStage;
            $hasNextStage = $project->stages()
                ->where('position', '>', $currentStage->position)
                ->exists();

            abort_if(
                $hasNextStage,
                422,
                'Selesaikan stage berikutnya sebelum menutup project.'
            );

            $projectTasks = $project->tasks()
                ->with('stage')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            abort_if(
                $projectTasks->isEmpty(),
                422,
                'Project harus memiliki minimal satu task sebelum dapat diselesaikan.'
            );

            $incompleteTasks = $projectTasks
                ->where('status', '!=', 'completed')
                ->sortBy('project_stage_id')
                ->values();

            if ($incompleteTasks->isNotEmpty()) {
                $submittedIncompleteTaskIds = collect($request->input('incomplete_task_ids', []))
                    ->map(fn ($taskId) => (int) $taskId)
                    ->sort()
                    ->values();
                $currentIncompleteTaskIds = $incompleteTasks
                    ->pluck('id')
                    ->map(fn ($taskId) => (int) $taskId)
                    ->sort()
                    ->values();

                abort_unless(
                    $submittedIncompleteTaskIds->all() === $currentIncompleteTaskIds->all(),
                    409,
                    'Daftar task berubah sejak modal dibuka. Muat ulang halaman lalu tinjau kembali.'
                );

                $request->merge([
                    'completion_reason' => trim((string) $request->input('completion_reason')),
                ]);

                $validated = $request->validate([
                    'confirm_incomplete_tasks' => ['required', 'accepted'],
                    'completion_reason' => ['required', 'string', 'max:1000'],
                ], [
                    'confirm_incomplete_tasks.required' => 'Konfirmasikan pengecualian sebelum menutup project.',
                    'completion_reason.required' => 'Alasan pengecualian wajib diisi.',
                ]);

                $reason = $validated['completion_reason'];

                foreach ($incompleteTasks as $task) {
                    ProjectCompletionException::query()->create([
                        'project_id' => $project->id,
                        'task_id' => $task->id,
                        'waived_by' => Auth::id(),
                        'stage_name' => $task->stage?->name ?? 'Stage tidak tersedia',
                        'task_title' => $task->title,
                        'task_status' => $task->status,
                        'reason' => $reason,
                    ]);
                }
            }

            $currentStage->update([
                'completed_at' => now(),
            ]);
            $project->update([
                'status' => 'completed',
            ]);

            return $incompleteTasks->isEmpty()
                ? 'Semua task selesai. Project berhasil diselesaikan.'
                : 'Project diselesaikan. Task yang dikecualikan tercatat pada detail project.';
        });

        return back()->with('success', $successMessage);
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
