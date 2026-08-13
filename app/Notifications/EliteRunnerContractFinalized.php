<?php

namespace App\Notifications;

use App\Models\RunRegistrationElement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

class EliteRunnerContractFinalized extends Notification
{
    use Queueable;

    public RunRegistrationElement $element;
    public string $pdfSignedUrl;
    public string $editSignedUrl;

    public function __construct(RunRegistrationElement $element, ?string $pdfSignedUrl = null, ?string $editSignedUrl = null)
    {
        $this->element = $element;

        $registrationId = $element->run_registration_id ?: 1;

        $this->pdfSignedUrl = $pdfSignedUrl ?: URL::signedRoute('pdf.elite-contract', [
            'registration' => $registrationId,
        ]);

        $this->editSignedUrl = $editSignedUrl ?: URL::signedRoute('front.run-registration.edit', [
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

        $mail = (new MailMessage)
            ->subject('🏃‍♂️ Course de Noël ' . $currentEditionYear . ' - Confirmation course Élite et contrat (' . $name . ')')
            ->replyTo($managerEmail)
            ->bcc($managerEmail)
            ->greeting('Bonjour ' . ($this->element->first_name ?: $name) . ',')
            ->line('Nous avons le plaisir de vous confirmer la finalisation de votre contrat d\'engagement Élite pour la ' . $currentEditionYear . 'ᵉ édition de la Course de Noël à Sion ! Votre dossier et vos conditions d\'engagement ont été validés par le responsable des courses Élite.')
            ->action('📄 Télécharger le Contrat (PDF)', $this->pdfSignedUrl)
            ->line('Vous pouvez à tout moment consulter ou mettre à jour vos coordonnées personnelles et votre IBAN via votre fiche en ligne :')
            ->line(new HtmlString('<a href="'.$this->editSignedUrl.'">'.$this->editSignedUrl.'</a>'))
            ->line('Nous restons à votre entière disposition pour toute question d\'ici le jour de la course.')
            ->salutation('Le responsable Élite');

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
