<?php

namespace App\Livewire\DashboardBanners;

use App\Models\DashboardBanner;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Index extends Component
{
    use WithFileUploads;

    public bool $showUploadModal = false;
    public string $title = '';
    public $image = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->isOwner() || auth()->user()->isManager(), 403);
    }

    public function save(): void
    {
        $this->validate([
            'title' => 'nullable|string|max:120',
            'image' => 'required|image|max:5120',
        ]);

        DashboardBanner::create([
            'uploaded_by' => auth()->id(),
            'title' => trim($this->title) ?: null,
            'image_path' => $this->image->store('dashboard-banners', 'public'),
            'image_original_name' => $this->image->getClientOriginalName(),
            'display_order' => (int) DashboardBanner::max('display_order') + 1,
        ]);

        $this->reset('title', 'image');
        $this->showUploadModal = false;
        session()->flash('success', 'Gallery image uploaded. It is now visible in every dashboard carousel.');
    }

    public function toggle(int $bannerId): void
    {
        $banner = DashboardBanner::findOrFail($bannerId);
        $banner->update(['is_active' => ! $banner->is_active]);
    }

    public function delete(int $bannerId): void
    {
        $banner = DashboardBanner::findOrFail($bannerId);
        Storage::disk('public')->delete($banner->image_path);
        $banner->delete();
        session()->flash('success', 'Gallery image removed.');
    }

    public function render()
    {
        return view('livewire.dashboard-banners.index', [
            'banners' => DashboardBanner::with('uploader')->orderBy('display_order')->latest('id')->get(),
        ])->layout('layouts.app', ['pageHeader' => 'Dashboard Gallery']);
    }
}
