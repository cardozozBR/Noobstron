<?php

namespace App\Mail;

use App\Models\Proposal;
use App\Support\TenantMoneyFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProposalMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Proposal $proposal
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __(
                'proposals.email_subject',
                [
                    'number' => $this->proposal->number,
                ]
            )
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.proposals.sent',
            with: [
                'proposal' => $this->proposal,
            ]
        );
    }

    public function attachments(): array
    {
        $proposal = $this->proposal;

        $filename = 'proposal-'
            . preg_replace(
                '/[^A-Za-z0-9._-]+/',
                '-',
                $proposal->number
            )
            . '.pdf';

        return [
            Attachment::fromData(
                function () use ($proposal): string {
                    return Pdf::loadView(
                        'proposals.pdf',
                        [
                            'proposal' => $proposal,
                        ]
                    )
                        ->setPaper(
                            'a4',
                            'portrait'
                        )
                        ->output();
                },
                $filename
            )->withMime(
                'application/pdf'
            ),
        ];
    }
}
