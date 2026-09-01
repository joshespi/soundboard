<?php

namespace App\Livewire\Screens;

use App\Models\LibrarySound;
use App\Models\Screen;
use App\Models\Sound;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Manage extends Component
{
    use WithFileUploads;

    public Screen $screen;

    public string $name = '';

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

    public string $librarySearch = '';

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

    public function addFromLibrary(LibrarySound $librarySound): void
    {
        $path = $this->copyIntoScreen($librarySound->file_path, $this->soundPath());
        $imagePath = $librarySound->image_path
            ? $this->copyIntoScreen($librarySound->image_path, $this->soundPath('images'))
            : null;

        $this->screen->sounds()->create([
            'name' => $librarySound->name,
            'emoji' => $librarySound->emoji,
            'color' => $librarySound->color,
            'image_path' => $imagePath,
            'file_path' => $path,
            'sort_order' => ($this->screen->sounds()->max('sort_order') ?? -1) + 1,
        ]);
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
            'editEmoji' => Sound::EMOJI_RULES,
            'editImage' => Sound::IMAGE_RULES,
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
            'librarySounds' => LibrarySound::query()
                ->when($this->librarySearch !== '', fn ($query) => $query->where('name', 'like', '%'.$this->librarySearch.'%'))
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    private function soundPath(string $sub = ''): string
    {
        return 'sounds/'.$this->screen->user_id.($sub !== '' ? '/'.$sub : '');
    }

    private function copyIntoScreen(string $sourcePath, string $directory): string
    {
        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $path = $directory.'/'.Str::random(40).'.'.$extension;
        Storage::disk('public')->copy($sourcePath, $path);

        return $path;
    }
}
