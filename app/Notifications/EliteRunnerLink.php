<?php

namespace App\Notifications;

use App\Models\RunRegistrationElement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class EliteRunnerLink extends Notification
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
        $name = trim($this->element->first_name . ' ' . $this->element->last_name);
        $currentEditionYear = now()->format('Y');

        return (new MailMessage)
            ->subject('🏃‍♂️ Course de Noël '.$currentEditionYear.' - Fiche d\'inscription Élite (' . $name . ')')
            ->replyTo($managerEmail)
            ->bcc($managerEmail)
            ->greeting('Bonjour ' . ($this->element->first_name ?: $name) . ',')
            ->line('Nous sommes ravis de vous compter parmi les athlètes Élite de la prochaine édition de la Course de Noël à Sion !')
            ->line('Afin de finaliser votre dossier, nous vous invitons à vérifier et compléter vos informations personnelles (adresse postale, localité et coordonnées bancaires) via votre lien sécurisé :')
            ->action('🏃‍♂️ Compléter ma fiche', $this->signedUrl)
            ->line('Les conditions financières de votre contrat ainsi que vos éventuels arrangements d\'hébergement restent directement enregistrés et gérés par le responsable des courses Élite.')
            ->line('N\'hésitez pas à répondre à cet e-mail si vous avez la moindre question.')
            ->salutation('Le responsable Élite');
    }
}
