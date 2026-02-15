<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Verify OTP</title>
    <link rel="stylesheet" href="{{ asset('css/tailwind.css') }}">
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let timeLeft = {{ $timeLeft }}; // dynamic from backend
            const timerElement = document.getElementById('timer');
            const resendLink = document.getElementById('resend-link');

            const timer = setInterval(() => {
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    timerElement.textContent = "OTP expired";
                    resendLink.classList.remove('hidden');
                } else {
                    let minutes = String(Math.floor(timeLeft / 60)).padStart(2, '0');
                    let seconds = String(timeLeft % 60).padStart(2, '0');
                    timerElement.textContent = `${minutes}:${seconds}`;
                    timeLeft--;
                }
            }, 1000);
        });
    </script>

</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-black min-h-screen flex items-center justify-center font-sans text-white">

<div class="bg-gray-800 shadow-xl rounded-2xl p-8 w-full max-w-md text-center relative">
    <!-- Back Button -->
    <a href="{{ route('filament.admin.auth.login') }}"
       class="absolute top-4 left-4 flex items-center justify-center w-8 h-8 rounded-full bg-gray-700 hover:bg-gray-600 transition"
       title="Back to Login">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke-width="2" stroke="white" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
    </a>
    <!-- Logo -->
    @php
        $branding = \App\Models\BrandingSetting::first();
        $app = str(config('app.name'))->lower();
        $app = trim(str_replace( 'system', '', $app));
    @endphp
    <img src="{{ $branding && $branding->white_logo
        ? url('storage/' . $branding->white_logo)
        : url('media/' . $app . '-dark-logo.png') }}"
         alt="Logo"
         class="mx-auto mb-6 w-10 h-auto">

    <h2 class="text-2xl font-bold mb-4">Verify OTP</h2>
    <p class="text-gray-300 mb-2">Enter the verification code we sent you</p>

    <div class="text-red-400 font-bold mb-6 text-lg" id="timer">
        {{ sprintf('%02d:%02d', floor($timeLeft / 60), $timeLeft % 60) }}
    </div>


    <form wire:submit.prevent="submit" class="space-y-6">
        <input type="text" wire:model="otp"
               placeholder="Enter code" maxlength="6"
               inputmode="numeric" pattern="\d*"
               oninput="this.value = this.value.replace(/\D/g, '')"
               class="w-full text-center text-lg tracking-widest px-4 py-3 border border-gray-600 rounded-xl bg-gray-900 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
               required>

        @if (session()->has('error'))
            <div class="text-red-400 font-bold mb-6 text-lg">{{ session('error') }}</div>
        @endif

        <button type="submit"
                class="w-full bg-blue-600 text-white py-3 rounded-xl font-semibold hover:bg-blue-700 shadow-lg transition">
            Verify
        </button>
    </form>

    <p class="text-gray-400 text-sm mt-6 hidden" id="resend-link">
        Didn’t receive the code?
        <button type="button" wire:click="resendOtp" class="text-blue-400 hover:underline">
            Resend
        </button>
    </p>

    @if (session()->has('success'))
        <div class="text-green-400 font-bold mb-6 text-lg">{{ session('success') }}</div>
    @endif
</div>

</body>
</html>
