<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiPollVoteController extends Controller
{
    public function store(Request $request, string $token)
    {
        // CHANGEMENT :
        // On récupère le sondage grâce au token présent dans l'URL.
        // On charge aussi les options pour vérifier que les choix envoyés existent bien.
        $poll = Poll::with('options')
            ->where('secret_token', $token)
            ->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        // CHANGEMENT :
        // On bloque le vote si le sondage est encore en brouillon.
        // Un sondage brouillon ne doit pas être accessible au vote,
        // même si quelqu'un possède son lien avec token.
        if ($poll->is_draft) {
            return response()->json([
                'message' => 'This poll is still a draft.',
            ], 403);
        }

        // CHANGEMENT :
        // On bloque le vote si le sondage a une date de fin dépassée.
        if ($poll->ends_at && now()->greaterThan($poll->ends_at)) {
            return response()->json(['message' => 'This poll is closed.'], 403);
        }

        // CHANGEMENT :
        // Validation des options envoyées par le frontend.
        // Le frontend enverra toujours un tableau d'ids, même pour un choix unique.
        $validated = $request->validate([
            'option_ids' => ['required', 'array', 'min:1'],
            'option_ids.*' => ['required', 'integer'],
        ]);

        $optionIds = $validated['option_ids'];

        // CHANGEMENT :
        // Si le sondage n'autorise pas plusieurs choix,
        // on refuse plus d'une option.
        if (!$poll->allow_multiple_choices && count($optionIds) > 1) {
            return response()->json([
                'message' => 'Only one option can be selected for this poll.',
            ], 422);
        }

        // CHANGEMENT :
        // On vérifie que toutes les options sélectionnées appartiennent bien à ce sondage.
        $validOptionIds = $poll->options->pluck('id')->toArray();

        foreach ($optionIds as $optionId) {
            if (!in_array($optionId, $validOptionIds)) {
                return response()->json([
                    'message' => 'Invalid option selected.',
                ], 422);
            }
        }

        // CHANGEMENT :
        // On vérifie si l'utilisateur a déjà voté à ce sondage.
        $existingVotes = PollVote::where('poll_id', $poll->id)
            ->where('user_id', $request->user()->id)
            ->get();

        // CHANGEMENT :
        // Pour respecter la consigne obligatoire,
        // un utilisateur ne peut voter qu'une seule fois à un même sondage.
        // La modification du vote est un bonus non implémenté ici.
        if ($existingVotes->isNotEmpty()) {
            return response()->json([
                'message' => 'You have already voted for this poll.',
            ], 403);
        }

        DB::transaction(function () use ($request, $poll, $optionIds) {
            // CHANGEMENT :
            // Comme on a déjà vérifié qu'il n'existe pas de vote précédent,
            // on peut directement créer le vote.
            foreach ($optionIds as $optionId) {
                PollVote::create([
                    'poll_id' => $poll->id,
                    'user_id' => $request->user()->id,
                    'poll_option_id' => $optionId,
                ]);
            }
        });

        return response()->json([
            'message' => 'Vote submitted successfully.',
        ], 201);
    }
}