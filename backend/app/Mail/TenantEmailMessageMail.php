<?php

namespace App\Mail;

use App\Models\EmailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TenantEmailMessageMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly EmailMessage $emailMessage
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject(
                $this->emailMessage->subject
            )
            ->html(
                nl2br(
                    e(
                        $this->emailMessage->body
                    )
                )
            );
    }
}