<x-filament-panels::page>
    <form wire:submit="generate">
        {{ $this->form }}

        <div class="mt-6 flex gap-3">
            <x-filament::button
                type="button"
                color="info"
                icon="heroicon-o-eye"
                wire:click="$dispatch('open-modal', { id: 'preview-modal' })"
            >
                Preview Code
            </x-filament::button>

            <x-filament::button
                type="submit"
                color="success"
                icon="heroicon-o-code-bracket"
            >
                Generate Files
            </x-filament::button>
        </div>
    </form>

    <x-filament::modal id="preview-modal" width="7xl">
        <x-slot name="heading">
            Code Preview
        </x-slot>

        <div wire:loading wire:target="preview">
            <div class="flex items-center justify-center p-8">
                <x-filament::loading-indicator class="h-8 w-8" />
                <span class="ml-2">Generating preview...</span>
            </div>
        </div>

        <div wire:loading.remove wire:target="preview">
            @if(isset($previews))
                @include('filament.code-preview', ['previews' => $previews])
            @endif
        </div>
    </x-filament::modal>

    @script
    <script>
        $wire.on('open-modal', (data) => {
            $wire.preview().then(() => {
                // Modal will open automatically due to Alpine.js
            });
        });
    </script>
    @endscript
</x-filament-panels::page>
