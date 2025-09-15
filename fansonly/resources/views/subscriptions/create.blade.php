<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Subscribe to @{{ $profile->username }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 rounded shadow space-y-4">
                <div>
                    <div class="text-gray-700">Monthly price</div>
                    <div class="text-2xl font-semibold">{{ number_format($profile->monthly_price, 2) }} {{ $profile->currency }}</div>
                </div>
                <form method="POST" action="{{ route('subscribe.store', $profile->username) }}">
                    @csrf
                    <input type="hidden" name="confirm" value="yes" />
                    <x-primary-button>Proceed to Checkout</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

