<?php

namespace App\Http\Controllers\ProjectManager;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
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
            !$firstStage,
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

        abort_unless(
            $project->status === 'active',
            422,
            'Project harus berstatus aktif.'
        );

        $project->load('currentStage');

        abort_if(
            !$project->currentStage,
            422,
            'Project belum memiliki stage aktif.'
        );

        $currentStage = $project->currentStage;

        $nextStage = $project->stages()
            ->where('position', '>', $currentStage->position)
            ->orderBy('position')
            ->first();

        abort_if(
            !$nextStage,
            422,
            'Project sudah berada pada tahap terakhir.'
        );

        DB::transaction(function () use (
            $project,
            $currentStage,
            $nextStage
        ) {
            $currentStage->update([
                'completed_at' => now(),
            ]);

            $project->update([
                'current_stage_id' => $nextStage->id,
            ]);
        });

        return back()->with(
            'success',
            "Tahap {$currentStage->name} selesai. Project sekarang masuk ke tahap {$nextStage->name}."
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
