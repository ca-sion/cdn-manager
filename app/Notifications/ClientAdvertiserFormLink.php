<?php

namespace App\Notifications;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\URL;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ClientAdvertiserFormLink extends Notification
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
        $signedUrl = URL::signedRoute('advertisers.form.client', ['client' => $this->client->id]);
        $currentEditionYear = now()->format('Y');

        return (new MailMessage)
            ->subject('🏃‍♂️ Course de Noël et Trail des Châteaux '.$currentEditionYear.' - Formulaire annonceur ('.$notifiable->name.')')
            ->replyTo('pub@coursedenoel.ch')
            ->bcc('pub@coursedenoel.ch')
            ->greeting('Cher Partenaire,')
            ->line('Comme vous le savez, la Course de Noël est devenue un événement incontournable du 2ᵉ samedi de décembre à Sion. Cette année, nous organisons la 56ᵉ édition et nous sommes impatients d’accueillir plus de 6000 participant·e·s le 13 décembre 2025. Le 7ᵉ Trail des Châteaux se tiendra le même jour entre les châteaux de notre belle région.')
            ->line('C\'est une opportunité d\'améliorer votre visibilité auprès d\'un large public. Comme les autres années, nous vous proposons :')
            ->line('- Annonces dans l\'encarté du Nouvelliste (tiré à 40\'000 exemplaires) - Banderoles - Visuels sur écran - Packs entreprise - Dons')
            ->line('Si vous souhaitez **soutenir** la Course de Noël et le Trail des Châteaux, nous vous invitons à **remplir le formulaire en ligne** suivant :')
            ->action('Formulaire de commande', $signedUrl)
            ->lineIf($this->previousOrderDetails, 'Détails de votre commande de l\'édition précédente :')
            ->lineIf($this->previousOrderDetails, $this->previousOrderDetails ? '- '.implode(', ', $this->previousOrderDetails) : '- Aucune commande trouvée pour l\'édition précédente.')
            ->line('Vous pouvez aussi nous retourner le formulaire papier au format PDF après l\'avoir rempli à l\'adresse pub@coursedenoel.ch. Vous pouvez le télécharger sous : https://coursedenoel.ch/assets/documents/fo-annonceurs.pdf')
            ->line('Nous restons à disposition en cas de questions ou pour tout complément d\'information.')
            ->salutation('Le Comité de la Course de Noël')
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
