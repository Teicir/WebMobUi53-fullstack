@php
    // CHANGEMENT :
    // On prépare les données envoyées à Vue dans une variable PHP.
    // Cela évite les erreurs de syntaxe avec @json([...]) directement dans l'attribut HTML.
    $props = [
        'poll' => $poll,
        'totalVotes' => $totalVotes,
        'canShowResults' => $canShowResults,
        'voteUrl' => url('/api/v1/polls/' . $poll->secret_token . '/votes'),
        'pollApiUrl' => url('/api/v1/polls/' . $poll->secret_token),
        'csrfToken' => csrf_token(),
    ];
@endphp

<x-vue-app-layout>
    <x-slot:title>
        {{ $poll->title ?? 'Sondage' }}
    </x-slot>

    <x-slot:scripts>
        @vite(['resources/js/poll-public.js'])
    </x-slot>

    <!-- CHANGEMENT :
         La page publique du sondage est maintenant gérée par Vue.
         Blade ne sert plus qu'à transmettre les données initiales au frontend. -->
    <div
        id="poll-public-app"
        data-props='@json($props)'
    ></div>
</x-vue-app-layout>