<?php

namespace App\Http\Controllers;

use App\Models\Poll;
use Illuminate\Http\Request;

class PollShareController extends Controller
{
    public function show(Request $request, string $token)
    {
        // CHANGEMENT :
        // On récupère le sondage à partir de son secret_token.
        // withCount('votes') ajoute votes_count sur chaque option.
        $poll = Poll::with(['options' => function ($query) {
            $query->withCount('votes');
        }])
            ->where('secret_token', $token)
            ->firstOrFail();

        // CHANGEMENT :
        // On calcule le nombre total de votes pour pouvoir afficher des pourcentages.
        $totalVotes = $poll->options->sum('votes_count');

        // CHANGEMENT :
        // On vérifie si l'utilisateur connecté est le propriétaire du sondage.
        // Si personne n'est connecté, user() vaut null.
        $isOwner = $request->user()?->id === $poll->user_id;

        // CHANGEMENT :
        // Les résultats sont visibles si :
        // - le sondage les rend publics
        // - ou si l'utilisateur connecté est le propriétaire du sondage
        $canShowResults = $poll->results_public || $isOwner;

        // CHANGEMENT :
        // On retourne aussi totalVotes et canShowResults à la vue Blade.
        return view('polls.show', [
            'poll' => $poll,
            'totalVotes' => $totalVotes,
            'canShowResults' => $canShowResults,
        ]);
    }
}