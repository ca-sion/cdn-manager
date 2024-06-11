<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Support\HtmlString;

class MessageController extends Controller
{
    public function adertiserSuccess()
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
}
