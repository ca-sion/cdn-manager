<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Edition;
use Illuminate\Notifications\Messages\MailMessage;

class ClientAdvertiserMediaMissingRelaunch extends ClientAdvertiserMediaMissing
{
    public string $deadline;

    public function __construct(?string $deadline = null)
    {
        parent::__construct();

        $this->deadline = $deadline ?? self::defaultDeadline();
    }

    public static function defaultDeadline(): string
    {
        return now()->addDays(7)->locale('fr')->translatedFormat('j F');
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $missing = $this->resolveMissingMediaSummary($notifiable);

        if ($missing === null) {
            // If no media is missing, return an empty MailMessage to prevent sending an email.
            return (new MailMessage)->subject('')->line('');
        }

        $currentEditionYear = $missing['year'];
        $provisionElements = $missing['summary'];
        $email = Edition::currentMeta('advertisers_email');
        $signature = Edition::currentMeta('advertisers_signature');

        return (new MailMessage)
            ->subject('🏃‍♂️ Course de Noël et Trail des Châteaux '.$currentEditionYear.' - Visuel manquant ('.$notifiable->name.') : rappel')
            ->replyTo($email)
            ->bcc($email)
            ->greeting('Bonjour,')
            ->line('Sauf erreur de notre part, nous n\'avons pas encore reçu votre ou vos visuels pour la Course de Noël et le Trail des Châteaux '.$currentEditionYear.'. Voici le rappel des spécifications attendues :')
            ->line([$provisionElements])
            ->line('Merci de nous faire parvenir vos visuels **d\'ici au '.$this->deadline.'**, soit via le formulaire habituel en cliquant sur le bouton ci-dessous, soit par retour d\'e-mail à l\'adresse ['.$email.'](mailto:'.$email.').')
            ->action('Ajouter les visuels', $notifiable->frontEditLink)
            ->line('**Important :** sans réponse de votre part d\'ici au '.$this->deadline.', nous utiliserons le visuel de l\'année passée ou, à défaut, votre logo d\'entreprise pour réaliser le visuel.')
            ->line('Si vous avez déjà envoyé les visuels récemment, nous vous prions de nous en excuser. Dans ce cas, merci de nous en faire part.')
            ->line('Merci pour votre soutien et belle journée !')
            ->salutation($signature);
    }
}
