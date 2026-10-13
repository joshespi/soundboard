<?php

namespace App\Livewire\Screens;

use App\Livewire\Concerns\HasEditableSoundForm;
use App\Models\LibrarySound;
use App\Models\Screen;
use App\Models\Sound;
use App\Services\MediaConverter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Manage extends Component
{
    use HasEditableSoundForm, WithFileUploads;

    public Screen $screen;

    public string $name = '';

    public string $librarySearch = '';

    public bool $isPublic = false;

    public ?int $trimmingSoundId = null;

    #[Validate(Sound::IMAGE_RULES)]
    public $screenImage = null;

    public function mount(Screen $screen): void
    {
        $this->authorize('update', $screen);

        $this->screen = $screen;
        $this->name = $screen->name;
        $this->isPublic = $screen->is_public;
    }

    public function updatedIsPublic(): void
    {
        $this->authorize('update', $this->screen);

        $this->screen->update(['is_public' => $this->isPublic]);
    }

    public function updateScreenName(): void
    {
        $this->authorize('update', $this->screen);

        $this->validate(['name' => Screen::NAME_RULES]);

        $this->screen->update(['name' => $this->name]);
    }

    // The screen's home-screen icon: saved as soon as a file is picked.
    public function updatedScreenImage(): void
    {
        $this->authorize('update', $this->screen);

        $this->validateOnly('screenImage');

        if (! $this->screenImage) {
            return;
        }

        $this->replaceScreenImage($this->screenImage->store($this->soundPath('screens'), 'public'));
        $this->reset('screenImage');
    }

    public function removeScreenImage(): void
    {
        $this->authorize('update', $this->screen);

        $this->replaceScreenImage(null);
    }

    public function uploadSound(): void
    {
        $this->authorize('update', $this->screen);

        $this->validate();

        if (! $this->newSound) {
            return;
        }

        $order = $this->nextSortOrder($this->screen->sounds());

        $this->screen->sounds()->create($this->uploadedSoundAttributes($this->soundPath(), $this->soundPath('images'), $order) + [
            'sort_order' => $order,
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

    // Drag-and-drop target (x-sort): moves one sound to a zero-based position and renumbers the rest.
    public function reorder(int $soundId, int $position): void
    {
        $this->authorize('update', $this->screen);

        $ids = $this->screen->sounds()->pluck('id')->all();

        abort_unless(in_array($soundId, $ids, true), 404);

        $ids = array_values(array_diff($ids, [$soundId]));
        array_splice($ids, max(0, min($position, count($ids))), 0, [$soundId]);

        DB::transaction(function () use ($ids) {
            foreach ($ids as $order => $id) {
                $this->screen->sounds()->whereKey($id)->update(['sort_order' => $order]);
            }
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
            'editColor' => Sound::COLOR_RULES,
            'editImage' => Sound::IMAGE_RULES,
        ]);

        $sound->update([
            'name' => $this->editName,
            'emoji' => $this->editEmoji !== '' ? $this->editEmoji : null,
            'color' => $this->editColor ?: $sound->color,
            'image_path' => $this->updatedImagePath($sound->image_path, $this->soundPath('images')),
        ]);

        $this->cancelEditSound();
    }

    public function startTrim(int $soundId): void
    {
        $this->trimmingSoundId = $this->findSound($soundId)->id;
        $this->resetErrorBag('trim');
    }

    public function cancelTrim(): void
    {
        $this->reset('trimmingSoundId');
        $this->resetErrorBag('trim');
    }

    // Cuts the sound to [$start, $end] seconds and/or drops its video, via ffmpeg on the server.
    public function trimSound(MediaConverter $converter, float $start, float $end, bool $audioOnly, bool $asNew): void
    {
        $sound = $this->findSound($this->trimmingSoundId);

        $disk = Storage::disk('public');
        $input = $disk->path($sound->file_path);
        $duration = $converter->duration($input);

        if ($duration === null) {
            $this->addError('trim', __('Could not read that file.'));

            return;
        }

        $stripVideo = $audioOnly && $sound->is_video;
        $start = max(0.0, $start);
        $end = min($end, $duration);
        $isWholeFile = $start < 0.05 && $end > $duration - 0.05;

        if ($end - $start < 0.1) {
            $this->addError('trim', __('Pick a longer range.'));

            return;
        }

        if ($isWholeFile && ! $stripVideo) {
            $this->addError('trim', __('Nothing to change — move a handle or choose audio only.'));

            return;
        }

        $keepVideo = $sound->is_video && ! $stripVideo;
        $newPath = $this->newStoragePath($this->soundPath(), $keepVideo ? 'mp4' : 'm4a');
        $disk->makeDirectory(dirname($newPath));
        set_time_limit(MediaConverter::TIMEOUT_SECONDS);

        try {
            $converter->convert($input, $disk->path($newPath), $isWholeFile ? 0.0 : $start, $isWholeFile ? null : $end, $keepVideo);
        } catch (\RuntimeException $e) {
            $disk->delete($newPath);
            $this->addError('trim', __('Conversion failed: :reason', ['reason' => Str::limit($e->getMessage(), 200)]));

            return;
        }

        if ($asNew) {
            $this->screen->sounds()->create([
                'name' => Str::limit($sound->name, 40, '').($stripVideo ? ' (audio)' : ' (clip)'),
                'emoji' => $sound->emoji,
                'color' => $sound->color,
                'image_path' => $sound->image_path
                    ? $this->copyIntoScreen($sound->image_path, $this->soundPath('images'))
                    : null,
                'file_path' => $newPath,
                'sort_order' => $this->nextSortOrder($this->screen->sounds()),
            ]);
        } else {
            $old = $sound->file_path;
            $sound->update(['file_path' => $newPath]);
            $disk->delete($old);
        }

        $this->cancelTrim();
    }

    public function render()
    {
        $sounds = $this->screen->sounds()->get();

        return view('livewire.screens.manage', [
            'trimmingSound' => $this->trimmingSoundId ? $sounds->firstWhere('id', $this->trimmingSoundId) : null,
            'sounds' => $sounds,
            'librarySounds' => LibrarySound::query()
                ->when($this->librarySearch !== '', fn ($query) => $query->where('name', 'like', '%'.addcslashes($this->librarySearch, '%_\\').'%'))
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    private function soundPath(string $sub = ''): string
    {
        return Sound::storagePathFor($this->screen->user_id, $sub);
    }

    private function replaceScreenImage(?string $newPath): void
    {
        $old = $this->screen->image_path;

        $this->screen->update(['image_path' => $newPath]);

        if ($old) {
            Storage::disk('public')->delete($old);
        }
    }

    private function copyIntoScreen(string $sourcePath, string $directory): string
    {
        $path = $this->newStoragePath($directory, pathinfo($sourcePath, PATHINFO_EXTENSION));
        Storage::disk('public')->copy($sourcePath, $path);

        return $path;
    }

    private function newStoragePath(string $directory, string $extension): string
    {
        return $directory.'/'.Str::random(40).'.'.$extension;
    }

    // A sound on this screen the user may edit; 404s for other screens' sounds.
    private function findSound(?int $id): Sound
    {
        $sound = $this->screen->sounds()->findOrFail($id);
        $this->authorize('update', $sound);

        return $sound;
    }
}
