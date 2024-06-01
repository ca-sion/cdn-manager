<x-layouts.app>
    <div class="flex items-center justify-center h-screen">
        <section class="bg-white dark:bg-gray-900">
            <div class="py-8 px-4 mx-auto max-w-screen-xl lg:py-16 lg:px-6">
                <div class="mx-auto max-w-screen-sm text-center">
                    <h1 class="mb-4 text-5xl tracking-tight font-extrabold lg:text-7xl text-primary-600 dark:text-primary-500">{{ $title }}</h1>
                    <p class="mb-4 text-2xl tracking-tight font-bold text-gray-900 md:text-3xl dark:text-white">{{ $description }}</p>
                    <p class="mb-4 text-lg font-light text-gray-500 dark:text-gray-400">{{ $text }}</p>
                    <a href="{{ $actionLink }}" target="_blank" class="inline-flex text-white bg-primary-600 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:focus:ring-primary-900 my-4">{{ $actionLabel }}</a>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
