<?php

namespace App\Notifications;

use App\Models\RunRegistrationElement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class EliteRunnerLinkNotification extends Notification
{
    use Queueable;

    public RunRegistrationElement $element;
    public string $signedUrl;

    public function __construct(RunRegistrationElement $element, string $signedUrl)
    {
        $this->element = $element;
        $this->signedUrl = $signedUrl;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $managerEmail = setting('elite_manager_email', config('mail.from.address', 'elites@coursedenoel.ch'));

        return (new MailMessage)
            ->replyTo($managerEmail)
            ->subject('Votre fiche d\'inscription Élite — Course de Noël Sion')
            ->greeting('Bonjour ' . $this->element->first_name . ' ' . $this->element->last_name . ',')
            ->line('Voici votre lien d\'accès personnel et sécurisé pour consulter votre dossier Élite et mettre à jour vos coordonnées personnelles (adresse postale, localité et IBAN de versement de vos primes) :')
            ->action('Accéder à ma fiche Élite', $this->signedUrl)
            ->line('Les conditions financières du contrat Élite et les arrangements d\'hébergement sont enregistrés et gérés par le responsable des courses Élite.')
            ->line('Au plaisir de vous accueillir à Sion !');
    }
}
