<div class="space-y-4">
    @if ($deadline)
        <div class="flex items-center gap-2 text-sm text-gray-700 bg-indigo-50 border border-indigo-200 rounded-lg p-3">
            <svg class="w-5 h-5 text-indigo-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>Délai d'inscription : <strong>{{ $deadline }}</strong></span>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach ($runs as $run)
            <div class="bg-gray-50 rounded-lg border border-gray-200 p-4">
                <div class="flex justify-between items-center mb-1">
                    <span class="font-semibold text-sm text-gray-800">{{ $run->name }}</span>
                    <span class="text-xs text-gray-500 font-mono">{{ $run->cost ? number_format($run->cost, 2) . ' CHF' : 'Gratuit' }}</span>
                </div>
                @if ($run->registrations_limit)
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2 overflow-hidden">
                        <div class="bg-indigo-600 h-2 rounded-full transition-all duration-300" style="width: {{ min(100, $run->fill_rate) }}%"></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                        <span>Places : {{ $run->registrations_number }} / {{ $run->registrations_limit }}</span>
                        <span>{{ $run->fill_rate }}% rempli</span>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
