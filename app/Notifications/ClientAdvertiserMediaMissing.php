<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ClientAdvertiserMediaMissing extends Notification
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
            ->subject('🏃‍♂️ Course de Noël et Trail des Châteaux 2024 - Visuel manquant ('.$notifiable->name.')')
            ->replyTo('pub@coursedenoel.ch')
            ->bcc('pub@coursedenoel.ch')
            ->greeting('Bonjour,')
            ->line('Sauf erreur de notre part, nous n\'avons pas encore reçu de visuel·s de votre part pour la Course de Noël. Seriez-vous d\'accord de me les faire parvenir selon les spécifications ci-après ?')
            ->line([$provisionElements])
            ->line('Vous pouvez ajouter vos visuels en cliquant sur le lien ci-après :')
            ->action('Ajouter les visuels', $notifiable->frontEditLink)
            ->line('Vous pouvez également simplement envoyer vos visuels par retour d\'email à l\'adresse [pub@coursedenoel.ch](mailto:pub@coursedenoel.ch).')
            ->line('Si vous avez déjà envoyé les visuels il y a quelque temps, je vous prie de m\'en excuser. Dans ce cas, merci de m\'en faire part.')
            ->line('Merci pour votre soutien et belle journée !')
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
