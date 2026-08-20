<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Already paid</title>
</head>
<body>
    <h1>Already paid</h1>
    <p>Reference: {{ $request->request_reference }}</p>
    <p><a href="{{ route('public.explore') }}">Back to explore</a></p>
</body>
</html>

