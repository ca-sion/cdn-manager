<?php

namespace App\Notifications;

use App\Models\Edition;
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
        $vipEmail = Edition::currentMeta('vip_email');
        $eventShortDate = Edition::currentMeta('event_short_date');
        $vipStartTime = Edition::currentMeta('vip_start_time');
        $vipPdfUrl = Edition::currentMeta('vip_invitation_pdf_url');
        $vipDeadline = Edition::currentMeta('vip_deadline');
        $vipSignature = nl2br(e(Edition::currentMeta('vip_signature')));

        $message = (new MailMessage)
            ->subject('Course de Noël et Trail des Châteaux - Invitation VIP ('.$notifiable->name.')')
            ->replyTo($vipEmail)
            // ->bcc('info@coursedenoel.ch')
            ->greeting(new HtmlString('<small>Pour '.$notifiable->name.',</small><br>Chère Amie, Cher Ami,'))
            ->line('Le **'.$eventShortDate.' prochain**, la Place de la Planta et les rues de la vieille ville de Sion vibreront à nouveau sous les applaudissements du public venu encourager les participants de la Course de Noël et du Trail des Châteaux.')
            ->line('Comme le veut la tradition, nous vous invitons dans l’espace VIP Swiss Life sur la Place de la Planta pour un apéritif-raclette qui vous sera servi dès '.$vipStartTime.' selon le programme décrit ci-après. Les festivités se poursuivront sous la tente des fêtes.')
            ->line('📨 [Invitation et programme]('.$vipPdfUrl.')')
            ->lineIf($this->provisionElement->vip_invitation_number > 1, '🔢 Nombre d’invitations : '.$this->provisionElement->vip_invitation_number)
            ->line('Inscription souhaitée jusqu’au **'.$vipDeadline.'** en remplissant le formulaire ci-après :')
            ->action('✍️ Répondre à l’invitation (oui/non)', $responseUrl)
            ->line('Vous trouverez plus d’informations sur nos deux courses sur [coursedenoel.ch](https://coursedenoel.ch) et [traildeschateaux.ch](https://traildeschateaux.ch).')
            ->line('Nous nous réjouissons de vous accueillir dans l’espace VIP Swiss Life et vous présentons, Mesdames, Messieurs, Chères Amies, Chers Amis, nos salutations sportives.')
            ->salutation(new HtmlString($vipSignature));

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
        return [
            //
        ];
    }
}
