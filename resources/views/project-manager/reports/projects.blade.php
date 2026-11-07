<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Proyek - {{ $periodLabel }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 34px 36px 48px;
        }

        body {
            color: #1e293b;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10px;
        }

        .report-header {
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dc2626;
        }

        .brand {
            margin: 0 0 4px;
            color: #b91c1c;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        h1 {
            margin: 0;
            font-size: 16px;
        }

        .intro {
            margin: 4px 0 0;
            color: #475569;
            font-size: 9px;
        }

        .summary {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: separate;
            border-spacing: 4px 0;
        }

        .summary td {
            width: 25%;
            padding: 7px 8px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
        }

        .summary-label {
            display: block;
            color: #64748b;
            font-size: 8px;
            text-transform: uppercase;
        }

        .summary-value {
            display: block;
            margin-top: 2px;
            color: #0f172a;
            font-size: 14px;
            font-weight: bold;
        }

        .projects {
            width: 100%;
            border-collapse: collapse;
        }

        .projects th,
        .projects td {
            padding: 6px 6px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
        }

        .projects th {
            background: #e2e8f0;
            color: #0f172a;
            font-size: 8px;
            text-align: left;
        }

        .projects td {
            font-size: 9px;
        }

        .projects tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .text-center {
            text-align: center;
        }

        .project-name {
            font-weight: bold;
        }

        .empty {
            padding: 20px;
            color: #64748b;
            text-align: center;
        }

        .page-footer {
            position: fixed;
            right: 0;
            bottom: -31px;
            left: 0;
            padding-top: 5px;
            border-top: 1px solid #cbd5e1;
            color: #64748b;
            font-size: 8px;
        }

        .page-number {
            float: right;
        }
    </style>
</head>
<body>
    <footer class="page-footer">
        Dibuat pada {{ $generatedAt->locale('id')->translatedFormat('d F Y, H:i') }}
        <span class="page-number">Halaman {PAGE_NUM} dari {PAGE_COUNT}</span>
    </footer>

    <main>
        <header class="report-header">
            <p class="brand">ProjectHub</p>
            <h1>Laporan Proyek</h1>
            <p class="intro">
                Periode {{ $periodLabel }}. Laporan ini merangkum jumlah, status, progres,
                tahapan workflow, dan penyelesaian tugas proyek saat laporan dibuat.
            </p>
        </header>

        <table class="summary">
            <tr>
                <td>
                    <span class="summary-label">Proyek pada periode ini</span>
                    <span class="summary-value">{{ $reportProjectCount }}</span>
                </td>
                <td>
                    <span class="summary-label">Total seluruh proyek dibuat</span>
                    <span class="summary-value">{{ $totalProjects }}</span>
                </td>
                <td>
                    <span class="summary-label">Proyek berjalan</span>
                    <span class="summary-value">{{ $statusCounts['active'] ?? 0 }}</span>
                </td>
                <td>
                    <span class="summary-label">Proyek selesai</span>
                    <span class="summary-value">{{ $statusCounts['completed'] ?? 0 }}</span>
                </td>
            </tr>
        </table>

        <table class="projects">
            <thead>
                <tr>
                    <th style="width: 30%;">Proyek / dibuat</th>
                    <th style="width: 14%;">Status</th>
                    <th style="width: 22%;">Tahap workflow</th>
                    <th class="text-center" style="width: 12%;">Progress</th>
                    <th class="text-center" style="width: 22%;">Tugas<br>(total / belum selesai / batal)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($projects as $reportProject)
                    @php
                        $project = $reportProject['project'];
                        $statusLabel = $project->isOverdue() ? 'Lewat tenggat' : match ($project->status) {
                            'draft' => 'Draf',
                            'active' => 'Berjalan',
                            'completed' => 'Selesai',
                            'cancelled' => 'Dibatalkan',
                            default => ucfirst($project->status),
                        };
                        $currentStage = $project->currentStage?->name
                            ?? ($project->status === 'completed' ? 'Workflow selesai' : 'Belum dimulai');
                    @endphp
                    <tr>
                        <td>
                            <span class="project-name">{{ $project->name }}</span>
                            <br>
                            <span class="project-date">
                                {{ $project->created_at->locale('id')->translatedFormat('d M Y') }}
                            </span>
                        </td>
                        <td>{{ $statusLabel }}</td>
                        <td>{{ $currentStage }}</td>
                        <td class="text-center">{{ $reportProject['progress'] }}%</td>
                        <td class="text-center">
                            {{ $project->tasks_count }}
                            /
                            {{ $project->unfinished_tasks_count }}
                            /
                            {{ $project->cancelled_tasks_count }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty">Tidak ada proyek yang dibuat pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </main>
</body>
</html>
