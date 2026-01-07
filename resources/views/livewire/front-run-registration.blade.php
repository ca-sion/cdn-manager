<div>
    <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Inscription {{ App\Enums\RunRegistrationTypesEnum::from($type)->getLabel() }}
            </h2>
            @if($isLocked)
                <p class="mt-2 text-sm text-red-600 font-semibold">Le délai d'inscription est dépassé. La liste des participants n'est plus modifiable.</p>
            @endif
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <button wire:click="save" type="button" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Enregistrer l'inscription
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <p>{{ session('message') }}</p>
        </div>
    @endif

    <div class="space-y-6">
        <!-- Section Coordonnées -->
        <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
            <div class="md:grid md:grid-cols-3 md:gap-6">
                <div class="md:col-span-1">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Coordonnées de contact</h3>
                    <p class="mt-1 text-sm text-gray-500">Ces informations nous permettent de vous recontacter si nécessaire.</p>
                </div>
                <div class="mt-5 md:mt-0 md:col-span-2">
                    <div class="grid grid-cols-6 gap-6">
                        @if($type === 'company')
                            <div class="col-span-6">
                                <label class="block text-sm font-medium text-gray-700">Nom de l'entreprise</label>
                                <input wire:model="company_name" type="text" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>
                        @elseif($type === 'school')
                            <div class="col-span-6">
                                <label class="block text-sm font-medium text-gray-700">Nom de l'école</label>
                                <input wire:model="school_name" type="text" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>
                        @endif

                        <div class="col-span-6 sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Prénom contact</label>
                            <input wire:model="contact_first_name" type="text" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>

                        <div class="col-span-6 sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Nom contact</label>
                            <input wire:model="contact_last_name" type="text" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>

                        <div class="col-span-6 sm:col-span-4">
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input wire:model="contact_email" type="email" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grille des participants -->
        <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6 overflow-x-auto">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Liste des participants</h3>
            <table class="min-w-full divide-y divide-gray-200 border border-gray-200" id="registration-grid">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">Prénom</th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">Nom</th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">Né le</th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">Genre</th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" x-data="{
                    isLocked: @js($isLocked),
                    handleKey(e, index, field) {
                        if (this.isLocked) return;
                        let target = null;
                        if (e.key === 'ArrowDown') {
                            target = document.querySelector(`input[data-index='${index + 1}'][data-field='${field}']`);
                        } else if (e.key === 'ArrowUp') {
                            target = document.querySelector(`input[data-index='${index - 1}'][data-field='${field}']`);
                        }
                        if (target) {
                            e.preventDefault();
                            target.focus();
                        }
                    }
                }">
                    @foreach($elements as $index => $element)
                        <tr wire:key="element-{{ $index }}">
                            <td class="p-0 border-r">
                                <input wire:model.defer="elements.{{ $index }}.first_name" 
                                       data-index="{{ $index }}" data-field="first_name"
                                       @keydown="handleKey($event, {{ $index }}, 'first_name')"
                                       @if($isLocked) disabled @endif
                                       type="text" class="w-full border-none focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm @if($isLocked) bg-gray-50 cursor-not-allowed @endif">
                            </td>
                            <td class="p-0 border-r">
                                <input wire:model.defer="elements.{{ $index }}.last_name" 
                                       data-index="{{ $index }}" data-field="last_name"
                                       @keydown="handleKey($event, {{ $index }}, 'last_name')"
                                       @if($isLocked) disabled @endif
                                       type="text" class="w-full border-none focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm @if($isLocked) bg-gray-50 cursor-not-allowed @endif">
                            </td>
                            <td class="p-0 border-r">
                                <input wire:model.defer="elements.{{ $index }}.birthdate" 
                                       data-index="{{ $index }}" data-field="birthdate"
                                       @keydown="handleKey($event, {{ $index }}, 'birthdate')"
                                       @if($isLocked) disabled @endif
                                       type="date" class="w-full border-none focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm @if($isLocked) bg-gray-50 cursor-not-allowed @endif">
                            </td>
                            <td class="p-0 border-r">
                                <select wire:model.defer="elements.{{ $index }}.gender" 
                                        data-index="{{ $index }}" data-field="gender"
                                        @if($isLocked) disabled @endif
                                        class="w-full border-none focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm @if($isLocked) bg-gray-50 cursor-not-allowed @endif">
                                    <option value=""></option>
                                    <option value="M">M</option>
                                    <option value="F">F</option>
                                </select>
                            </td>
                            <td class="p-0">
                                <select wire:model.defer="elements.{{ $index }}.run_id" 
                                        data-index="{{ $index }}" data-field="run_id"
                                        @if($isLocked) disabled @endif
                                        class="w-full border-none focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm @if($isLocked) bg-gray-50 cursor-not-allowed @endif">
                                    <option value=""></option>
                                    @foreach($runs as $run)
                                        <option value="{{ $run->id }}">{{ $run->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if(!$isLocked)
                <div class="mt-4">
                    <button wire:click="addRow" type="button" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">+ Ajouter une ligne</button>
                </div>
            @endif
        </div>
    </div>
</div>