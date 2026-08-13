<?php

namespace App\Notifications;

use App\Models\RunRegistrationElement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

class EliteRunnerFormLink extends Notification
{
    use Queueable;

    public RunRegistrationElement $element;
    public string $signedUrl;
    public string $pdfSignedUrl;

    public function __construct(RunRegistrationElement $element, ?string $signedUrl = null, ?string $pdfSignedUrl = null)
    {
        $this->element = $element;

        $registrationId = $element->run_registration_id ?: 1;

        $this->signedUrl = $signedUrl ?: URL::signedRoute('front.run-registration.edit', [
            'registration' => $registrationId,
        ]);

        $this->pdfSignedUrl = $pdfSignedUrl ?: URL::signedRoute('pdf.elite-contract', [
            'registration' => $registrationId,
        ]);
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
            ->subject('🏃‍♂️ Course de Noël ' . $currentEditionYear . ' - Invitation Élite et fiche coureur (' . $name . ')')
            ->replyTo($managerEmail)
            ->bcc($managerEmail)
            ->greeting('Bonjour ' . ($this->element->first_name ?: $name) . ',')
            ->line('Nous sommes ravis de vous compter parmi les athlètes Élite invités pour la prochaine édition de la Course de Noël à Sion ! Comme chaque année, la Course de Noël rassemble l\'élite de la course à pied dans une ambiance festive au cœur de la ville de Sion.')
            ->line('Afin de nous permettre de finaliser votre inscription et la préparation de votre contrat, nous vous invitons à **vérifier et compléter vos informations personnelles** (date de naissance, nationalité, adresse postale et IBAN pour les primes) :')
            ->action('🏃‍♂️ Compléter ma fiche', $this->signedUrl)
            ->line('Vos éventuels arrangements d\'hébergement et conditions financières restent gérés directement par le responsable de la course Élite.')
            ->line('N\'hésitez pas à répondre à cet e-mail en cas de question.')
            ->salutation('Le responsable Élite');
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
