<?php

namespace App\Livewire\Concerns;

use App\Models\LibrarySound;
use App\Models\Sound;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Validate;

/**
 * Shared upload/edit form state and file-handling for components that manage
 * a list of Sound-shaped records (Sound or LibrarySound) with a name, emoji,
 * audio file, and optional tile image.
 */
trait HasEditableSoundForm
{
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

    protected function beginEditingSound(Sound|LibrarySound $sound): void
    {
        $this->editingSoundId = $sound->id;
        $this->editName = $sound->name;
        $this->editEmoji = $sound->emoji ?? '';
        $this->editImage = null;
        $this->editRemoveImage = false;
        $this->resetValidation();
    }

    public function cancelEditSound(): void
    {
        $this->reset('editingSoundId', 'editName', 'editEmoji', 'editImage', 'editRemoveImage');
        $this->resetValidation();
    }

    protected function uploadedSoundAttributes(string $audioPath, string $imagePath): array
    {
        $path = $this->newSound->store($audioPath, 'public');
        $storedImagePath = $this->newSoundImage
            ? $this->newSoundImage->store($imagePath, 'public')
            : null;

        return [
            'name' => $this->newSoundName !== '' ? $this->newSoundName : pathinfo($this->newSound->getClientOriginalName(), PATHINFO_FILENAME),
            'emoji' => $this->newSoundEmoji !== '' ? $this->newSoundEmoji : null,
            'image_path' => $storedImagePath,
            'file_path' => $path,
        ];
    }

    protected function resetUploadForm(): void
    {
        $this->reset('newSound', 'newSoundName', 'newSoundEmoji', 'newSoundImage');
    }

    protected function updatedImagePath(?string $currentImagePath, string $imageStoragePath): ?string
    {
        if ($this->editImage) {
            if ($currentImagePath) {
                Storage::disk('public')->delete($currentImagePath);
            }

            return $this->editImage->store($imageStoragePath, 'public');
        }

        if ($this->editRemoveImage && $currentImagePath) {
            Storage::disk('public')->delete($currentImagePath);

            return null;
        }

        return $currentImagePath;
    }

    protected function nextSortOrder(Builder $query): int
    {
        return ($query->max('sort_order') ?? -1) + 1;
    }
}
