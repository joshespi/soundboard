<?php

namespace App\Livewire\Screens;

use App\Livewire\Concerns\HasEditableSoundForm;
use App\Models\LibrarySound;
use App\Models\Screen;
use App\Models\Sound;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Manage extends Component
{
    use HasEditableSoundForm, WithFileUploads;

    public Screen $screen;

    public string $name = '';

    public string $librarySearch = '';

    public function mount(Screen $screen): void
    {
        $this->authorize('update', $screen);

        $this->screen = $screen;
        $this->name = $screen->name;
    }

    public function updateScreenName(): void
    {
        $this->authorize('update', $this->screen);

        $this->validate(['name' => Screen::NAME_RULES]);

        $this->screen->update(['name' => $this->name]);
    }

    public function uploadSound(): void
    {
        $this->authorize('update', $this->screen);

        $this->validate();

        if (! $this->newSound) {
            return;
        }

        $this->screen->sounds()->create($this->uploadedSoundAttributes($this->soundPath(), $this->soundPath('images')) + [
            'sort_order' => $this->nextSortOrder($this->screen->sounds()),
        ]);

        $this->resetUploadForm();
    }

    public function deleteSound(Sound $sound): void
    {
        $this->authorize('delete', $sound);

        $sound->delete();
    }

    public function addFromLibrary(LibrarySound $librarySound): void
    {
        $this->authorize('update', $this->screen);

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
            'sort_order' => $this->nextSortOrder($this->screen->sounds()),
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

        DB::transaction(function () use ($sound, $sibling, $order, $siblingOrder) {
            $sound->update(['sort_order' => $siblingOrder]);
            $sibling->update(['sort_order' => $order]);
        });
    }

    public function startEditSound(Sound $sound): void
    {
        $this->authorize('update', $sound);

        $this->beginEditingSound($sound);
    }

    public function saveEditSound(): void
    {
        $sound = Sound::findOrFail($this->editingSoundId);
        $this->authorize('update', $sound);

        $this->validate([
            'editName' => Sound::NAME_RULES,
            'editEmoji' => Sound::EMOJI_RULES,
            'editImage' => Sound::IMAGE_RULES,
        ]);

        $sound->update([
            'name' => $this->editName,
            'emoji' => $this->editEmoji !== '' ? $this->editEmoji : null,
            'image_path' => $this->updatedImagePath($sound->image_path, $this->soundPath('images')),
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
        return Sound::storagePathFor($this->screen->user_id, $sub);
    }

    private function copyIntoScreen(string $sourcePath, string $directory): string
    {
        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $path = $directory.'/'.Str::random(40).'.'.$extension;
        Storage::disk('public')->copy($sourcePath, $path);

        return $path;
    }
}
