<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiKnowledgeBase;
use App\Models\AiKnowledgeDocument;
use App\Services\Ai\KnowledgeIndexingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAiKnowledgeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(AiKnowledgeDocument::with('knowledgeBase:id,name')->latest()->paginate(30));
    }

    public function store(Request $request, KnowledgeIndexingService $indexer): JsonResponse
    {
        abort_unless($request->user()->isOwner() || $request->user()->isManager(), 403);
        $data = $this->validated($request);
        $base = AiKnowledgeBase::firstOrCreate(['name' => 'ALAS Support'], ['status' => 'ACTIVE', 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
        $document = AiKnowledgeDocument::create([...$data, 'knowledge_base_id' => $base->id, 'status' => 'DRAFT', 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
        if ($request->boolean('index_now', true)) $indexer->index($document);
        return response()->json(['data' => $document->fresh('chunks')], 201);
    }

    public function update(Request $request, AiKnowledgeDocument $document, KnowledgeIndexingService $indexer): JsonResponse
    {
        abort_unless($request->user()->isOwner() || $request->user()->isManager(), 403);
        $data = $this->validated($request);
        $new = DB::transaction(fn () => AiKnowledgeDocument::create([...$data, 'knowledge_base_id' => $document->knowledge_base_id, 'previous_version_id' => $document->id, 'version' => $document->version + 1, 'status' => 'DRAFT', 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]));
        $indexer->index($new);
        return response()->json(['data' => $new->fresh('chunks')]);
    }

    public function disable(Request $request, AiKnowledgeDocument $document): JsonResponse
    {
        abort_unless($request->user()->isOwner() || $request->user()->isManager(), 403);
        $document->update(['status' => 'DISABLED', 'updated_by' => $request->user()->id]);
        return response()->json(['data' => $document]);
    }

    public function reindex(Request $request, AiKnowledgeDocument $document, KnowledgeIndexingService $indexer): JsonResponse
    {
        abort_unless($request->user()->isOwner() || $request->user()->isManager(), 403);
        $indexer->index($document);
        return response()->json(['data' => $document->fresh('chunks')]);
    }

    private function validated(Request $request): array
    {
        return $request->validate(['title' => ['required', 'string', 'max:255'], 'content' => ['required', 'string', 'max:100000'], 'source_type' => ['sometimes', 'in:MANUAL,FAQ'], 'category' => ['nullable', 'string', 'max:100']]);
    }
}
