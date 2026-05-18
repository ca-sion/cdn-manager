<?php

namespace App\Notifications;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\URL;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ContactDonorFormLink extends Notification
{
    use Queueable;

    public Contact $contact;

    /**
     * Create a new notification instance.
     */
    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
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
        $signedUrl = URL::signedRoute('donors.form.contact', ['contact' => $this->contact->id]);
        $currentEditionYear = now()->format('Y');

        return (new MailMessage)
            ->subject('🏃‍♂️ Course de Noël et Trail des Châteaux '.$currentEditionYear.' - Formulaire donateur ('.$notifiable->name.')')
            ->replyTo('pub@coursedenoel.ch')
            ->bcc('pub@coursedenoel.ch')
            ->greeting('Cher Ami de la Course de Noël et du Trail des Châteaux,')
            ->line('Comme vous le savez, la Course de Noël est devenue un événement incontournable du 2ᵉ samedi de décembre à Sion. Cette année, nous organisons la 57ᵉ édition et nous sommes impatients d’accueillir plus de 6000 participant·e·s le 12 décembre 2026. Le 8ᵉ Trail des Châteaux se tiendra le même jour entre les châteaux de notre belle région. Votre contribution, même modeste, est précieuse. Elle nous permettra de continuer à offrir un événement de qualité.')
            // ->line('En remerciement de votre générosité, votre nom sera mentionné dans l\'encarté du Nouvelliste, distribué à 40 000 exemplaires dans les districts de Sion, d’Hérens et de Conthey le x décembre. C\'est une belle opportunité de montrer votre engagement auprès de la communauté. Mention anonyme possible.')
            ->line('Si vous souhaitez **soutenir** la Course de Noël et le Trail des Châteaux, nous vous invitons à **remplir le formulaire en ligne** suivant :')
            ->action('Formulaire de don', $signedUrl)
            ->line('Nous restons à disposition en cas de questions ou pour tout complément d\'information.')
            ->line('Avec nos remerciements anticipés pour votre généreux soutien,')
            ->salutation('Le Comité d\organisation');
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
