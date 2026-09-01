<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\EnsuresAdmin;
use App\Livewire\Concerns\HasEditableSoundForm;
use App\Models\LibrarySound;
use App\Models\Screen;
use App\Models\Sound;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Library extends Component
{
    use EnsuresAdmin, HasEditableSoundForm, WithFileUploads;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile[] */
    public array $massUpload = [];

    public function uploadMassSounds(): void
    {
        $this->ensureAdmin();

        $this->validate(['massUpload.*' => Sound::AUDIO_RULES]);

        if (empty($this->massUpload)) {
            return;
        }

        $sortOrder = LibrarySound::max('sort_order') ?? -1;

        foreach ($this->massUpload as $file) {
            LibrarySound::create([
                'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'file_path' => $file->store('library-sounds', 'public'),
                'sort_order' => ++$sortOrder,
            ]);
        }

        $this->reset('massUpload');
    }

    public function uploadSound(): void
    {
        $this->ensureAdmin();

        $this->validate();

        if (! $this->newSound) {
            return;
        }

        LibrarySound::create($this->uploadedSoundAttributes('library-sounds', 'library-sounds/images') + [
            'sort_order' => $this->nextSortOrder(LibrarySound::query()),
        ]);

        $this->resetUploadForm();
    }

    public function deleteSound(LibrarySound $librarySound): void
    {
        $this->ensureAdmin();

        $librarySound->delete();
    }

    public function startEditSound(LibrarySound $librarySound): void
    {
        $this->ensureAdmin();

        $this->beginEditingSound($librarySound);
    }

    public function saveEditSound(): void
    {
        $this->ensureAdmin();

        $librarySound = LibrarySound::findOrFail($this->editingSoundId);

        $this->validate([
            'editName' => Screen::NAME_RULES,
            'editEmoji' => Sound::EMOJI_RULES,
            'editImage' => Sound::IMAGE_RULES,
        ]);

        $librarySound->update([
            'name' => $this->editName,
            'emoji' => $this->editEmoji !== '' ? $this->editEmoji : null,
            'image_path' => $this->updatedImagePath($librarySound->image_path, 'library-sounds/images'),
        ]);

        $this->cancelEditSound();
    }

    public function render()
    {
        return view('livewire.admin.library', [
            'librarySounds' => LibrarySound::orderBy('sort_order')->get(),
        ]);
    }
}
