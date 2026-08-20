<x-mail::message>
# Potwierdzenie rezerwacji #{{ $shortId }}

Witaj{{ $rental->name ? ' ' . $rental->name : '' }},

Dziekujemy za dokonanie rezerwacji. Ponizej szczegoly:

<x-mail::panel>
**Numer rezerwacji:** {{ $rental->id }}
**Status:** {{ ucfirst($rental->status) }}
@if($rentable)
**Zasob:** {{ $rentable->name ?? $rentable->title ?? class_basename($rentable) . ' #' . $rentable->getKey() }}
@endif
**Data rozpoczecia:** {{ \Illuminate\Support\Carbon::parse($rental->start_date)->format('d.m.Y') }}
**Data zakonczenia:** {{ \Illuminate\Support\Carbon::parse($rental->end_date)->format('d.m.Y') }}
**Liczba dni:** {{ $rental->daysCount() }}
@if($rental->qty > 1)
**Ilosc:** {{ $rental->qty }}
@endif
**Kwota:** {{ $amount }} {{ $currency }}
@if($rental->payment_order_id)
**Numer transakcji:** {{ $rental->payment_order_id }}
@endif
</x-mail::panel>

@if($rental->message)
**Twoja wiadomosc:**

> {{ $rental->message }}
@endif

W razie pytan prosimy o kontakt zwrotny na ten email.

Pozdrawiamy,<br>
{{ config('app.name') }}

<x-slot:subcopy>
Ten email zostal wygenerowany automatycznie po potwierdzeniu rezerwacji.
Jesli to nie ty, zignoruj go lub skontaktuj sie z nami.
</x-slot:subcopy>
</x-mail::message>
