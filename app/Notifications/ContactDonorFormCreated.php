<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use App\Models\ProvisionElement;
use Illuminate\Support\HtmlString;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Sprain\SwissQrBill\Reference\QrPaymentReferenceGenerator;

class ContactDonorFormCreated extends Notification
{
    use Queueable;

    public $donationProvisionElement;

    /**
     * Create a new notification instance.
     */
    public function __construct(ProvisionElement $donationProvisionElement)
    {
        $this->donationProvisionElement = $donationProvisionElement;
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
        $editionYear = $this->donationProvisionElement->edition?->year;
        $cost = $this->donationProvisionElement->cost;
        $note = $this->donationProvisionElement->note;
        $mention = $this->donationProvisionElement->textual_indicator;
        $qrReference = QrPaymentReferenceGenerator::generate(null, $this->donationProvisionElement->edition?->year.'4444'.$notifiable->id);

        return (new MailMessage)
            ->subject('CDN '.$editionYear.' - Commande donateur effectuée ('.$notifiable->name.')')
            ->replyTo('pub@coursedenoel.ch')
            ->bcc('pub@coursedenoel.ch')
            ->greeting($notifiable->name.',')
            ->line('Vous venez de passer une commande pour une donation pour la Course de Noël. Nous vous remercions beaucoup pour votre soutien.')
            ->lineIf($cost, '**Montant** : '.$cost.' CHF')
            ->lineIf($mention, '**Mention** : '.$mention)
            ->lineIf($note, '**Note** : '.$note)
            ->line('Vous avez différentes possibilités pour effectuer votre don de **'.$cost.' CHF** :')
            ->action('Avec Twint', url()->query('https://donate.raisenow.io/tfbdk', [
                'supporter.first_name.value' => $notifiable->first_name,
                'supporter.last_name.value'  => $notifiable->last_name,
                'supporter.email.value'      => $notifiable->email,
                'amount.values'              => $this->donationProvisionElement->cost,
                'amount.custom'              => true,
            ]))
            ->line(new HtmlString('<a href="https://qr-rechnung.net/#/b,fr,SPC,0200,1,CH473000526565424140D,S,CA%20Sion%20-%20Course%20de%20No%C3%ABl,Case%20postale,4057,1950,Sion,CH,,,,,,,,'.$cost.',CHF,,,,,,,,QRR,'.$qrReference.',Donation%20'.$notifiable->name.',EPD,%2F%2FS1%2F10%2F2023423%2F11%2F240506%2F30%2F329493754%2F31%2F240605?op=downloadpdf">Par bulletin de versement (Qr)</a>'))
            ->line('Par versement avec les coordonnées bancaires suivantes :')
            ->line(new HtmlString('
            CH63 0026 5265 6542 4140 D
            UBS Switzerland AG
            CA Sion - Course de Noël
            Rue du Vieux-Moulin 33
            1950 Sion
            BIC: UBSWCHZH19E'))
            ->line('**Info** : La mention dans l\'encarté sera effective dès que votre don aura été reçu.')
            ->line('Nous restons à disposition en cas de questions ou pour tout complément d\'information.')
            ->salutation('Le Comité de la Course de Noël');
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
