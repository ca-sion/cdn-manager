<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use App\Models\RunRegistration;
use Illuminate\Support\Facades\URL;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class RunRegistrationLink extends Notification
{
    use Queueable;

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
    public function toMail(RunRegistration $notifiable): MailMessage
    {
        // On génère une URL signée. La route sera définie en Phase 4.
        // On utilise un try catch ou on s'assure que la route existe pour le test.
        $url = 'Placeholder URL for '.$notifiable->type->value;
        try {
            $url = URL::signedRoute('front.run-registration.edit', [
                'registration' => $notifiable->id,
                'type'         => $notifiable->type->value,
            ]);
        } catch (\Exception $e) {
            // Fallback pour le test si la route n'existe pas encore
        }

        return (new MailMessage)
            ->subject('Course de Noël - Lien d\'édition de votre inscription')
            ->greeting('Bonjour '.$notifiable->contact_first_name.',')
            ->line('Vous avez créé un dossier d\'inscription pour la Course de Noël.')
            ->line('Vous pouvez accéder à tout moment à votre dossier pour ajouter ou modifier des participants via le lien sécurisé ci-dessous.')
            ->action('Accéder à mon inscription', $url)
            ->line('Nous vous remercions pour votre participation !');
    }
}
