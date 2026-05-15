@php
    // CHANGEMENT :
    // On vérifie si le sondage est fermé à cause de sa date de fin.
    $isClosed = $poll->ends_at && now()->greaterThan($poll->ends_at);

    // CHANGEMENT :
    // $canShowResults vient maintenant du contrôleur.
    // Il vaut true si les résultats sont publics OU si l'utilisateur connecté est propriétaire.
@endphp

<x-vue-app-layout>
    <x-slot:title>
        {{ $poll->title ?? 'Sondage' }}
    </x-slot>

    <main class="poll-page">
        <section class="poll-card">
            <h1>{{ $poll->title ?? 'Sondage' }}</h1>

            <p class="question">
                {{ $poll->question }}
            </p>

            @if ($isClosed)
                <p class="error-message">
                    Ce sondage est terminé. Il n’est plus possible de voter.
                </p>
            @else
                <form id="vote-form">
                    <div class="options-list">
                        @foreach ($poll->options as $option)
                            <label class="option-card">
                                @if ($poll->allow_multiple_choices)
                                    <input
                                        type="checkbox"
                                        name="option_ids[]"
                                        value="{{ $option->id }}"
                                    >
                                @else
                                    <input
                                        type="radio"
                                        name="option_ids[]"
                                        value="{{ $option->id }}"
                                    >
                                @endif

                                <span>{{ $option->label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <button type="submit" class="vote-button">
                        Voter
                    </button>

                    <p id="vote-message" class="vote-message"></p>
                </form>
            @endif
        </section>

        @if ($canShowResults)
            <section class="poll-card results-card">
                <h2>Résultats</h2>

                <!-- CHANGEMENT :
                     On ajoute un id pour pouvoir mettre à jour le total en JavaScript. -->
                <p id="results-total" class="results-total">
                    Total des votes : {{ $totalVotes }}
                </p>

                <!-- CHANGEMENT :
                     On ajoute un id sur la liste des résultats pour la mettre à jour avec le polling. -->
                <div id="results-list" class="results-list">
                    @foreach ($poll->options as $option)
                        @php
                            $percentage = $totalVotes > 0
                                ? round(($option->votes_count / $totalVotes) * 100)
                                : 0;
                        @endphp

                        <div class="result-item">
                            <div class="result-header">
                                <span>{{ $option->label }}</span>
                                <span>
                                    {{ $option->votes_count }} vote(s) — {{ $percentage }}%
                                </span>
                            </div>

                            <div class="result-bar">
                                <div
                                    class="result-bar-fill"
                                    style="width: {{ $percentage }}%;"
                                ></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @else
            <section class="poll-card results-card">
                <h2>Résultats</h2>

                <p class="muted-message">
                    Les résultats de ce sondage ne sont pas publics.
                </p>
            </section>
        @endif
    </main>

    <script>
        const form = document.querySelector('#vote-form');
        const message = document.querySelector('#vote-message');

        const canShowResults = @json($canShowResults);
        const resultsTotal = document.querySelector('#results-total');
        const resultsList = document.querySelector('#results-list');

        function renderResults(poll) {
            if (!canShowResults || !resultsTotal || !resultsList) return;

            const totalVotes = poll.options.reduce((total, option) => {
                return total + option.votes_count;
            }, 0);

            resultsTotal.textContent = `Total des votes : ${totalVotes}`;

            resultsList.innerHTML = poll.options.map(option => {
                const percentage = totalVotes > 0
                    ? Math.round((option.votes_count / totalVotes) * 100)
                    : 0;

                return `
                    <div class="result-item">
                        <div class="result-header">
                            <span>${option.label}</span>
                            <span>${option.votes_count} vote(s) — ${percentage}%</span>
                        </div>

                        <div class="result-bar">
                            <div
                                class="result-bar-fill"
                                style="width: ${percentage}%;"
                            ></div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        async function fetchResults() {
            if (!canShowResults) return;

            try {
                const response = await fetch('/api/v1/polls/{{ $poll->secret_token }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XmlHttpRequest',
                    },
                });

                if (!response.ok) return;

                const poll = await response.json();

                renderResults(poll);
            } catch (error) {
                console.error('Erreur récupération résultats :', error);
            }
        }

        if (canShowResults) {
            fetchResults();

            // CHANGEMENT :
            // Polling : on récupère les résultats toutes les 3 secondes.
            setInterval(fetchResults, 3000);
        }

        if (form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const checkedOptions = form.querySelectorAll('input[name="option_ids[]"]:checked');

                const optionIds = Array.from(checkedOptions).map(option => Number(option.value));

                if (optionIds.length === 0) {
                    message.textContent = 'Veuillez sélectionner une réponse.';
                    message.className = 'vote-message error-message';
                    return;
                }

                try {
                    const response = await fetch('/api/v1/polls/{{ $poll->secret_token }}/votes', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XmlHttpRequest',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            option_ids: optionIds,
                        }),
                    });

                    // CHANGEMENT :
                    // Si le backend refuse le vote, on récupère son vrai message d'erreur.
                    // Cela permet d'afficher un message clair si l'utilisateur a déjà voté.
                    if (!response.ok) {
                        const errorData = await response.json();

                        throw new Error(errorData.message);
                    }

                    message.textContent = 'Votre vote a bien été enregistré.';
                    message.className = 'vote-message success-message';

                    // CHANGEMENT :
                    // Au lieu de recharger toute la page, on met directement les résultats à jour.
                    fetchResults();
                } catch (error) {
                    // CHANGEMENT :
                    // Message spécifique si l'utilisateur tente de voter une deuxième fois.
                    if (error.message === 'You have already voted for this poll.') {
                        message.textContent = 'Vous avez déjà voté à ce sondage.';
                    } else {
                        message.textContent = 'Erreur avec le backend. Le vote n’a pas pu être enregistré.';
                    }

                    message.className = 'vote-message error-message';
                }
            });
        }
    </script>

    <style>
        .poll-page {
            max-width: 700px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .poll-card {
            padding: 1.5rem;
            background: white;
            border: 1px solid #ddd;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .question {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
        }

        .options-list,
        .results-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .option-card {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            cursor: pointer;
        }

        .option-card:hover {
            background-color: #f8fafc;
        }

        .vote-button {
            padding: 0.75rem 1rem;
            color: white;
            background-color: #2563eb;
            border: none;
            border-radius: 0.5rem;
            font-weight: 700;
            cursor: pointer;
        }

        .vote-button:hover {
            background-color: #1d4ed8;
        }

        .vote-message {
            margin-top: 1rem;
            font-weight: 600;
        }

        .success-message {
            color: #166534;
        }

        .error-message {
            color: #b91c1c;
        }

        .muted-message,
        .results-total {
            color: #64748b;
        }

        .result-item {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .result-header {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            font-weight: 600;
        }

        .result-bar {
            height: 0.75rem;
            overflow: hidden;
            background-color: #e2e8f0;
            border-radius: 999px;
        }

        .result-bar-fill {
            height: 100%;
            background-color: #2563eb;
            border-radius: 999px;
        }

        /* CHANGEMENT :
    Adaptation mobile de la page publique du sondage. */
        @media (max-width: 700px) {
            .poll-page {
                margin: 1rem auto;
                padding: 0 1rem;
            }

            .poll-card {
                padding: 1rem;
            }

            .vote-button {
                width: 100%;
            }

            .result-header {
                flex-direction: column;
                gap: 0.25rem;
            }

            .chart-title {
                margin: 1.25rem 0 0.75rem;
                font-size: 1rem;
                color: #334155;
            }
        }
            </style>
</x-vue-app-layout>