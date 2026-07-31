<?php

namespace App\Livewire\PromotionActivities;

use App\Models\CompensationRecord;
use App\Models\PromotionActivity;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    public bool $showSubmitModal = false;
    public ?int $reviewingActivityId = null;
    public string $activity_type = 'Event / booth shift';
    public string $activity_date = '';
    public string $campaign = '';
    public string $platform = '';
    public string $outcome = '';
    public $proof = null;
    public string $review_amount = '';
    public string $review_notes = '';

    public function mount(): void
    {
        $this->activity_date = now()->toDateString();

        $activityId = request()->integer('activity');
        if ($activityId && (auth()->user()->isOwner() || auth()->user()->isManager())) {
            $activity = PromotionActivity::whereKey($activityId)->where('status', 'submitted')->first();
            if ($activity && $activity->user_id !== auth()->id()) {
                $this->openReview($activity->id);
            }
        }
    }

    public function submit(): void
    {
        $this->validate([
            'activity_type' => 'required|string|max:100',
            'activity_date' => 'required|date|before_or_equal:today',
            'campaign' => 'nullable|string|max:120',
            'platform' => 'nullable|string|max:120',
            'outcome' => 'nullable|string|max:1000',
            'proof' => 'nullable|image|max:5120',
        ]);

        $proofPath = $this->proof?->store('activity-proofs', 'public');

        $activity = PromotionActivity::create([
            'user_id' => auth()->id(),
            'activity_type' => $this->activity_type,
            'activity_date' => $this->activity_date,
            'campaign' => $this->campaign ?: null,
            'platform' => $this->platform ?: null,
            'outcome' => $this->outcome ?: null,
            'proof_path' => $proofPath,
            'proof_original_name' => $this->proof?->getClientOriginalName(),
            'proof_size' => $this->proof?->getSize(),
            'status' => 'submitted',
        ]);

        ActivityLogService::log(
            'Promotion Activity Submitted',
            "Submitted {$activity->activity_type} for {$activity->activity_date->format('M j, Y')}.",
            $activity,
            ['activity_type' => $activity->activity_type, 'new_status' => 'submitted']
        );

        $activity->load('user');
        NotificationService::notifyPromotionActivitySubmitted($activity);

        $this->resetSubmissionForm();
        $this->showSubmitModal = false;
        session()->flash('success', 'Activity submitted for review. Your incentive will appear after approval.');
    }

    public function openReview(int $activityId): void
    {
        abort_unless(auth()->user()->isOwner() || auth()->user()->isManager(), 403);

        $activity = PromotionActivity::findOrFail($activityId);
        abort_if($activity->user_id === auth()->id(), 403);
        abort_unless($activity->status === 'submitted', 422);

        $this->reviewingActivityId = $activity->id;
        $this->review_amount = '10.00';
        $this->review_notes = '';
    }

    public function approve(): void
    {
        abort_unless(auth()->user()->isOwner() || auth()->user()->isManager(), 403);
        $this->validate([
            'reviewingActivityId' => 'required|exists:promotion_activities,id',
            'review_amount' => 'required|numeric|min:0.01',
            'review_notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function (): void {
            $activity = PromotionActivity::lockForUpdate()->findOrFail($this->reviewingActivityId);

            abort_if($activity->user_id === auth()->id(), 403);
            abort_unless($activity->status === 'submitted', 422, 'This activity has already been reviewed.');

            $activity->update([
                'status' => 'approved',
                'approved_amount' => $this->review_amount,
                'review_notes' => $this->review_notes ?: null,
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);

            $record = CompensationRecord::create([
                'record_number' => 'PENDING',
                'user_id' => $activity->user_id,
                'promotion_activity_id' => $activity->id,
                'type' => 'activity_incentive',
                'amount' => $this->review_amount,
                'period_start' => $activity->activity_date,
                'period_end' => $activity->activity_date,
                'status' => 'pending_approval',
                'remarks' => "{$activity->activity_type}: " . ($this->review_notes ?: 'Promotional activity approved.'),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
            $record->update(['record_number' => 'CMP-'.str_pad($record->id, 6, '0', STR_PAD_LEFT)]);

            ActivityLogService::log(
                'Activity Incentive Earned',
                "Approved {$activity->activity_type} and created {$record->record_number} for ₱".number_format($record->amount, 2).'.',
                $activity,
                ['compensation_type' => 'activity_incentive', 'amount' => $record->amount, 'previous_status' => 'submitted', 'new_status' => 'pending_approval']
            );

            $activity->load('user');
            $record->load('user');
            NotificationService::notifyPromotionActivityApproved($activity);
            NotificationService::notifyCompensationAwaitingApproval($record);
        });

        $this->resetReviewForm();
        session()->flash('success', 'Activity approved and added to compensation for Owner approval.');
    }

    public function reject(): void
    {
        abort_unless(auth()->user()->isOwner() || auth()->user()->isManager(), 403);
        $this->validate([
            'reviewingActivityId' => 'required|exists:promotion_activities,id',
            'review_notes' => 'required|string|min:3|max:1000',
        ]);

        $activity = PromotionActivity::whereKey($this->reviewingActivityId)->where('status', 'submitted')->firstOrFail();
        abort_if($activity->user_id === auth()->id(), 403);
        $activity->update(['status' => 'rejected', 'review_notes' => $this->review_notes, 'reviewed_at' => now(), 'reviewed_by' => auth()->id()]);

        ActivityLogService::log('Promotion Activity Rejected', "Rejected {$activity->activity_type}.", $activity, ['previous_status' => 'submitted', 'new_status' => 'rejected']);
        $activity->load('user');
        NotificationService::notifyPromotionActivityRejected($activity);
        $this->resetReviewForm();
        session()->flash('success', 'Activity marked as rejected.');
    }

    public function render()
    {
        $query = PromotionActivity::with(['user', 'reviewer', 'compensationRecord'])->latest('id');
        $isStaff = auth()->user()->isStaff();

        if ($isStaff) {
            $query->where('user_id', auth()->id());
        } elseif (auth()->user()->isManager()) {
            $query->whereHas('user', fn ($users) => $users->where('role', 'staff'));
        }

        return view('livewire.promotion-activities.index', [
            'activities' => $query->paginate(12),
            'canReview' => auth()->user()->isOwner() || auth()->user()->isManager(),
        ])->layout('layouts.app', ['pageHeader' => 'Promotion Activities']);
    }

    private function resetSubmissionForm(): void
    {
        $this->reset('campaign', 'platform', 'outcome', 'proof');
        $this->activity_type = 'Event / booth shift';
        $this->activity_date = now()->toDateString();
    }

    private function resetReviewForm(): void
    {
        $this->reset('reviewingActivityId', 'review_amount', 'review_notes');
    }
}
