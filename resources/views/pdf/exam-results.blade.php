<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Results — {{ $exam->title }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; background: #fff; padding: 32px; }

        .header { margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid #0d9488; }
        .header h1 { font-size: 20px; font-weight: 700; color: #0d9488; }
        .header .sub { font-size: 12px; color: #6b7280; margin-top: 4px; }

        .summary { display: flex; gap: 24px; margin-bottom: 24px; }
        .stat { background: #f0fdfa; border: 1px solid #99f6e4; border-radius: 6px; padding: 12px 20px; text-align: center; flex: 1; }
        .stat-number { font-size: 28px; font-weight: 800; color: #0d9488; }
        .stat-label  { font-size: 10px; color: #6b7280; margin-top: 2px; }

        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        thead { background: #f0fdfa; }
        th { padding: 8px 10px; text-align: left; font-weight: 700; color: #374151; border-bottom: 2px solid #0d9488; }
        td { padding: 7px 10px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
        tr:nth-child(even) td { background: #fafafa; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 600; }
        .badge-pass { background: #dcfce7; color: #166534; }
        .badge-warn { background: #fef9c3; color: #854d0e; }
        .badge-fail { background: #fee2e2; color: #991b1b; }

        .score-pass { color: #059669; font-weight: 700; }
        .score-warn { color: #d97706; font-weight: 700; }
        .score-fail { color: #dc2626; font-weight: 700; }

        .footer { margin-top: 32px; padding-top: 12px; border-top: 1px solid #e5e7eb; font-size: 10px; color: #9ca3af; display: flex; justify-content: space-between; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Class Results</h1>
        <div class="sub">
            {{ $exam->title }}
            @if ($exam->description) — {{ $exam->description }} @endif
        </div>
        <div class="sub" style="margin-top:2px;">
            Exported by {{ $teacher->name }} &nbsp;·&nbsp; {{ now()->format('d M Y, H:i') }}
        </div>
    </div>

    {{-- Summary stats --}}
    @php
        $passed   = $attempts->where('score', '>=', 70)->count();
        $avgScore = $attempts->count() > 0 ? round($attempts->avg('score')) : 0;
    @endphp
    <div class="summary">
        <div class="stat">
            <div class="stat-number">{{ $attempts->count() }}</div>
            <div class="stat-label">Submissions</div>
        </div>
        <div class="stat">
            <div class="stat-number">{{ $avgScore }}%</div>
            <div class="stat-label">Average Score</div>
        </div>
        <div class="stat">
            <div class="stat-number">{{ $passed }}</div>
            <div class="stat-label">Passed (≥70%)</div>
        </div>
        <div class="stat">
            <div class="stat-number">{{ $attempts->count() > 0 ? round(($passed / $attempts->count()) * 100) : 0 }}%</div>
            <div class="stat-label">Pass Rate</div>
        </div>
    </div>

    {{-- Results table --}}
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Student</th>
                <th>Score</th>
                <th>Result</th>
                <th>Submitted</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($attempts as $i => $attempt)
                @php
                    $scoreClass = $attempt->score >= 70 ? 'score-pass' : ($attempt->score >= 50 ? 'score-warn' : 'score-fail');
                    $badgeClass = $attempt->score >= 70 ? 'badge-pass' : ($attempt->score >= 50 ? 'badge-warn' : 'badge-fail');
                    $badgeLabel = $attempt->score >= 70 ? 'Passed' : ($attempt->score >= 50 ? 'Needs Improvement' : 'Failed');
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $attempt->student->name }}</td>
                    <td class="{{ $scoreClass }}">{{ $attempt->score }}%</td>
                    <td><span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span></td>
                    <td>{{ $attempt->completed_at->format('d M Y, H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center; color:#9ca3af; padding:20px;">No submissions yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <span>{{ config('app.name') }}</span>
        <span>Generated {{ now()->format('d M Y, H:i') }}</span>
    </div>

</body>
</html>
