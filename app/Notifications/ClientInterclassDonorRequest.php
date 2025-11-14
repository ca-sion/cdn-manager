<?php

namespace App\Notifications;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\URL;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ClientInterclassDonorRequest extends Notification
{
    use Queueable;

    public Client $client;

    public $previousOrderDetails;

    /**
     * Create a new notification instance.
     */
    public function __construct(Client $client, $previousOrderDetails = null)
    {
        $this->client = $client;
        $this->previousOrderDetails = $previousOrderDetails;
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
        // $signedUrl = URL::signedRoute('advertisers.form.client', ['client' => $this->client->id]);
        $currentEditionYear = now()->format('Y');

        return (new MailMessage)
            ->subject('🏃‍♂️ Course de Noël '.$currentEditionYear.' - Concours interclasses : demande pour prix spéciaux ('.$notifiable->name.')')
            ->replyTo('pub@coursedenoel.ch')
            ->bcc('pub@coursedenoel.ch')
            ->greeting('Madame, Monsieur,')
            ->line('Le samedi 13 décembre 2025 marquera un événement exceptionnel à Sion : la **Course de Noël**, qui verra plus de 6\'000 coureurs s\'élancer au cœur de la ville de Sion.')
            ->line('Dans ce cadre, le « **Concours interclasses** » fêtera son jubilé, sa 25e édition, et permettra à nouveau à **plus de 1\'000 élèves** du degré primaire de découvrir le plaisir de courir avec leurs camarades de classe et de donner le meilleur d’eux-mêmes pour renforcer leur esprit d’équipe !')
            ->line('Vous avez été un soutien fidèle par le passé ou partagez nos valeurs de promotion du sport jeunesse. C’est pourquoi nous nous permettons de vous solliciter afin de pouvoir primer les meilleures classes de chacune des 6 catégories.')
            ->line('Nous vous serions ainsi très reconnaissants si vous pouviez **offrir aux participant·e·s des bons/entrées** et ce jusqu\'à 25 élèves. Votre généreux geste permettrait à ces enfants et à leurs familles de passer ensemble un moment inoubliable.')
            ->line('En contrepartie, nous :')
            ->line('- mentionnons votre entreprise sur notre site web;')
            ->line('- publions un post de remerciement sur nos différents réseaux sociaux;')
            ->line('- vous permettons de vous faire connaître auprès d\'un large public de plus de 1\'000 élèves et parents d\'élèves.')

            // ->action('Formulaire de commande', $signedUrl)

            ->line('Pour confirmer votre soutien ou si vous avez des questions, nous vous invitons à répondre par retour d\'email :')
            ->line('- Adresse : pub@coursedenoel.ch')
            ->line('- Délai : 10 novembre 2025')
            ->line('Les lots physiques sont à envoyer à :')
            ->line(new HtmlString('<small>Christian Masserey<br>Chemin des Amandiers 108<br>1950 Sion<br>079 453 60 03<br>christian.masserey@bluewin.ch</small>'))

            ->lineIf($this->previousOrderDetails, 'Votre prix de l\'édition précédente :')
            ->lineIf($this->previousOrderDetails, $this->previousOrderDetails ? '- '.implode(', ', $this->previousOrderDetails) : '- Aucune commande trouvée pour l\'édition précédente.')

            ->line('Nous vous remercions infiniment d\'avance pour votre précieuse aide, au nom de tous les élèves et du Comité d\'organisation.')
            ->salutation(new HtmlString('Christian Masserey<br>Responsable du Concours interclasses'))
            ->line(new HtmlString('<img src="'.$notifiable->currentEngagement?->tracker.'" width="1" height="1" />'));
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
