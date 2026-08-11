<?php

namespace App\Notifications;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;

class ClientSendVouchersNotification extends Notification
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
        $mail = (new MailMessage)
            ->subject('Vos codes vouchers / dossards offerts - Course de Noël')
            ->greeting('Bonjour ' . ($notifiable->name ?? ''))
            ->line('Voici la liste de vos codes vouchers pour l\'inscription de vos participants :');

        if ($this->customMessage) {
            $mail->line($this->customMessage);
        }

        foreach ($this->vouchers as $voucher) {
            $runInfo = $voucher->run ? ' (Course : ' . $voucher->run->name . ')' : '';
            $status = $voucher->is_used ? ' [DÉJÀ UTILISÉ]' : ' [VALIDE]';
            $mail->line('• Code : ' . $voucher->code . $runInfo . $status);
        }

        $mail->line('Ces codes permettent d\'obtenir la gratuité lors de la saisie des participants sur notre plateforme d\'inscription ou sur Datasport.')
            ->line('Merci pour votre engagement et votre confiance !');

        return $mail;
    }
}
