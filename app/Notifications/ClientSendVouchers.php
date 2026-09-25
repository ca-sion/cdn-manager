<?php

namespace App\Notifications;

use App\Models\Client;
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

    public ?int $trailQuota;

    public function __construct(Collection $vouchers, ?string $customMessage = null, ?int $trailQuota = null)
    {
        $this->vouchers = $vouchers;
        $this->customMessage = $customMessage;
        $this->trailQuota = $trailQuota;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $clientName = $notifiable->name ?? ($notifiable->company_name ?? 'Partenaire');
        $currentEditionYear = now()->format('Y');

        $cdnCount = $this->vouchers->count();
        $trailQuota = $this->trailQuota ?? ($notifiable instanceof Client ? $notifiable->trail_vouchers_quota : 0);

        $csvFileName = 'vouchers_cdn_'.str($clientName)->slug().'.csv';

        // Build CSV attachment content with UTF-8 BOM (Code;Course)
        $csvLines = ['Code;Course'];
        foreach ($this->vouchers as $voucher) {
            $runName = $voucher->run ? $voucher->run->name : 'Toutes courses';
            $csvLines[] = "{$voucher->code};\"{$runName}\"";
        }
        $csvData = "\xEF\xBB\xBF".implode("\r\n", $csvLines);

        $mail = (new MailMessage)
            ->subject('🎟️ Course de Noël et Trail des Châteaux '.$currentEditionYear.' - Inscriptions offertes selon partenariat ('.$clientName.')')
            ->replyTo('info@coursedenoel.ch')
            ->bcc('info@coursedenoel.ch')
            ->greeting('Cher partenaire,')
            ->line('Selon les conditions de votre partenariat avec la Course de Noël et le Trail des Châteaux, voici le récapitulatif de vos inscriptions offertes ainsi que les modalités pour inscrire vos participants.')
            ->attachData($csvData, $csvFileName, [
                'mime' => 'text/csv; charset=UTF-8',
            ]);

        if ($this->customMessage) {
            $mail->line($this->customMessage);
        }

        // 1. Synthèse des inscriptions offertes selon partenariat
        $trailSummaryBlock = '';
        if ($trailQuota > 0) {
            $trailSummaryBlock = '<div style="flex: 1; min-width: 200px; background: #ffffff; border: 1px solid #bbf7d0; border-radius: 6px; padding: 12px; margin: 4px;">'
                .'<div style="font-size: 11px; text-transform: uppercase; color: #15803d; font-weight: 700; letter-spacing: 0.5px;">Trail des Châteaux</div>'
                .'<div style="font-size: 20px; font-weight: 800; color: #166534; margin: 4px 0;">'.$trailQuota.' <span style="font-size: 13px; font-weight: normal; color: #374151;">inscriptions</span></div>'
                .'<div style="font-size: 12px; color: #6b7280;">Inscription par email</div>'
                .'</div>';
        }

        $summaryHtml = '<div style="margin: 20px 0 16px 0; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px;">'
            .'<div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 10px;">🎟️ Vos inscriptions offertes – Édition '.$currentEditionYear.'</div>'
            .'<div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px;">'
            .'<div style="flex: 1; min-width: 200px; background: #ffffff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 12px; margin: 4px;">'
            .'<div style="font-size: 11px; text-transform: uppercase; color: #1d4ed8; font-weight: 700; letter-spacing: 0.5px;">Course de Noël</div>'
            .'<div style="font-size: 20px; font-weight: 800; color: #1e40af; margin: 4px 0;">'.$cdnCount.' <span style="font-size: 13px; font-weight: normal; color: #374151;">inscriptions</span></div>'
            .'<div style="font-size: 12px; color: #6b7280;">Codes fournis dans le fichier joint</div>'
            .'</div>'
            .$trailSummaryBlock
            .'</div>'
            .'<div style="font-size: 13px; color: #475569; padding-top: 4px;">'
            .'📎 <strong>Fichier joint :</strong> Vos codes sont disponibles dans le fichier <em>'.$csvFileName.'</em> (compatible Excel).'
            .'</div>'
            .'</div>';
        $mail->line(new HtmlString($summaryHtml));

        // 2. Section Challenge Entreprises (Course de Noël) adaptée au nombre de vouchers
        if ($cdnCount >= 20) {
            $registrationDetailsHtml = '<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; margin-bottom: 10px;">'
                .'<div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">🔹 Équipe de 20 personnes et plus (Recommandé) :</div>'
                .'<div style="font-size: 13px; color: #334155; line-height: 1.5; margin-bottom: 8px;">Inscription groupée simplifiée via notre formulaire en ligne (vos '.$cdnCount.' inscriptions seront déduites automatiquement).</div>'
                .'<a href="https://manager.coursedenoel.ch/registrations/company" style="display: inline-block; background-color: #2563eb; color: #ffffff; padding: 7px 14px; font-size: 13px; font-weight: 600; text-decoration: none; border-radius: 4px;">Formulaire en ligne (+ 20) →</a>'
                .'</div>'
                .'<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; margin-bottom: 12px;">'
                .'<div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">🔹 Pour des inscriptions individuelles :</div>'
                .'<div style="font-size: 13px; color: #334155; line-height: 1.5; margin-bottom: 8px;">Vous pouvez également transmettre les codes vouchers joints à vos coureurs pour une inscription individuelle sur Datasport.</div>'
                .'<a href="https://coursedenoel.ch/courses/challenge-entreprises#content-inscriptions" style="display: inline-block; background-color: #475569; color: #ffffff; padding: 6px 12px; font-size: 12px; font-weight: 600; text-decoration: none; border-radius: 4px;">Inscription Datasport →</a>'
                .'</div>';
        } else {
            $registrationDetailsHtml = '<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; margin-bottom: 10px;">'
                .'<div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">🔹 Moins de 20 participants :</div>'
                .'<div style="font-size: 13px; color: #334155; line-height: 1.5; margin-bottom: 8px;">Inscription individuelle sur la plateforme Datasport en saisissant un code voucher par participant lors de la validation.</div>'
                .'<a href="https://coursedenoel.ch/courses/challenge-entreprises#content-inscriptions" style="display: inline-block; background-color: #2563eb; color: #ffffff; padding: 7px 14px; font-size: 13px; font-weight: 600; text-decoration: none; border-radius: 4px;">Inscription Datasport →</a>'
                .'</div>'
                .'<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; margin-bottom: 12px;">'
                .'<div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">🔹 20 participants et plus :</div>'
                .'<div style="font-size: 13px; color: #334155; line-height: 1.5; margin-bottom: 8px;">Si votre délégation atteint 20 personnes ou plus, vous pouvez utiliser notre formulaire de groupe simplifié (vos '.$cdnCount.' inscriptions seront déduites automatiquement).</div>'
                .'<a href="https://manager.coursedenoel.ch/registrations/company" style="display: inline-block; background-color: #475569; color: #ffffff; padding: 6px 12px; font-size: 12px; font-weight: 600; text-decoration: none; border-radius: 4px;">Formulaire de groupe (≥ 20) →</a>'
                .'</div>';
        }

        $instructionsHtml = '<div style="margin: 16px 0; background: #f8fafc; border: 1px solid #cbd5e1; border-top: 4px solid #2563eb; border-radius: 8px; padding: 16px;">'
            .'<h4 style="margin: 0 0 12px 0; color: #1e3a8a; font-size: 16px; font-weight: 700;">🏃 Challenge Entreprises</h4>'
            .$registrationDetailsHtml
            .'<div style="font-size: 13px; color: #475569; border-top: 1px solid #e2e8f0; padding-top: 10px; line-height: 1.5;">'
            .'⏰ <strong>Délai :</strong> <strong style="color: #dc2626;">22 novembre</strong> · ℹ️ <strong>Infos :</strong> <a href="https://coursedenoel.ch/courses/challenge-entreprises" style="color: #2563eb; text-decoration: underline;">coursedenoel.ch/courses/challenge-entreprises</a>'
            .'</div>'
            .'</div>';
        $mail->line(new HtmlString($instructionsHtml));

        // 3. Section Trail des Châteaux (conditionnelle)
        if ($trailQuota > 0) {
            $trailHtml = '<div style="margin: 16px 0; background: #f0fdf4; border: 1px solid #bbf7d0; border-top: 4px solid #16a34a; border-radius: 8px; padding: 16px;">'
                .'<h4 style="margin: 0 0 10px 0; color: #15803d; font-size: 16px; font-weight: 700;">⛰️ Trail des Châteaux</h4>'
                .'<p style="margin: 0 0 12px 0; font-size: 13px; color: #166534; line-height: 1.6;">'
                .'Vous bénéficiez également de <strong>'.$trailQuota.' inscriptions</strong> pour le <strong>Trail des Châteaux</strong>.<br>'
                .'Pour inscrire vos coureurs au Trail, merci de contacter directement notre secrétariat par email à '
                .'<a href="mailto:inscriptions@traildeschateaux.ch" style="color: #15803d; font-weight: bold; text-decoration: underline;">inscriptions@traildeschateaux.ch</a> '
                .'en indiquant les nom, prénom, date de naissance, adresse email, sexe, numéro de téléphone portable et parcours choisi pour chaque participant.'
                .'</p>'
                .'<a href="mailto:inscriptions@traildeschateaux.ch?subject=Inscriptions%20Trail%20des%20Ch%C3%A2teaux%20-%20'.urlencode($clientName).'" style="display: inline-block; background-color: #16a34a; color: #ffffff; padding: 7px 14px; font-size: 13px; font-weight: 600; text-decoration: none; border-radius: 4px;">✉️ Écrire au secrétariat du Trail →</a>'
                .'</div>';
            $mail->line(new HtmlString($trailHtml));
        }

        // 4. Section T-shirts Texner
        $tshirtHtml = '<div style="margin: 16px 0; background: #f0f9ff; border: 1px solid #bae6fd; border-top: 4px solid #0284c7; border-radius: 8px; padding: 16px;">'
            .'<h4 style="margin: 0 0 6px 0; color: #0369a1; font-size: 15px; font-weight: 700;">👕 T-shirts personnalisés avec Texner</h4>'
            .'<p style="margin: 0 0 10px 0; font-size: 13px; color: #0c4a6e; line-height: 1.5;">'
            .'Vous souhaitez un T-shirt personnalisé aux couleurs de votre entreprise ? Choisissez un visuel et commandez directement avec notre partenaire <strong>Texner</strong> avant le <strong>11 novembre</strong>.'
            .'</p>'
            .'<a href="https://coursedenoel.ch/courses/challenge-entreprises#content-t-shirt" style="display: inline-block; background-color: #0284c7; color: #ffffff; padding: 6px 12px; font-size: 12px; font-weight: 600; text-decoration: none; border-radius: 4px;">Commander vos T-shirts Texner →</a>'
            .'</div>';
        $mail->line(new HtmlString($tshirtHtml));

        // Clôture
        $mail
            ->line(new HtmlString('<div style="margin-top: 14px;">Bonne préparation !</div>'))
            ->salutation('Le comité d\'organisation');

        return $mail;
    }
}
