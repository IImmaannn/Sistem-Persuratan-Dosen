<?php

namespace App\Mail;

use App\Models\PermohonanSurat;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class KirimSuratDosen extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PermohonanSurat $surat // Kita lempar data surat ke sini
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Surat Tugas Resmi: ' . $this->surat->config->value,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.surat-dosen', // Kita bikin view-nya nanti
        );
    }

    public function attachments(): array
    {
        // NARIK PDF YANG BARU KITA GENERATE TADI
        return [
            Attachment::fromStorageDisk('public', $this->surat->file_surat_selesai)
                ->as('Surat_Tugas_Resmi.docx')
                ->withMime('application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ];
    }
}