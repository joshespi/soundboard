<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\EnsuresAdmin;
use App\Models\LibrarySound;
use App\Models\Screen;
use App\Models\Sound;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Library extends Component
{
    use EnsuresAdmin, WithFileUploads;

    #[Validate(Sound::AUDIO_RULES)]
    public $newSound = null;

    #[Validate('nullable|string|max:255')]
    public string $newSoundName = '';

    #[Validate(Sound::EMOJI_RULES)]
    public string $newSoundEmoji = '';

    #[Validate(Sound::IMAGE_RULES)]
    public $newSoundImage = null;

    public ?int $editingSoundId = null;

    public string $editName = '';

    public string $editEmoji = '';

    #[Validate(Sound::IMAGE_RULES)]
    public $editImage = null;

    public bool $editRemoveImage = false;

    public function uploadSound(): void
    {
        $this->ensureAdmin();

        $this->validate();

        if (! $this->newSound) {
            return;
        }

        $path = $this->newSound->store('library-sounds', 'public');
        $imagePath = $this->newSoundImage
            ? $this->newSoundImage->store('library-sounds/images', 'public')
            : null;

        LibrarySound::create([
            'name' => $this->newSoundName !== '' ? $this->newSoundName : pathinfo($this->newSound->getClientOriginalName(), PATHINFO_FILENAME),
            'emoji' => $this->newSoundEmoji !== '' ? $this->newSoundEmoji : null,
            'image_path' => $imagePath,
            'file_path' => $path,
            'sort_order' => (LibrarySound::max('sort_order') ?? -1) + 1,
        ]);

        $this->reset('newSound', 'newSoundName', 'newSoundEmoji', 'newSoundImage');
    }

    public function deleteSound(LibrarySound $librarySound): void
    {
        $this->ensureAdmin();

        $librarySound->delete();
    }

    public function startEditSound(LibrarySound $librarySound): void
    {
        $this->editingSoundId = $librarySound->id;
        $this->editName = $librarySound->name;
        $this->editEmoji = $librarySound->emoji ?? '';
        $this->editImage = null;
        $this->editRemoveImage = false;
        $this->resetValidation();
    }

    public function cancelEditSound(): void
    {
        $this->reset('editingSoundId', 'editName', 'editEmoji', 'editImage', 'editRemoveImage');
        $this->resetValidation();
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

        $imagePath = $librarySound->image_path;

        if ($this->editImage) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            $imagePath = $this->editImage->store('library-sounds/images', 'public');
        } elseif ($this->editRemoveImage && $imagePath) {
            Storage::disk('public')->delete($imagePath);
            $imagePath = null;
        }

        $librarySound->update([
            'name' => $this->editName,
            'emoji' => $this->editEmoji !== '' ? $this->editEmoji : null,
            'image_path' => $imagePath,
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
