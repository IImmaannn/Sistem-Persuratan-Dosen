<?php

// namespace App\App\Filament\Pages;
namespace App\Filament\Pages;

use App\Models\Config;
use App\Models\SystemAsset;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Set;
use Illuminate\Support\Str;

class AssetPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationLabel = 'Asset';
    protected static ?string $title = 'Asset System';
    protected static ?string $slug = 'asset';
    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.asset-page';

    public ?array $data = [];

    /**
     * 1. LOAD DATA AWAL DENGAN SUNTIKAN UUID (SOLUSI ANTI-LOOP)
     */
    public function mount(): void
    {
        // PENTING: Kita petakan array-nya menggunakan Str::uuid() sebagai KEY parent array 
        // agar Livewire memiliki jangkar identitas yang permanen saat upload file
        $templates = Config::whereIn('kategori', ['jenis_surat', 'jenis_penelitian'])
            ->whereNotNull('template_path')
            ->get()
            ->mapWithKeys(fn($item) => [
                (string) Str::uuid() => [
                    'config_id' => $item->id,
                    'template_path' => $item->template_path,
                ]
            ])
            ->toArray();

        $images = SystemAsset::all()->mapWithKeys(fn($item) => [
            (string) Str::uuid() => [
                'nama_aset' => $item->nama_aset,
                'key_aset' => $item->key_aset,
                'file_path' => $item->file_path,
            ]
        ])->toArray();

        $this->form->fill([
            'templates' => $templates,
            'images' => $images,
        ]);
    }

    /**
     * 2. RANCANGAN FORM GRID CARD
     */
    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                // === BLOK ATAS: TEMPLATE SURAT ===
                Section::make('Template Surat')
                    ->description('Kelola file template dokumen Microsoft Word (.docx) secara dinamis per jenis permohonan surat.')
                    ->schema([
                        Repeater::make('templates')
                            ->label('Daftar Template Dokumen')
                            ->createItemButtonLabel('Tambah Template Dokumen Baru')
                            ->grid(3)
                            ->schema([
                                Select::make('config_id')
                                    ->label('Pilih Jenis Surat')
                                    ->options(function () {
                                        return Config::whereIn('kategori', ['jenis_surat', 'jenis_penelitian'])
                                            ->pluck('value', 'id');
                                    })
                                    ->required()
                                    ->searchable(),

                                FileUpload::make('template_path')
                                    ->label('File .docx')
                                    ->directory('templates')
                                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                                    ->helperText('Format wajib .docx Word')
                                    ->required(),
                            ])
                    ]),

                // === BLOK BAWAH: ASSET GAMBAR ===
                Section::make('Asset Gambar')
                    ->description('Kelola file gambar resmi penunjang berkas seperti TTD Dekan, Cap Fakultas, atau Logo Instansi.')
                    ->schema([
                        Repeater::make('images')
                            ->label('Daftar Komponen Gambar')
                            ->createItemButtonLabel('Tambah Asset Gambar Baru')
                            ->grid(3)
                            ->schema([
                                TextInput::make('nama_aset')
                                    ->label('Nama Komponen')
                                    ->placeholder('Contoh: ttd dekan')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        $set('key_aset', Str::snake($state));
                                    }),

                                TextInput::make('key_aset')
                                    ->label('Key Sistem (Otomatis)')
                                    ->required()
                                    ->alphaDash()
                                    ->readonly()
                                    ->helperText('Kunci jangkar script Word'),

                                FileUpload::make('file_path')
                                    ->label('File .png Transparan')
                                    // ->image() 
                                    ->directory('system-assets')
                                    ->acceptedFileTypes(['image/png']) // Tetap kita kunci wajib .png transparan
                                    ->maxSize(2048)
                                    ->helperText('Max 2MB')
                                    ->required(),
                            ])
                    ]),
            ]);
    }

    /**
     * 3. PROSES SIMPAN MASSAL JALUR GANDA
     */
    public function save(): void
    {
        $state = $this->form->getState();

        // --- PROSES SIMPAN BLOK TEMPLATE (.docx) ---
        Config::whereIn('kategori', ['jenis_surat', 'jenis_penelitian'])->update(['template_path' => null]);

        if (!empty($state['templates'])) {
            foreach ($state['templates'] as $template) {
                Config::where('id', $template['config_id'])->update([
                    'template_path' => $template['template_path'],
                ]);
            }
        }

        // --- PROSES SIMPAN BLOK GAMBAR (.png) ---
        $keepImageKeys = [];
        if (!empty($state['images'])) {
            foreach ($state['images'] as $image) {
                $key = $image['key_aset'] ?? Str::snake($image['nama_aset']);
                SystemAsset::updateOrCreate(
                    ['key_aset' => $key],
                    [
                        'nama_aset' => $image['nama_aset'],
                        'file_path' => $image['file_path'],
                    ]
                );
                $keepImageKeys[] = $key;
            }
        }
        SystemAsset::whereNotIn('key_aset', $keepImageKeys)->delete();

        Notification::make()
            ->title('Semua Komponen Dokumen Berhasil Diperbarui!')
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()->role === 'Admin';
    }
}