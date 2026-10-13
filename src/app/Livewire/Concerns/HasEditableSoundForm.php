<?php

namespace App\Livewire\Concerns;

use App\Models\LibrarySound;
use App\Models\Sound;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;

/**
 * Shared upload/edit form state and file-handling for components that manage
 * a list of Sound-shaped records (Sound or LibrarySound) with a name, emoji,
 * audio file, and optional tile image.
 */
trait HasEditableSoundForm
{
    #[Validate(Sound::MEDIA_RULES)]
    public $newSound = null;

    #[Validate(Sound::NAME_RULES_OPTIONAL)]
    public string $newSoundName = '';

    #[Validate(Sound::EMOJI_RULES)]
    public string $newSoundEmoji = '';

    #[Validate(Sound::IMAGE_RULES)]
    public $newSoundImage = null;

    // '' means "auto": the next preset colour in rotation.
    #[Validate(Sound::COLOR_RULES)]
    public string $newSoundColor = '';

    #[Validate(Sound::COLOR_RULES)]
    public string $editColor = '';

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
        $this->editColor = $sound->color ?? '';
        $this->editImage = null;
        $this->editRemoveImage = false;
        $this->resetValidation();
    }

    public function cancelEditSound(): void
    {
        $this->reset('editingSoundId', 'editName', 'editEmoji', 'editColor', 'editImage', 'editRemoveImage');
        $this->resetValidation();
    }

    protected function uploadedSoundAttributes(string $audioPath, string $imagePath, int $position): array
    {
        $path = $this->newSound->store($audioPath, 'public');
        $storedImagePath = $this->newSoundImage
            ? $this->newSoundImage->store($imagePath, 'public')
            : null;

        return [
            'name' => $this->newSoundName !== '' ? $this->newSoundName : Str::limit(pathinfo($this->newSound->getClientOriginalName(), PATHINFO_FILENAME), 50, ''),
            'color' => $this->newSoundColor !== '' ? $this->newSoundColor : Sound::colorForPosition($position),
            'emoji' => $this->newSoundEmoji !== '' ? $this->newSoundEmoji : null,
            'image_path' => $storedImagePath,
            'file_path' => $path,
        ];
    }

    protected function resetUploadForm(): void
    {
        $this->reset('newSound', 'newSoundName', 'newSoundEmoji', 'newSoundColor', 'newSoundImage');
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
