<x-filament-panels::page>
    <div class="flex flex-col items-center justify-center min-h-[50vh] gap-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            
            <a href="{{ \App\Filament\Resources\PermohonanSuratResource::getUrl('create', ['type' => 'penelitian']) }}" 
               style="background-color: #16a34a;" 
               class="flex items-center justify-center w-64 h-32 text-white font-bold text-xl rounded-xl shadow-lg hover:opacity-90 transition">
                Surat Penelitian
            </a>

            <a href="{{ \App\Filament\Resources\PermohonanSuratResource::getUrl('create', ['type' => 'narasumber']) }}" 
               style="background-color: #2563eb;" 
               class="flex items-center justify-center w-64 h-32 text-white font-bold text-xl rounded-xl shadow-lg hover:opacity-90 transition">
                Surat Narasumber
            </a>

            <a href="{{ \App\Filament\Resources\PermohonanSuratResource::getUrl('create', ['type' => 'penunjang']) }}" 
               style="background-color: #dc2626;" 
               class="flex items-center justify-center w-64 h-32 text-white font-bold text-xl rounded-xl shadow-lg hover:opacity-90 transition">
                Surat Penunjang
            </a>

        </div>
    </div>
</x-filament-panels::page>