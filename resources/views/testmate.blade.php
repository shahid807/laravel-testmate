
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel TestMate</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
            background: #f5f7fb;
            color: #1f2937;
        }
        textarea {
            width: 100%;
            min-height: 280px;
            box-sizing: border-box;
            padding: 14px;
            font: 14px monospace;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
        }
        button {
            margin-top: 12px;
            padding: 12px 20px;
            background: #2563eb;
            color: white;
            border: 0;
            border-radius: 6px;
            cursor: pointer;
        }
        pre {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            background: #111827;
            color: #e5e7eb;
            padding: 18px;
            border-radius: 8px;
        }
        .error { color: #b91c1c; }
        .panel {
            background: white;
            padding: 24px;
            border-radius: 10px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <h1>Laravel TestMate</h1>
    <p>Generate PHPUnit test suggestions for your Laravel and PHP code.</p>

     <p><a href="{{ route('regression.index') }}">Open regression testing</a></p>

    <div class="panel">
        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('testmate.generate') }}">
            @csrf

            <label for="code"><strong>Paste your PHP/Laravel code</strong></label>
            <br><br>

            <textarea id="code" name="code" required
                maxlength="12000"
                placeholder="Paste a controller, service, or method here...">{{ old('code', $originalCode ?? '') }}</textarea>

            <button type="submit">Generate PHPUnit Tests</button>
        </form>
    </div>

    @isset($generatedTests)
        <div class="panel">
            <h2>AI-Generated Test Suggestions</h2>
            <p>These tests are suggestions and have not been executed.</p>
            <pre>{{ $generatedTests }}</pre>
        </div>
    @endisset
</body>
</html>
