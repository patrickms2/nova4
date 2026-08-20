@props(['record'])

@include('pdf.layouts.app', [
    'record' => $record,
    'slot' => $slot
])
