<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Support\HtmlString;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ClientAdvertiserFormCreated extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
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
        $provisionElements = '';
        foreach ($notifiable->currentProvisionElements as $pe) {
            $provisionElements .= ($pe->provision->description ? '## '.$pe->provision->description : '## '.$pe->provision->name).' - '.$pe->price->amount('c')."\n" ;
            $provisionElements .= $pe->provision->dimensions_indicator ? '- Dimensions : '.$pe->provision->dimensions_indicator."\n" : null ;
            $provisionElements .= $pe->provision->format_indicator ? '- Format : '.$pe->provision->format_indicator."\n" : null ;
            $provisionElements .= $pe->provision->contact_indicator ? '- A transmettre à : '.$pe->provision->contact_indicator."\n" : null ;
            $provisionElements .= $pe->provision->due_date_indicator ? '- Délai : '.$pe->provision->due_date_indicator."\n" : null ;
            $provisionElements .= $pe->textual_indicator ? '- Mention  : '.$pe->textual_indicator."\n" : null ;
            $provisionElements .= "\n";
        }

        return (new MailMessage)
                    ->subject('CDN - Commande annonceur effectuée ('.$notifiable->name.')')
                    ->cc('info@coursedenoel.ch', 'Course de Noël')
                    ->greeting($notifiable->name.',')
                    ->line('Vous venez de passer une commande pour une prestation en tant qu\'annonceur pour la Course de Noël. Nous vous remercions beaucoup pour votre soutien.')
                    ->line('En cliquant sur le bouton ci-dessous, vous pouvez voir la commande passée.')
                    ->action('Voir la commande', $notifiable->pdfLink)
                    ->line('Vous pouvez déjà ajouter vos visuels ou modifier votre commande en cliquant sur le lien ci-après : **[Modifier votre commande]('.$notifiable->frontEditLink.')**')
                    ->line('Éléments et indications liés à votre commande :')
                    ->line([$provisionElements])
                    ->lineIf($notifiable->note, '**Note** : '.$notifiable->note)
                    ->line('Nous restons à disposition en cas de questions ou pour tout complément d\'information.')
                    ->salutation('Le Comité de la Course de Noël');
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
