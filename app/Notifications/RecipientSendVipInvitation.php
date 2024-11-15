<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use App\Models\ProvisionElement;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\URL;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class RecipientSendVipInvitation extends Notification
{
    use Queueable;

    public $provisionElement;

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

        $message = (new MailMessage)
            ->subject('Course de Noël - Invitation VIP ('.$notifiable->name.')')
            ->replyTo('info@coursedenoel.ch')
            // ->bcc('info@coursedenoel.ch')
            ->greeting(new HtmlString('<small>Pour '.$notifiable->name.',</small><br>Chère Amie, Cher Ami,'))
            ->line('Le **14 décembre prochain**, la Place de la Planta et les rues de la vieille ville de Sion vibreront à nouveau sous les applaudissements du public venu encourager les participants de la Course de Noël et du Trail des Châteaux.')
            ->line('Comme le veut la tradition, nous vous invitons dans notre espace VIP Swiss Life sur la Place de la Planta pour un apéritif-raclette qui vous sera servi dès 16h30 selon le programme décrit ci-après. Les festivités se poursuivront sous la tente des fêtes.')
            ->line('📨 [Invitation et programme](https://coursedenoel.ch/assets/documents/2024-invitation-vip-simple.pdf)')
            ->lineIf($this->provisionElement->vip_invitation_number > 1, '🔢 Nombre d\'invitations : '.$this->provisionElement->vip_invitation_number)
            ->line('Inscription souhaitée jusqu’au **1er décembre 2024** en remplissant le formulaire ci-après :')
            ->action('✍️ Répondre à l\'invitation (oui/non)', $responseUrl)
            ->line('Vous trouverez plus d’infos sur nos deux courses sur [coursedenoel.ch](https://coursedenoel.ch) et [traildeschateaux.ch](https://traildeschateaux.ch).')
            ->line('Nous nous réjouissons de vous accueillir dans notre espace VIP Swiss Life et vous présentons, Mesdames, Messieurs, Chères Amies, Chers Amis, nos salutations sportives.')
            ->salutation(new HtmlString('Dominique Solioz<br>Président du Co de la Course de Noël<br><br>David Valterio<br>Président du Co du Trail des Châteaux'));

        if ($this->provisionElement->clientVipContactEmail && $this->provisionElement->clientContactEmail && $this->provisionElement->clientVipContactEmail != $this->provisionElement->clientContactEmail) {
            $message->cc($this->provisionElement->clientContactEmail);
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
        return [
            //
        ];
    }
}
