<x-mail::layout>
    {{-- Header --}}
    <x-slot:header>
        <x-mail::header url="https://coursedenoel.ch">
            Course de Noël et Trail des Châteaux
        </x-mail::header>
    </x-slot:header>

    {{-- Body --}}
    {{ $slot }}

    {{-- Subcopy --}}
    @isset($subcopy)
        <x-slot:subcopy>
            <x-mail::subcopy>
                {{ $subcopy }}
            </x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    {{-- Footer --}}
    <x-slot:footer>
        <x-mail::footer>
            Course de Noël et Trail des Châteaux · Case postale 4057 · 1950 Sion
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
