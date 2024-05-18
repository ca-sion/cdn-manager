<div class="flex" style="align-items: center;">
    <div style="margin-right: 6px;">Édition :</div>
    <x-filament::input.wrapper>
        <x-filament::input.select wire:model="edition_id" wire:change="$dispatch('change')">
            @foreach (\App\Models\Edition::all() as $edition)
            <option value="{{ $edition->id }}" @selected(session('edition_id') == $edition->id)>{{ $edition->year }}</option>
            @endforeach
        </x-filament::input.select>
    </x-filament::input.wrapper>

    <script>
        window.addEventListener('refresh-page-edition-change', event => {
           window.location.reload(false);
        })
      </script>

</div>
