<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Not Found</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { --brand-primary: #392a26; }
        body { font-family: ui-sans-serif, system-ui, sans-serif; margin: 0; padding: 2rem; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f9fafb; }
        .box { max-width: 28rem; padding: 2rem; background: white; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        h1 { font-size: 1.5rem; color: #111827; margin: 0 0 0.5rem 0; }
        .code { font-size: 0.875rem; color: #6b7280; margin-bottom: 1rem; }
        .message { color: #374151; font-size: 0.9375rem; line-height: 1.5; margin-bottom: 1.5rem; }
        a { color: var(--brand-primary); text-decoration: none; font-size: 0.875rem; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="box">
        <h1>404 | Not Found</h1>
        <p class="code">The page or resource you requested could not be found.</p>
        @if(isset($exception) && $exception->getMessage())
            <p class="message">{{ $exception->getMessage() }}</p>
        @endif
        <a href="{{ url('/') }}">Go to homepage</a>
    </div>
</body>
</html>
