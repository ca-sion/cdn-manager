<?php

namespace App\Listeners;

use Illuminate\Support\Str;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Finder\SplFileInfo;

class SaveSentMessageToEml
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
    {
        $to = '';

        if ($toAddress = ($event->sent->getOriginalMessage()->getTo()[0] ?? null)) {
            $to = str_replace(['@', '.'], ['_at_', '_'], $toAddress->getAddress()) . '_';
        }

        $subject = $event->sent->getOriginalMessage()->getSubject();
        $date = $event->sent->getOriginalMessage()->getDate() ?? now();
        $dateFormatted = $date->format('YmdHis');

        $fileName = Str::slug($dateFormatted . '_' . $subject . '_' . $to, '_');

        Storage::disk('emails')->put(
            'eml/'.$fileName.'.eml',
            $event->message->toString()
        );

        $this->cleanOldMessages();
    }

    protected function cleanOldMessages(): void
    {
        collect(Storage::disk('emails')->files('eml', true))
            ->each(function($file) {
                if (Storage::disk('emails')->lastModified($file) < now()->subDays(17)->getTimestamp()) {
                    Storage::disk('emails')->delete($file);
                }
            });
    }
}
