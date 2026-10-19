
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Regression TestMate</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1100px;
            margin: 35px auto;
            padding: 0 20px;
            background: #f5f7fb;
            color: #1f2937;
        }
        .panel {
            background: white;
            padding: 22px;
            margin: 20px 0;
            border-radius: 10px;
        }
        .code-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }
        textarea {
            width: 100%;
            min-height: 260px;
            box-sizing: border-box;
            padding: 12px;
            font: 13px monospace;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
        }
        button {
            background: #2563eb;
            color: white;
            padding: 12px 18px;
            border: 0;
            border-radius: 6px;
            cursor: pointer;
        }
        pre {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            background: #111827;
            color: #f9fafb;
            padding: 18px;
            border-radius: 8px;
        }
        .error { color: #b91c1c; }
        @media (max-width: 700px) {
            .code-grid { grid-template-columns: 1fr; }
        }
.diff-output {
    background: #111827;
    color: #f9fafb;
    padding: 2px 6px;
    margin: 0;
    border-radius: 8px;
    overflow: auto;
    white-space: pre;
    font: 12px/1.2 monospace;
    max-height: 220px;
}

.diff-added,
.diff-removed,
.diff-context {
    display: block;
    min-height: 1.5em;
}

.diff-added {
    color: #86efac;
}

.diff-removed {
    color: #fca5a5;
}

.diff-context {
    color: #d1d5db;
}

.analysis-output {
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    background: #ffffff;
    color: #1f2937;
    padding: 8px;
    margin: 0;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font: 14px/1.7 Arial, sans-serif;
}

.risk-card {
    padding: 16px;
    margin-bottom: 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #f9fafb;
}

.risk-card h3 {
    margin: 0 0 8px;
}

.risk-score {
    margin: 0;
    font-size: 32px;
    font-weight: bold;
}

.risk-score span {
    font-size: 16px;
    color: #6b7280;
}

.risk-level {
    font-weight: bold;
}

.risk-low {
    color: #15803d;
}

.risk-medium {
    color: #b45309;
}

.risk-high {
    color: #b91c1c;
}


.risk-progress {
    width: 100%;
    height: 10px;
    margin: 12px 0;
    overflow: hidden;
    background: #e5e7eb;
    border-radius: 999px;
}

.risk-progress-bar {
    height: 100%;
    border-radius: 999px;
    transition: width 0.3s ease;
}

.risk-bar-low {
    background: #16a34a;
}

.risk-bar-medium {
    background: #d97706;
}

.risk-bar-high {
    background: #dc2626;
}

.risk-note {
    margin-bottom: 0;
    color: #6b7280;
    font-size: 13px;
}


    </style>
</head>
<body>
    <h1>Regression TestMate</h1>
    <p>Compare code versions and get AI recommendations for regression testing.</p>

    <p><a href="{{ route('testmate.index') }}">Open Test Generation</a></p>

    <div class="panel">
        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('regression.analyze') }}">
            @csrf

            <div class="code-grid">
                <div>
                    <label for="original_code"><strong>Original Code</strong></label>
                    <br><br>
                    <textarea
                        id="original_code"
                        name="original_code"
                        maxlength="12000"
                        required
                        placeholder="Paste the original PHP code">{{ old('original_code', $originalCode ?? '') }}</textarea>
                </div>

                <div>
                    <label for="updated_code"><strong>Updated Code</strong></label>
                    <br><br>
                    <textarea
                        id="updated_code"
                        name="updated_code"
                        maxlength="12000"
                        required
                        placeholder="Paste the updated PHP code">{{ old('updated_code', $updatedCode ?? '') }}</textarea>
                </div>
            </div>

            <br>
            <button type="submit">Analyze Regression Risks</button>
        </form>
    </div>

    
@isset($diff)
    <div class="panel">
        <h2>Code Changes (Unified Diff)</h2>

        @if ($diff === '')
            <p>No code changes detected. Both code versions are identical.</p>
        @else
           <pre class="diff-output">@foreach (preg_split('/\r\n|\r|\n/', $diff) as $line)<span class="{{ str_starts_with($line, '+') && !str_starts_with($line, '+++') ? 'diff-added' : (str_starts_with($line, '-') && !str_starts_with($line, '---') ? 'diff-removed' : 'diff-context') }}">{{ $line }}</span>{{ "\n" }}@endforeach</pre>
        @endif
    </div>
@endisset


@if (isset($riskScore, $riskLevel))
    <div class="risk-card">
        <h3>Regression Risk Score</h3>

        <div class="risk-score">
            {{ $riskScore }}<span>/100</span>
        </div>

        <p class="risk-level risk-{{ strtolower($riskLevel) }}">
            {{ $riskLevel }} Risk
        </p>

        <div
            class="risk-progress"
            role="progressbar"
            aria-label="Regression risk score"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-valuenow="{{ $riskScore }}"
        >
            <div
                class="risk-progress-bar risk-bar-{{ strtolower($riskLevel) }}"
                style="width: {{ $riskScore }}%;"
            ></div>
        </div>

        <p class="risk-note">
            AI-generated estimate, not a validated prediction.
        </p>
    </div>
@endif

    @isset($analysisHtml)
        <div class="panel">
            <h2>Regression Analysis</h2>
            <p>These are AI-generated recommendations. No tests have been executed by this analysis.</p>
            <pre class="analysis-output">{!! $analysisHtml !!}</pre>
        </div>
    @endisset
</body>
</html>
