<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ClientSendInvoice extends Notification
{
    use Queueable;

    public $invoice;

    /**
     * Create a new notification instance.
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $editionYear = $this->invoice->edition?->year;

        $provisionElements = '';
        foreach ($notifiable->currentProvisionElements->where('provision.format_indicator', '!=', null) as $pe) {
            $provisionElements .= ($pe->provision->description ? '## '.$pe->provision->description : '## '.$pe->provision->name)."\n";
            $provisionElements .= $pe->provision->dimensions_indicator ? '- Dimensions : '.$pe->provision->dimensions_indicator."\n" : null;
            $provisionElements .= $pe->provision->format_indicator ? '- Format : '.$pe->provision->format_indicator."\n" : null;
            $provisionElements .= $pe->provision->contact_indicator ? '- A transmettre à : '.$pe->provision->contact_indicator."\n" : null;
            $provisionElements .= $pe->provision->due_date_indicator ? '- Délai : '.$pe->provision->due_date_indicator."\n" : null;
            $provisionElements .= $pe->textual_indicator ? '- Mention  : '.$pe->textual_indicator."\n" : null;
            $provisionElements .= "\n";
        }

        return (new MailMessage)
            ->subject('Course de Noël '.$editionYear.' - Facture (F'.$this->invoice->number.')')
            ->replyTo('info@coursedenoel.ch')
            ->bcc('info@coursedenoel.ch')
            ->greeting('Bonjour,')
            ->line('Selon notre partenariat, nous vous adressons la présente facture pour le versement du montant convenu. Pour la visionner, cliquer sur le bouton ci-après.')
            ->action('Visionner la facture', $this->invoice->link)
            ->line('Vous pouvez la payer d\'ici au '.Carbon::parse($this->invoice->due_date)->locale('fr_CH')->isoFormat('L').' en utilisant le bulletin de versement (QR-facture) attaché.')
            ->line('Nous vous remercions chaleureusement pour votre généreux soutien.')
            ->line(new HtmlString('Je reste à votre disposition pour toutes questions ou remarques.<br>Meilleures salutations'))
            ->salutation('Michael Ravedoni, Administration');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
