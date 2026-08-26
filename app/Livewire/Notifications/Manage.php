<?php

namespace App\Livewire\Notifications;

use App\Models\Announcement;
use App\Models\RecurringAnnouncement;
use App\Services\RecurringAnnouncementService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Manage extends Component
{
    use WithFileUploads;
    use WithPagination;

    public bool $showComposeModal = false;
    public string $title = '';
    public string $message = '';
    public $image = null;
    public string $target_role = 'all';
    public string $delivery = 'immediate';
    public string $scheduled_for = '';
    public ?RecurringAnnouncement $earningReminder = null;
    public string $earning_title = '';
    public string $earning_message = '';
    public string $earning_target_role = 'all';
    public string $earning_send_time = '20:00';
    public bool $earning_is_active = true;

    public function mount(): void
    {
        abort_unless(auth()->user()->isOwner() || auth()->user()->isManager(), 403);
        $this->scheduled_for = now()->addHour()->format('Y-m-d\\TH:i');
        $this->loadEarningReminder();
    }

    public function save(): void
    {
        abort_unless(auth()->user()->isOwner() || auth()->user()->isManager(), 403);

        $this->validate([
            'title' => 'required|string|min:3|max:120',
            'message' => 'required|string|min:3|max:2000',
            'image' => 'nullable|image|max:5120',
            'target_role' => 'required|in:all,owner,manager,staff',
            'delivery' => 'required|in:immediate,scheduled',
            'scheduled_for' => 'required_if:delivery,scheduled|nullable|date|after:now',
        ]);

        $isScheduled = $this->delivery === 'scheduled';
        $imagePath = $this->image?->store('announcement-images', 'public');
        $announcement = Announcement::create([
            'created_by' => auth()->id(),
            'target_role' => $this->target_role,
            'title' => $this->title,
            'message' => $this->message,
            'image_path' => $imagePath,
            'image_original_name' => $this->image?->getClientOriginalName(),
            'status' => $isScheduled ? 'scheduled' : 'draft',
            'scheduled_for' => $isScheduled ? Carbon::parse($this->scheduled_for) : null,
        ]);

        if ($isScheduled) {
            session()->flash('success', 'Announcement scheduled for '.$announcement->scheduled_for->format('M j, Y g:i A').'.');
        } else {
            NotificationService::publishAnnouncement($announcement);
            session()->flash('success', 'Announcement sent to the selected recipients.');
        }

        $this->resetComposeForm();
        $this->showComposeModal = false;
    }

    public function render()
    {
        return view('livewire.notifications.manage', [
            'announcements' => Announcement::with('creator')->latest('id')->paginate(12),
        ])->layout('layouts.app', ['pageHeader' => 'Announcements']);
    }

    public function saveEarningReminder(): void
    {
        abort_unless(auth()->user()->isOwner() || auth()->user()->isManager(), 403);

        $this->validate([
            'earning_title' => 'required|string|min:3|max:120',
            'earning_message' => 'required|string|min:3|max:2000',
            'earning_target_role' => 'required|in:all,owner,manager,staff',
            'earning_send_time' => 'required|date_format:H:i',
            'earning_is_active' => 'boolean',
        ]);

        $this->earningReminder = RecurringAnnouncement::updateOrCreate(
            ['key' => RecurringAnnouncementService::SOCIAL_SHARING_EARNING_KEY],
            [
                'created_by' => $this->earningReminder?->created_by ?? auth()->id(),
                'updated_by' => auth()->id(),
                'target_role' => $this->earning_target_role,
                'title' => $this->earning_title,
                'message' => $this->earning_message,
                'send_time' => $this->earning_send_time.':00',
                'timezone' => 'Asia/Manila',
                'is_active' => $this->earning_is_active,
            ],
        );

        session()->flash('success', 'Earning opportunity reminder updated.');
    }

    private function resetComposeForm(): void
    {
        $this->reset('title', 'message', 'image');
        $this->target_role = 'all';
        $this->delivery = 'immediate';
        $this->scheduled_for = now()->addHour()->format('Y-m-d\\TH:i');
    }

    private function loadEarningReminder(): void
    {
        $this->earningReminder = RecurringAnnouncement::where('key', RecurringAnnouncementService::SOCIAL_SHARING_EARNING_KEY)->first();
        $this->earning_title = $this->earningReminder?->title ?? 'Earn more by sharing our posts';
        $this->earning_message = $this->earningReminder?->message ?? 'Share approved ALAS posts on Facebook or Instagram to earn. Open Promotion Activities for the latest instructions.';
        $this->earning_target_role = $this->earningReminder?->target_role ?? 'all';
        $this->earning_send_time = substr((string) ($this->earningReminder?->send_time ?? '20:00'), 0, 5);
        $this->earning_is_active = $this->earningReminder?->is_active ?? true;
    }
}
