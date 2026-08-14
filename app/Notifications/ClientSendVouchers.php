<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

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

        $csvFileName = 'vouchers_cdn_'.str($clientName)->slug().'.csv';

        // Build CSV attachment content (Code;Course;Statut)
        $csvLines = ['Code;Course'];
        foreach ($this->vouchers as $voucher) {
            $runName = $voucher->run ? $voucher->run->name : 'Toutes courses';
            $csvLines[] = "{$voucher->code};\"{$runName}\"";
        }
        $csvData = implode("\r\n", $csvLines);

        $mail = (new MailMessage)
            ->subject('🎟️ Course de Noël et Trail des Châteaux '.$currentEditionYear.' - Codes pour inscriptions offertes ('.$clientName.')')
            ->replyTo('info@coursedenoel.ch')
            ->bcc('info@coursedenoel.ch')
            ->greeting('Cher partenaire,')
            ->line('Selon les conditions de votre partenariat avec la Course de Noël et le Trail des Châteaux, trouverez en pièce jointe la liste des codes/vouchers pour vos inscriptions gratuites.')
            ->attachData($csvData, $csvFileName, [
                'mime' => 'text/csv',
            ]);

        $mail->line('📎 La liste complète est disponible dans le fichier joint _'.$csvFileName.'_ (ouvrable directement dans Excel).');

        if ($this->customMessage) {
            $mail->line($this->customMessage);
        }

        // Instructions Section
        $instructionsHtml = '<div style="margin-top: 18px;">'
            .'<h4 style="margin: 0 0 6px 0; color: #0f172a; font-size: 16px;">Inscription à la course enetreprise</h4>'
            .'<ul style="margin: 0; padding-left: 20px; font-size: 15px; color: #334155;">'
            .'<li><strong>Informations :</strong> <a href="https://coursedenoel.ch/courses/challenge-entreprises" style="color: #2563eb; text-decoration: underline;">coursedenoel.ch/courses/challenge-entreprises</a></li>'
            .'<li><strong>Délai d\'inscription :</strong> <strong style="color: #dc2626;">30 novembre</strong></li>'
            .'</ul>'
            .'</div>';
        $mail->line(new HtmlString($instructionsHtml));

        // Texner T-shirt Section
        $tshirtHtml = '<div style="margin-top: 16px; background-color: #f1f5f9; border-left: 4px solid #0ea5e9; padding: 10px 14px; border-radius: 4px;">'
            .'<h4 style="margin: 0 0 4px 0; color: #0369a1; font-size: 13px;">👕 T-shirts personnalisés avec Texner</h4>'
            .'<p style="margin: 0; font-size: 12px; color: #334155;">'
            .'Vous souhaitez un T-shirt personnalisé aux couleurs de votre entreprise ? C\'est possible ! Choisissez un visuel et commandez directement avec notre partenaire <strong>Texner</strong> avant le <strong>7 novembre</strong> sur : '
            .'<a href="https://coursedenoel.ch/courses/challenge-entreprises" style="color: #0284c7; font-weight: bold; text-decoration: underline;">coursedenoel.ch/courses/challenge-entreprises</a>.'
            .'</p>'
            .'</div>';
        $mail->line(new HtmlString($tshirtHtml));

        // Contact Section
        $mail
            ->line(new HtmlString('<br>'))
            ->line('Bonne préparation !')
            ->salutation('Le Comité d\'organisation');

        return $mail;
    }
}
