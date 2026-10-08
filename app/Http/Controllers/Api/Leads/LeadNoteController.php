<?php

namespace App\Http\Controllers\Api\Leads;

use App\Http\Controllers\Api\ApiController;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadNoteController extends ApiController
{
    public function index(Lead $lead): JsonResponse
    {
        $notes = $lead->leadNotes()->with('user:id,name')->latest()->get();

        return $this->success($notes->map(fn (LeadNote $note) => [
            'id' => $note->id,
            'lead_id' => $note->lead_id,
            'user_id' => $note->user_id,
            'user' => $note->user ? ['id' => $note->user->id, 'name' => $note->user->name] : null,
            'note' => $note->note,
            'content' => $note->note,
            'created_at' => $note->created_at?->toIso8601String(),
            'updated_at' => $note->updated_at?->toIso8601String(),
        ])->all(), 'Lead notes loaded');
    }

    public function store(Request $request, Lead $lead, LeadService $leads): JsonResponse
    {
        $data = $request->validate([
            'note' => ['required_without:content', 'nullable', 'string'],
            'content' => ['required_without:note', 'nullable', 'string'],
        ]);
        $note = $leads->addNote($lead, (string) ($data['note'] ?? $data['content']), $request->user());

        return $this->success([
            'id' => $note->id,
            'lead_id' => $note->lead_id,
            'user_id' => $note->user_id,
            'note' => $note->note,
            'content' => $note->note,
            'user' => $note->user ? ['id' => $note->user->id, 'name' => $note->user->name] : null,
            'created_at' => $note->created_at?->toIso8601String(),
        ], 'Note created', 201);
    }

    public function update(Request $request, Lead $lead, LeadNote $note, LeadService $leads): JsonResponse
    {
        $data = $request->validate([
            'note' => ['required_without:content', 'nullable', 'string'],
            'content' => ['required_without:note', 'nullable', 'string'],
        ]);
        $note = $leads->updateNote($lead, $note, (string) ($data['note'] ?? $data['content']), $request->user());

        return $this->success([
            'id' => $note->id,
            'note' => $note->note,
            'content' => $note->note,
            'updated_at' => $note->updated_at?->toIso8601String(),
        ], 'Note updated');
    }

    public function destroy(Request $request, Lead $lead, LeadNote $note, LeadService $leads): JsonResponse
    {
        $leads->deleteNote($lead, $note, $request->user());

        return $this->success(null, 'Note deleted');
    }
}
