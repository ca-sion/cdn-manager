<?php

namespace App\Notifications;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;

class ClientSendVouchers extends Notification
{
    use Queueable;

    public Collection $vouchers;
    public ?string $customMessage;

    public function __construct(Collection $vouchers, ?string $customMessage = null)
    {
        $this->vouchers = $vouchers;
        $this->customMessage = $customMessage;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $clientName = $notifiable->name ?? ($notifiable->company_name ?? 'Partenaire');
        $currentEditionYear = now()->format('Y');

        $mail = (new MailMessage)
            ->subject('🎟️ Course de Noël et Trail des Châteaux '.$currentEditionYear.' - Codes pour inscriptions offertes (dossards gratuits)')
            ->replyTo('info@coursedenoel.ch')
            ->bcc('info@coursedenoel.ch')
            ->greeting('Bonjour ' . $clientName . ',')
            ->line('Selon les conditions du contrat que vous avez avec la Course de Noël et le Trail des Châteaux, vous trouverez ci-après la liste des codes/vouchers pour vos inscriptions gratuites.')
            ->line('Instructions pour inscription : https://coursedenoel.ch/courses/challenge-entreprises · Délai entreprises : 30 novembre')
            ->line('T-shirt Texner')
            ->line("Vous souhaitez un T-shirt personnalisé avec vos couleurs pour représenter fièrement votre entreprise ? C'est possible ! Choisissez un visuel et commandez votre T-shirt avec Texner : https://coursedenoel.ch/courses/challenge-entreprises. Dernier délai pour la commande : 7 novembre.")
            ->line('Et si vous avez des questions ou besoin d’aide, contactez-nous : info@coursedenoel.ch')
            ->line('Bonne préparation !')
            ->line('Voici la liste de vos codes vouchers (dossards offerts) attribués pour votre groupe :');

        if ($this->customMessage) {
            $mail->line($this->customMessage);
        }

        foreach ($this->vouchers as $voucher) {
            $runInfo = $voucher->run ? ' (Course : ' . $voucher->run->name . ')' : '';
            $mail->line('• ' . $voucher->code . $runInfo);
        }

        $mail->line('Ces codes permettent d\'obtenir la gratuité lors de la saisie de vos participants sur notre plateforme d\'inscription en ligne Datasport.')
            ->line('Nous restons à votre entière disposition pour toute question ou précision complémentaire.')
            ->salutation('Le Comité d\'organisation');

        return $mail;
    }
}
