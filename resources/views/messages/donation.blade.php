<x-layouts.app>
    <div class="flex items-center justify-center h-screen">
        <section class="bg-white dark:bg-gray-900">
            <div class="py-8 px-4 mx-auto max-w-screen-xl lg:py-16 lg:px-6">
                <div class="mx-auto max-w-screen-sm text-center">
                    <h1 class="mb-4 text-5xl tracking-tight font-extrabold lg:text-7xl text-primary-600 dark:text-primary-500">{{ $title }}</h1>
                    <p class="mb-4 text-2xl tracking-tight font-bold text-gray-900 md:text-3xl dark:text-white">{{ $description }}</p>
                    <p class="mb-4 text-lg font-light text-gray-500 dark:text-gray-400">{{ $text }}</p>
                    @if ($actionLabel)
                        <a href="{{ $actionLink }}" target="_blank" class="inline-flex text-white bg-primary-600 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:focus:ring-primary-900 my-4">{{ $actionLabel }}</a>
                    @endif

                    <div class="mt-6">
                        <div class="flex p-4 mb-4 text-sm text-start text-gray-800 rounded-lg bg-gray-50 dark:bg-gray-800 dark:text-gray-400" role="alert">
                            <svg class="shrink-0 inline w-4 h-4 me-3 mt-[2px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                            </svg>
                            <span class="sr-only">Note</span>
                            <div>
                                <span>La mention "<em>{{ $mention }}</em>" dans l'encarté sera effective dès que votre don aura été reçu.</span>
                            </div>
                        </div>
                    </div>
                    <p class="mt-4">Vous avez différentes possibilités pour effectuer votre don de {{ $cost }} CHF :</p>

                    @if ($twintLink)
                    <style>:host{display:block;text-align:center;opacity:1;transition:opacity .3s}.wrapper{box-sizing:border-box;background-color:var(--bg-color);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;width:auto;min-width:12.375em;-webkit-user-select:none;-moz-user-select:none;user-select:none;transition:min-width .3s,height .3s ease-in-out,background-color .3s,padding-left .3s,padding-right .3s ease-in-out;font-weight:500;font-family:Roboto PaylinkButton,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,sans-serif,BlinkMacSystemFont,-apple-system,Segoe UI,Oxygen,Ubuntu,Cantarell,Fira Sans,Droid Sans,Helvetica Neue,sans-serif}.wrapper,.wrapper:hover,.wrapper:visited{color:#fff;-webkit-text-decoration:none;text-decoration:none;text-decoration-color:#fff}.wrapper:hover{background-color:var(--bg-color-hover)}.wrapper:active{background-color:var(--bg-color-active)}.wrapper img{transition:margin-right .3s ease-in-out,height .3s ease-in-out}.wrapper span{white-space:nowrap;letter-spacing:normal;letter-spacing:initial;transition:font-size .3s ease-in-out}.width-fixed{min-width:12.375em}.width-full{min-width:100%}.paylink-width-dynamic{width:auto}.paylink-width-dynamic.paylink-size-large{padding:0 1.875em}.paylink-width-dynamic.paylink-size-medium{padding:0 1.5625em}.paylink-width-dynamic.paylink-size-small{padding:0 1.25em}.paylink-width-fixed.paylink-size-large{width:270px}.paylink-width-fixed.paylink-size-medium{width:235px}.paylink-width-fixed.paylink-size-small{width:200px}.paylink-width-dynamic{width:auto;padding:0 30px}.paylink-width-full{min-width:100%}.size-small{height:2.5em;padding:0 1.5625em;border-radius:.25em}.size-small span{font-size:.9375em}.size-small img{margin-right:1em;height:1.6875em}.paylink-size-small{height:2.5em}.paylink-size-small span{font-size:.9375em}.paylink-size-small img{margin-right:.625em;height:1em}.size-medium{height:2.875em;padding:0 1.875em;border-radius:.25em}.size-medium span{font-size:.9375em}.size-medium img{margin-right:1em;height:1.8125em}.paylink-size-medium{height:2.875em}.paylink-size-medium span{font-size:.9375em}.paylink-size-medium img{margin-right:.625em;height:1em}.size-large{height:3.5em;padding:0 2.1875em;border-radius:.375em}.size-large span{font-size:1.0625em}.size-large img{margin-right:1.25em;height:2.25em}.paylink-size-large{height:3.5em}.paylink-size-large span{font-size:1.0625em}.paylink-size-large img{margin-right:.625em;height:1.125em}.color-scheme-light,.color-scheme-light:hover,.color-scheme-light:visited{color:#000;background-color:#fff}.color-scheme-dark,.color-scheme-dark:hover,.color-scheme-dark:visited{color:#fff;background-color:#262626}.md-ripple{position:relative;overflow:hidden}.md-ripple .md-ripple-effect{position:absolute;opacity:0;border-radius:50%;animation:md-ripple .3s}.md-ripple.color-scheme-light .md-ripple-effect{background-color:#000}.md-ripple.color-scheme-dark .md-ripple-effect{background-color:#fff}@keyframes md-ripple{0%{transform:scale(.75);opacity:.2}to{transform:scale(3);opacity:0}}</style>
                    <div class="mx-auto mt-6">
                        <a class="wrapper width-fixed size-large color-scheme-dark" href="{{ $twintLink }}" target="_blank"><img alt="" src="https://assets.raisenow.io/twint-logo-dark.svg"><span>Faire votre don avec TWINT</span></a>
                    </div>
                    @endif

                    <div class="mt-6">
                        <a href="https://qr-rechnung.net/#/b,fr,SPC,0200,1,CH473000526565424140D,S,CA%20Sion%20-%20Course%20de%20No%C3%ABl,Case%20postale,4057,1950,Sion,CH,,,,,,,,{{ $cost }},CHF,,,,,,,,QRR,{{ $qrReference }},Donation%20{{ $contact->name }},EPD,%2F%2FS1%2F10%2F2023423%2F11%2F240506%2F30%2F329493754%2F31%2F240605?op=downloadpdf" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center me-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" target="_blank">
                            <span class="me-2">📄</span>
                        Faire votre don par bulletin de versement
                        </a>
                    </div>


                    <div class="text-start mt-6">
                        <p>Par versement avec les coordonnées bancaires suivantes :</p>
                        <div class="ms-4 text-sm mt-4">
                            CH63 0026 5265 6542 4140 D<br>UBS Switzerland AG<br>CA Sion - Course de Noël<br>Rue du Vieux-Moulin 33<br>1950 Sion<br>BIC: UBSWCHZH19E
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
