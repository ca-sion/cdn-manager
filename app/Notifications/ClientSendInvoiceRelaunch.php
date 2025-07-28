<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ClientSendInvoiceRelaunch extends Notification
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

            $currentEditionYear = $pe->edition?->year;
        }

        $invoiceDate = Carbon::parse($this->invoice->date)->locale('fr_CH')->isoFormat('L');
        $invoiceNumber = $this->invoice->number;

        return (new MailMessage)
            ->subject('Course de Noël '.$editionYear.' - Facture (F'.$invoiceNumber.') : rappel')
            ->replyTo('info@coursedenoel.ch')
            ->bcc('info@coursedenoel.ch')
            ->greeting('Bonjour,')
            ->line('Sauf erreur de notre part, le paiement de la facture F'.$invoiceNumber.' du '.$invoiceDate.' ne nous est pas parvenu.')
            ->action('Visionner la facture', $this->invoice->link)
            ->line('Nous vous remercions de régler le montant ouvert dans les prochains jours. N\'hésitez pas à nous contacter en cas de questions à ce sujet. Il est possible que votre paiement se soit croisé avec ce rappel. Dans ce cas, veuillez ignorer ce message.')
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
