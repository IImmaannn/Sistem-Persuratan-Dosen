<?php

namespace App\Filament\Resources\PersetujuanSuratResource\Pages;

use App\Filament\Resources\PersetujuanSuratResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditPersetujuanSurat extends EditRecord
{
    protected static string $resource = PersetujuanSuratResource::class;

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getFormActions(): array
    {
        return [
            // TOMBOL SETUJU
            Actions\Action::make('setujui')
                ->label('Setujui')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->action(function () {
                    $record = $this->record;
                    $user = auth()->user();
                    
                    $nextStatus = match ($user->role) {
                        'Supervisor' => 'Disetujui_Supervisor',
                        'Manager' => 'Disetujui_Manager',
                        'Wakil_Dekan' => 'Disetujui_Wakil_Dekan',
                        'Dekan' => 'Selesai_Pimpinan',
                        default => $record->status_terakhir,
                    };

                    // Catat Log
                    \App\Models\LogPersetujuan::create([
                        'permohonan_id' => $record->id,
                        'pimpinan_id'   => $user->id,
                        'status_aksi'   => $nextStatus,
                        'catatan'       => 'Disetujui oleh ' . $user->role . ' melalui halaman detail.',
                    ]);

                    // Update Status
                    $record->update(['status_terakhir' => $nextStatus]);

                    Notification::make()->title('Surat Berhasil Disetujui')->success()->send();

                    // Redirect balik ke daftar surat
                    return redirect($this->getResource()::getUrl('index'));
                }),

            // TOMBOL TOLAK
            Actions\Action::make('tolak')
                ->label('Tolak')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->form([
                    \Filament\Forms\Components\Textarea::make('catatan')
                        ->label('Alasan Penolakan')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $record = $this->record;

                    // Catat Log Penolakan
                    \App\Models\LogPersetujuan::create([
                        'permohonan_id' => $record->id,
                        'pimpinan_id'   => auth()->id(),
                        'status_aksi'   => 'Revisi',
                        'catatan'       => $data['catatan'],
                    ]);

                    $record->update(['status_terakhir' => 'Revisi OCS']);

                    Notification::make()->title('Surat Dikembalikan ke OCS')->danger()->send();

                    return redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }

}
