<?php

namespace App\Livewire\AiSupport;

use App\Actions\Support\ResumeSupportAiAction;
use App\Actions\Support\SendAdminSupportMessageAction;
use App\Actions\Support\TakeOverSupportConversationAction;
use App\Enums\SupportConversationMode;
use App\Enums\SupportConversationStatus;
use App\Models\AiKnowledgeBase;
use App\Models\AiKnowledgeDocument;
use App\Models\AiRun;
use App\Models\AiSetting;
use App\Models\SupportConversation;
use App\Models\SupportEvent;
use App\Services\Ai\KnowledgeIndexingService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Index extends Component
{
    public string $tab = 'inbox';
    public ?string $selectedConversationId = null;
    public string $reply = '';
    public string $search = '';
    public string $filter = 'all';
    public string $knowledgeTitle = '';
    public string $knowledgeContent = '';
    public string $knowledgeCategory = '';

    public function mount(): void { abort_unless(auth()->check(), 403); }
    public function selectConversation(string $id): void { $this->selectedConversationId = $id; SupportConversation::whereKey($id)->update(['admin_unread_count' => 0]); }
    public function closeConversation(): void { $this->selectedConversationId = null; }
    public function sendReply(SendAdminSupportMessageAction $action): void { $this->validate(['reply' => 'required|string|max:2000']); $action->execute($this->selected(), auth()->user(), trim($this->reply)); $this->reply = ''; }
    public function takeOver(TakeOverSupportConversationAction $action): void { $action->execute($this->selected(), auth()->user()); }
    public function resumeAi(ResumeSupportAiAction $action): void { $action->execute($this->selected(), auth()->user()); }
    public function resolve(): void { DB::transaction(function () { $conversation = SupportConversation::lockForUpdate()->findOrFail($this->selectedConversationId); $conversation->update(['mode' => SupportConversationMode::RESOLVED, 'status' => SupportConversationStatus::RESOLVED, 'resolved_at' => now()]); SupportEvent::create(['conversation_id' => $conversation->id, 'event_type' => 'CONVERSATION_RESOLVED', 'actor_type' => 'ADMIN', 'actor_id' => auth()->id()]); }); }
    public function saveKnowledge(KnowledgeIndexingService $indexer): void
    {
        abort_unless(auth()->user()->isOwner() || auth()->user()->isManager(), 403);
        $this->validate(['knowledgeTitle' => 'required|string|max:255', 'knowledgeContent' => 'required|string|max:100000', 'knowledgeCategory' => 'nullable|string|max:100']);
        $base = AiKnowledgeBase::firstOrCreate(['name' => 'ALAS Support'], ['status' => 'ACTIVE', 'created_by' => auth()->id(), 'updated_by' => auth()->id()]);
        $document = AiKnowledgeDocument::create(['knowledge_base_id' => $base->id, 'title' => $this->knowledgeTitle, 'content' => $this->knowledgeContent, 'category' => $this->knowledgeCategory ?: null, 'source_type' => 'MANUAL', 'status' => 'DRAFT', 'created_by' => auth()->id(), 'updated_by' => auth()->id()]);
        $indexer->index($document); $this->reset('knowledgeTitle', 'knowledgeContent', 'knowledgeCategory'); session()->flash('success', 'Knowledge indexed and activated.');
    }
    public function disableKnowledge(string $id): void { abort_unless(auth()->user()->isOwner() || auth()->user()->isManager(), 403); AiKnowledgeDocument::whereKey($id)->update(['status' => 'DISABLED', 'updated_by' => auth()->id()]); }
    public function toggleAi(): void { abort_unless(auth()->user()->isOwner(), 403); $setting = AiSetting::firstOrFail(); $setting->update(['enabled' => ! $setting->enabled, 'updated_by' => auth()->id()]); }

    public function render()
    {
        $conversations = SupportConversation::query()->with('customer:id,display_name,email')->with(['messages' => fn ($q) => $q->latest()->limit(1)])
            ->when($this->search, fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('display_name', 'like', '%'.$this->search.'%')->orWhere('email', 'like', '%'.$this->search.'%')))
            ->when($this->filter !== 'all', fn ($q) => $q->where($this->filter === 'unread' ? 'admin_unread_count' : 'mode', $this->filter === 'unread' ? '>' : '=', $this->filter === 'unread' ? 0 : $this->filter))
            ->orderByDesc('last_message_at')->limit(50)->get();
        $selected = $this->selectedConversationId ? SupportConversation::with('customer', 'assignedAdmin:id,name', 'messages.senderUser:id,name')->find($this->selectedConversationId) : null;
        return view('livewire.ai-support.index', ['conversations' => $conversations, 'selected' => $selected, 'knowledge' => AiKnowledgeDocument::latest()->limit(50)->get(), 'settings' => AiSetting::first(), 'runs' => AiRun::latest()->limit(25)->get()])->layout('layouts.app', ['pageHeader' => 'AI Support']);
    }
    private function selected(): SupportConversation { return SupportConversation::findOrFail($this->selectedConversationId); }
}
