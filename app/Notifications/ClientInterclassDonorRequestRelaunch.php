<?php

namespace App\Notifications;

use App\Models\Client;
use App\Models\Edition;
use Illuminate\Bus\Queueable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\URL;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ClientInterclassDonorRequestRelaunch extends Notification
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
        $currentEditionYear = Edition::currentYear();
        $email = Edition::currentMeta('advertisers_email');
        $eventDate = Edition::currentMeta('event_date');
        $participants = Edition::currentMeta('expected_participants');
        $interclassNumber = Edition::currentMeta('interclass_edition_number');
        $interclassParticipants = Edition::currentMeta('interclass_participants');
        $deadline = Edition::currentMeta('interclass_relaunch_deadline');
        $contactAddress = nl2br(e(Edition::currentMeta('interclass_contact_address')));
        $signature = nl2br(e(Edition::currentMeta('interclass_signature')));

        return (new MailMessage)
            ->subject('🏃‍♂️ Course de Noël '.$currentEditionYear.' - Concours interclasses : demande pour prix spéciaux ('.$notifiable->name.') : rappel')
            ->replyTo($email)
            ->bcc($email)
            ->greeting('Madame, Monsieur,')
            ->line('Le samedi '.$eventDate.' marquera un événement exceptionnel à Sion : la **Course de Noël**, qui verra plus de '.$participants.' coureurs s\'élancer au cœur de la ville de Sion.')
            ->line('Dans ce cadre, le « **Concours interclasses** » fêtera sa '.$interclassNumber.' édition, et permettra à nouveau à **plus de '.$interclassParticipants.' élèves** du degré primaire de découvrir le plaisir de courir avec leurs camarades de classe et de donner le meilleur d’eux-mêmes pour renforcer leur esprit d’équipe !')
            ->line('**Nous nous permettons donc de réitérer notre appel à votre précieux soutien.**')
            ->line('Vous avez été un soutien fidèle par le passé ou partagez nos valeurs de promotion du sport jeunesse. C’est pourquoi nous nous permettons de vous solliciter afin de pouvoir primer les meilleures classes de chacune des 6 catégories.')
            ->line('Nous vous serions ainsi très reconnaissants si vous pouviez **offrir aux participant·e·s des bons/entrées** et ce jusqu\'à 25 élèves. Votre généreux geste permettrait à ces enfants et à leurs familles de passer ensemble un moment inoubliable.')
            ->line('En contrepartie, nous :')
            ->line('- mentionnons votre entreprise sur notre site web;')
            ->line('- publions un post de remerciement sur nos différents réseaux sociaux;')
            ->line('- vous permettons de vous faire connaître auprès d\'un large public de plus de 1\'000 élèves et parents d\'élèves.')

            // ->action('Formulaire de commande', $signedUrl)

            ->line('Pour confirmer votre soutien ou si vous avez des questions, nous vous invitons à répondre par retour d\'email :')
            ->line('- Adresse : '.$email)
            ->line('- Délai : '.$deadline)
            ->line('Les lots physiques sont à envoyer à :')
            ->line(new HtmlString('<small>'.$contactAddress.'</small>'))

            ->lineIf($this->previousOrderDetails, 'Votre prix de l\'édition précédente :')
            ->lineIf($this->previousOrderDetails, $this->previousOrderDetails ? '- '.implode(', ', $this->previousOrderDetails) : '- Aucune commande trouvée pour l\'édition précédente.')

            ->line('Nous vous remercions infiniment d\'avance pour votre précieuse aide, au nom de tous les élèves et du Comité d\'organisation.')
            ->salutation(new HtmlString($signature))
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
