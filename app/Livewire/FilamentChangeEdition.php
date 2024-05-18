<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;

class FilamentChangeEdition extends Component
{
    #[Session(key: 'edition_id')]
    public $edition_id;

    #[On('filament-page-edition-change')]
    public function change()
    {
        // session(['edition_id' => $this->edition_id]);
        $this->dispatch('refresh-page-edition-change');
    }

    public function render()
    {
        return view('livewire.filament-change-edition');
    }
}
