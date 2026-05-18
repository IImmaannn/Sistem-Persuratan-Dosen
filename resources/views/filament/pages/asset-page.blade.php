<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}
        
        <div class="mt-6 flex justify-end">
            <x-filament::button type="submit" size="lg" class="bg-primary-600 hover:bg-primary-500 text-white font-bold px-6 py-2 rounded-lg shadow">
                Simpan Semua Perubahan Dokumen
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>