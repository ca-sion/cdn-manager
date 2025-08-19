<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contact;
use App\Models\ProvisionElement;
use Illuminate\Support\HtmlString;
use Sprain\SwissQrBill\Reference\QrPaymentReferenceGenerator;

class MessageController extends Controller
{
    public function advertiserSuccess()
    {
        $client = Client::find(request()->input('client'));

        return view('messages.success', [
            'title'       => 'Magnifique !',
            'description' => $client->name.', merci pour l\'envoi de votre formulaire d\'annonce et pour votre soutien.',
            'text'        => new HtmlString('Un email de confirmation vous a été envoyé à l\'adresse <em>'.$client->email.'</em>. Il contient les instructions pour la suite des opérations. Nous restons à disposition en cas de questions ou pour tout complément d\'information à l\'adresse <a href="mailto:info@coursedenoel.ch" class="underline">info@coursedenoel.ch</a>.'),
            'actionLink'  => $client ? $client?->pdfLink : '/',
            'actionLabel' => $client ? 'Voir la commande effectuée' : 'Accueil',
        ]);
    }

    public function donorSuccess()
    {
        $contact = Contact::find(request()->input('contact'));
        $donationprovisionElement = ProvisionElement::find(request()->input('dpe'));

        return view('messages.donation', [
            'title'       => 'Magnifique !',
            'description' => $contact->name.', merci pour votre soutien.',
            'text'        => new HtmlString('Un email de confirmation vous a été envoyé à l\'adresse <em>'.$contact->email.'</em>. Nous restons à disposition en cas de questions ou pour tout complément d\'information à l\'adresse <a href="mailto:info@coursedenoel.ch" class="underline">info@coursedenoel.ch</a>.'),
            'actionLink'  => null,
            'actionLabel' => null,
            'contact'     => $contact,
            'cost'        => $donationprovisionElement->cost,
            'mention'     => $donationprovisionElement->textual_indicator,
            'qrReference' => QrPaymentReferenceGenerator::generate(null, $donationprovisionElement->edition?->year.'4444'.$contact->id),
            'twintLink'   => url()->query('https://donate.raisenow.io/tfbdk', [
                'supporter.first_name.value' => $contact->first_name,
                'supporter.last_name.value'  => $contact->last_name,
                'supporter.email.value'      => $contact->email,
                'amount.values'              => $donationprovisionElement->cost,
                'amount.custom'              => true,
            ]),
        ]);
    }
}
