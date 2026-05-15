<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApiPollController extends Controller
{
    /**
     * Display a listing of the authenticated user's polls.
     */
    public function index(Request $request)
    {
        $polls = $request->user()->polls()->orderBy('created_at', 'desc')->get();

        return $polls;
    }

    /**
     * Display the specified poll by its secret token.
     */
    public function show(string $token)
    {
        $poll = Poll::with(['options' => function ($query) {
            $query->withCount('votes');
        }])->where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        return $poll;
    }

    /**
     * Store a newly created poll.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'question' => ['required', 'string', 'max:255'],
            'options.*.label' => ['required', 'string', 'max:255', 'distinct'],
            'is_draft' => ['boolean'],
            'allow_multiple_choices' => ['boolean'],
            'allow_vote_change' => ['boolean'],
            'results_public' => ['boolean'],
            'duration' => ['nullable', 'integer', 'min:1'],
        ]);

        $poll = DB::transaction(function () use ($request, $validated) {
            $isDraft = $validated['is_draft'] ?? true;
            $startedAt = $isDraft ? null : now();
            $duration = $validated['duration'] ?? null;

            $poll = new Poll();
            $poll->user_id = $request->user()->id;
            $poll->title = $validated['title'] ?? null;
            $poll->question = $validated['question'];
            $poll->secret_token = Str::random(40);
            $poll->is_draft = $isDraft;
            $poll->allow_multiple_choices = $validated['allow_multiple_choices'] ?? false;
            $poll->allow_vote_change = $validated['allow_vote_change'] ?? false;
            $poll->results_public = $validated['results_public'] ?? false;
            $poll->duration = $duration;
            $poll->started_at = $startedAt;
            $poll->ends_at = $startedAt && $duration ? $startedAt->copy()->addSeconds($duration) : null;
            $poll->save();

            foreach ($validated['options'] as $option) {
                $poll->options()->create([
                    'label' => $option['label'],
                ]);
            }

            return $poll->load('options');
        });

        return response()->json($poll, 201);
    }

    /**
     * Update the specified poll.
     */
    public function update(Request $request, int $id)
    {
        $poll = Poll::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        // CHANGEMENT :
        // Un sondage lancé ne peut plus être modifié.
        // Seuls les sondages encore en brouillon peuvent être édités.
        if (!$poll->is_draft) {
            return response()->json([
                'message' => 'A launched poll cannot be edited.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'question' => ['required', 'string', 'max:255'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.id' => ['nullable', 'integer'],
            'options.*.label' => ['required', 'string', 'max:255', 'distinct'],
            'is_draft' => ['boolean'],
            'allow_multiple_choices' => ['boolean'],
            'allow_vote_change' => ['boolean'],
            'results_public' => ['boolean'],
            'duration' => ['nullable', 'integer', 'min:1'],
        ]);

        $poll = DB::transaction(function () use ($poll, $validated) {
            $wasDraft = $poll->is_draft;
            $isDraft = $validated['is_draft'] ?? $poll->is_draft;
            $duration = $validated['duration'] ?? null;

            $poll->title = $validated['title'] ?? null;
            $poll->question = $validated['question'];
            $poll->is_draft = $isDraft;
            $poll->allow_multiple_choices = $validated['allow_multiple_choices'] ?? false;
            $poll->allow_vote_change = $validated['allow_vote_change'] ?? false;
            $poll->results_public = $validated['results_public'] ?? false;
            $poll->duration = $duration;

            if ($wasDraft && !$isDraft) {
                $poll->started_at = now();
            }

            if ($poll->started_at && $duration) {
                $poll->ends_at = $poll->started_at->copy()->addSeconds($duration);
            } else {
                $poll->ends_at = null;
            }

            $poll->save();

            $keptOptionIds = [];

            foreach ($validated['options'] as $optionData) {
                if (!empty($optionData['id'])) {
                    $option = $poll->options()->where('id', $optionData['id'])->first();

                    if ($option) {
                        $option->update([
                            'label' => $optionData['label'],
                        ]);

                        $keptOptionIds[] = $option->id;
                    }
                } else {
                    $option = $poll->options()->create([
                        'label' => $optionData['label'],
                    ]);

                    $keptOptionIds[] = $option->id;
                }
            }

            $poll->options()
                ->whereNotIn('id', $keptOptionIds)
                ->delete();

            return $poll->load('options');
        });

        return response()->json($poll);
    }

    /**
     * Remove the specified poll.
     */
    public function remove(Request $request, int $id)
    {
        $poll = Poll::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $poll->delete();

        return response()->json(['message' => 'success'], 200);
    }
}