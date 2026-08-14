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
        $url = URL::signedRoute('front.run-registration.edit', [
            'registration' => $notifiable->id,
        ]);

        $dossierName = $notifiable->display_name;
        $currentEditionYear = now()->format('Y');

        return (new MailMessage)
            ->subject('🏃‍♂️ Course de Noël '.$currentEditionYear.' - Votre dossier d\'inscription ('.$dossierName.')')
            ->replyTo('inscriptions@coursedenoel.ch')
            ->greeting('Bonjour '.($notifiable->contact_first_name ?: 'Bonjour').',')
            ->line('Merci pour votre inscription et votre intérêt pour la Course de Noël !')
            ->line('Votre dossier pour **'.$dossierName.'** a bien été enregistré. Vous pouvez accéder à tout moment à votre espace personnel pour ajouter, modifier ou mettre à jour la liste de vos participants via le lien sécurisé ci-dessous :')
            ->action('📋 Gérer mon dossier d\'inscription', $url)
            ->line('Ce lien d\'accès permanent reste valide durant toute la période des inscriptions. Nous vous conseillons de conserver cet e-mail pour y accéder facilement ultérieurement.')
            ->line('Au plaisir de vous accueillir le jour de la course !')
            ->salutation('Le Comité d\'organisation');
    }
}
