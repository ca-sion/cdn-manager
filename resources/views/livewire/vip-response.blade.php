<div class="min-h-screen bg-gray-50 dark:bg-gray-950 py-8 sm:py-12 px-4 sm:px-6">
    <div class="w-full max-w-4xl mx-auto space-y-6">
        <!-- Header Banner -->
        <div class="overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 p-6 sm:p-8 text-white shadow-lg ring-1 ring-white/10">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                <div class="flex items-start gap-4">
                    <div class="shrink-0 rounded-xl bg-white/10 p-2.5 backdrop-blur-sm ring-1 ring-white/15">
                        <x-pdf.logo-cdn class="h-12 w-12" />
                    </div>
                    <div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-400/15 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-amber-300 ring-1 ring-amber-400/30 mb-2">
                            Invitation VIP
                        </span>
                        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white">
                            Course de Noël et Trail des Châteaux
                        </h1>
                        <p class="mt-1.5 text-sm text-blue-100/90 leading-relaxed">
                            Confirmation de présence à l'espace VIP
                        </p>
                    </div>
                </div>
            </div>

            <!-- Current Status Bar -->
            <div class="mt-6 pt-4 border-t border-white/10 flex flex-wrap items-center justify-between gap-3 text-xs text-blue-100/80">
                <div class="flex items-center gap-2">
                    <span>Statut actuel :</span>
                    @if ($provisionElement->vip_response_status === null || $provisionElement->vip_response_status === 'pending')
                        <span class="inline-flex items-center rounded-full bg-white/15 px-2.5 py-0.5 font-semibold text-white">
                            En attente de réponse
                        </span>
                    @elseif ((bool) $provisionElement->vip_response_status)
                        <span class="inline-flex items-center rounded-full bg-emerald-500/20 px-2.5 py-0.5 font-semibold text-emerald-300 ring-1 ring-emerald-400/30">
                            Présent
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-rose-500/20 px-2.5 py-0.5 font-semibold text-rose-300 ring-1 ring-rose-400/30">
                            Absent
                        </span>
                    @endif
                </div>
                <span>Vous pouvez modifier votre réponse à tout moment via ce lien.</span>
            </div>
        </div>

        @if ($saved)
            <div class="rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50 dark:bg-emerald-950/40 p-4 text-sm text-emerald-900 dark:text-emerald-200 shadow-xs">
                <p class="font-semibold">Votre réponse a bien été enregistrée.</p>
                @if ($confirmationEmail)
                    <p class="mt-1 text-xs text-emerald-800 dark:text-emerald-300">
                        Un e-mail de confirmation contenant le récapitulatif de votre réponse vient d'être envoyé à <span class="font-medium">{{ $confirmationEmail }}</span>.
                    </p>
                @endif
            </div>
        @endif

        <form wire:submit="update" class="space-y-6">
            {{ $this->form }}

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-2">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Un récapitulatif vous sera envoyé par e-mail après validation.
                </p>

                <x-filament::button
                    type="submit"
                    size="lg"
                    icon="heroicon-m-check-circle"
                    wire:loading.attr="disabled"
                    :disabled="! $this->hasValidStatus() || ! $this->hasChanges()"
                >
                    <span wire:loading.remove wire:target="update">Enregistrer et envoyer</span>
                    <span wire:loading wire:target="update">Enregistrement en cours...</span>
                </x-filament::button>
            </div>
        </form>

        <footer class="pt-4 border-t border-gray-200 dark:border-gray-800 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500 dark:text-gray-400">
            <span>Course de Noël et Trail des Châteaux · Sion</span>
            <div class="flex items-center gap-4">
                <a href="https://coursedenoel.ch" target="_blank" rel="noopener noreferrer" class="hover:text-gray-800 dark:hover:text-gray-200 transition">coursedenoel.ch</a>
                <a href="https://traildeschateaux.ch" target="_blank" rel="noopener noreferrer" class="hover:text-gray-800 dark:hover:text-gray-200 transition">traildeschateaux.ch</a>
            </div>
        </footer>
    </div>

    <x-filament-actions::modals />
</div>
