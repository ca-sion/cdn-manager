<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use App\Models\ProvisionElement;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\URL;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class VipResponseUpdated extends Notification
{
    use Queueable;

    public ProvisionElement $provisionElement;

    /**
     * Create a new notification instance.
     */
    public function __construct(ProvisionElement $provisionElement)
    {
        $this->provisionElement = $provisionElement;
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
        $responseUrl = URL::signedRoute('vip.response', ['provisionElement' => $this->provisionElement]);
        $isPresent = (bool) $this->provisionElement->vip_response_status;
        $statusLabel = $isPresent ? 'Présent' : 'Absent';
        $guests = collect($this->provisionElement->vip_guests)->filter()->values();

        $message = (new MailMessage)
            ->subject('Course de Noël et Trail des Châteaux - Confirmation de votre réponse VIP ('.$notifiable->name.')')
            ->replyTo('info@coursedenoel.ch')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Nous avons bien enregistré votre réponse à l\'invitation VIP pour la Course de Noël et le Trail des Châteaux.')
            ->line('**Récapitulatif de votre réponse :**')
            ->line('• **Statut :** '.$statusLabel)
            ->lineIf(filled($this->provisionElement->vip_category), '• **Catégorie :** '.$this->provisionElement->vip_category_label)
            ->lineIf(filled($this->provisionElement->vip_invitation_number), '• **Nombre d\'invitations :** '.$this->provisionElement->vip_invitation_number)
            ->lineIf($isPresent && $guests->isNotEmpty(), '• **Invités annoncés :** '.$guests->implode(', '))
            ->lineIf(filled($this->provisionElement->note), '• **Remarques :** '.$this->provisionElement->note);

        if ($isPresent) {
            $message
                ->line('Nous nous réjouissons de vous accueillir dans notre espace VIP. Vous retrouverez le programme et les informations pratiques dans votre e-mail d\'invitation.');
        } else {
            $message->line('Nous vous remercions de nous avoir fait part de votre réponse et espérons avoir le plaisir de vous accueillir lors d\'une prochaine édition.');
        }

        $message
            ->line('Vous pouvez modifier votre réponse ou la liste de vos invités à tout moment via le lien ci-dessous :')
            ->action('✍️ Modifier ma réponse', $responseUrl)
            ->line('Pour toute question, n\'hésitez pas à nous contacter à [info@coursedenoel.ch](mailto:info@coursedenoel.ch).')
            ->salutation(new HtmlString('Benoît Torrent<br>Co-Président du CO<br><br>Esther Debons<br>Co-Présidente du CO'));

        if ($this->provisionElement->recipientVipContactEmail && $this->provisionElement->recipientContactEmail && $this->provisionElement->recipientVipContactEmail != $this->provisionElement->recipientContactEmail) {
            $message->cc($this->provisionElement->recipientContactEmail);
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
