<?php

namespace App\Livewire\Screens;

use App\Models\Screen;
use App\Models\Sound;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Manage extends Component
{
    use WithFileUploads;

    const IMAGE_RULES = 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:4096';

    const EMOJI_RULES = 'nullable|string|max:8';

    public Screen $screen;

    public string $name = '';

    #[Validate('nullable|file|mimes:mp3,wav,ogg,m4a,aac|max:20480')]
    public $newSound = null;

    #[Validate('nullable|string|max:255')]
    public string $newSoundName = '';

    #[Validate(self::EMOJI_RULES)]
    public string $newSoundEmoji = '';

    #[Validate(self::IMAGE_RULES)]
    public $newSoundImage = null;

    public ?int $editingSoundId = null;

    public string $editName = '';

    public string $editEmoji = '';

    #[Validate(self::IMAGE_RULES)]
    public $editImage = null;

    public bool $editRemoveImage = false;

    public function mount(Screen $screen): void
    {
        $this->authorize('update', $screen);

        $this->screen = $screen;
        $this->name = $screen->name;
    }

    public function updateScreenName(): void
    {
        $this->validate(['name' => Screen::NAME_RULES]);

        $this->screen->update(['name' => $this->name]);
    }

    public function uploadSound(): void
    {
        $this->validate();

        if (! $this->newSound) {
            return;
        }

        $path = $this->newSound->store($this->soundPath(), 'public');
        $imagePath = $this->newSoundImage
            ? $this->newSoundImage->store($this->soundPath('images'), 'public')
            : null;

        $this->screen->sounds()->create([
            'name' => $this->newSoundName !== '' ? $this->newSoundName : pathinfo($this->newSound->getClientOriginalName(), PATHINFO_FILENAME),
            'emoji' => $this->newSoundEmoji !== '' ? $this->newSoundEmoji : null,
            'image_path' => $imagePath,
            'file_path' => $path,
            'sort_order' => ($this->screen->sounds()->max('sort_order') ?? -1) + 1,
        ]);

        $this->reset('newSound', 'newSoundName', 'newSoundEmoji', 'newSoundImage');
    }

    public function deleteSound(Sound $sound): void
    {
        $this->authorize('delete', $sound);

        $sound->delete();
    }

    public function move(Sound $sound, int $direction): void
    {
        $this->authorize('update', $sound);

        abort_unless($sound->screen_id === $this->screen->id, 404);

        $sibling = $this->screen->sounds()
            ->where('sort_order', $direction > 0 ? '>' : '<', $sound->sort_order)
            ->orderBy('sort_order', $direction > 0 ? 'asc' : 'desc')
            ->first();

        if (! $sibling) {
            return;
        }

        [$order, $siblingOrder] = [$sound->sort_order, $sibling->sort_order];
        $sound->update(['sort_order' => $siblingOrder]);
        $sibling->update(['sort_order' => $order]);
    }

    public function startEditSound(Sound $sound): void
    {
        $this->authorize('update', $sound);

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

    public function saveEditSound(): void
    {
        $sound = Sound::findOrFail($this->editingSoundId);
        $this->authorize('update', $sound);

        $this->validate([
            'editName' => Screen::NAME_RULES,
            'editEmoji' => self::EMOJI_RULES,
            'editImage' => self::IMAGE_RULES,
        ]);

        $imagePath = $sound->image_path;

        if ($this->editImage) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            $imagePath = $this->editImage->store($this->soundPath('images'), 'public');
        } elseif ($this->editRemoveImage && $imagePath) {
            Storage::disk('public')->delete($imagePath);
            $imagePath = null;
        }

        $sound->update([
            'name' => $this->editName,
            'emoji' => $this->editEmoji !== '' ? $this->editEmoji : null,
            'image_path' => $imagePath,
        ]);

        $this->cancelEditSound();
    }

    public function render()
    {
        return view('livewire.screens.manage', [
            'sounds' => $this->screen->sounds()->get(),
        ]);
    }

    private function soundPath(string $sub = ''): string
    {
        return 'sounds/' . $this->screen->user_id . ($sub !== '' ? '/' . $sub : '');
    }
}
