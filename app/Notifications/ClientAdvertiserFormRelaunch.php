<?php

namespace App\Notifications;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\URL;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ClientAdvertiserFormRelaunch extends Notification
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
            ->subject('🏃‍♂️ Course de Noël et Trail des Châteaux '.$currentEditionYear.' - Formulaire annonceur ('.$notifiable->name.') : rappel')
            ->replyTo('pub@coursedenoel.ch')
            ->bcc('pub@coursedenoel.ch')
            ->greeting('Cher Partenaire,')
            ->line('Sauf erreur de notre part, il semble que nous n\'ayons pas encore reçu votre formulaire de sponsoring pour l\'édition '.$currentEditionYear.' de la Course de Noël et du Trail des Châteaux. Ce n\'est pas grave, la date limite est encore lointaine !')
            ->line('Pour rappel, cet événement qui accueillera plus de 6000 participant·e·s est une superbe opportunité de visibilité.')
            ->line('Si vous souhaitez renouveler votre soutien ou devenir partenaire, vous pouvez remplir le formulaire en ligne via le lien ci-dessous :')
            ->action('Formulaire de commande', $signedUrl)
            ->lineIf($this->previousOrderDetails, 'Détails de votre commande de l\'édition précédente :')
            ->lineIf($this->previousOrderDetails, $this->previousOrderDetails ? '- '.implode(', ', $this->previousOrderDetails) : '- Aucune commande trouvée pour l\'édition précédente.')
            ->line('Vous pouvez aussi nous retourner le formulaire papier au format PDF après l\'avoir rempli à l\'adresse pub@coursedenoel.ch. Vous pouvez le télécharger sous : https://coursedenoel.ch/assets/documents/fo-annonceurs.pdf')
            ->line('Nous nous réjouissons de vous compter parmi nos partenaires et restons à votre disposition pour toute question.')
            ->salutation('Le Comité d\'organisation')
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
