<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Identitas - SSO Client</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.6);
        }
        .bg-pattern {
            background-color: #f8fafc;
            background-image: radial-gradient(#e2e8f0 1px, transparent 1px);
            background-size: 24px 24px;
        }
    </style>
</head>
<body class="min-h-screen bg-pattern flex items-center justify-center relative overflow-hidden text-slate-900 py-12 px-4">

    <div class="relative z-10 w-full max-w-2xl">
        <div class="glass-panel rounded-3xl shadow-[0_20px_40px_-15px_rgba(0,0,0,0.1)] p-10">
            
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center h-16 w-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-lg shadow-indigo-500/30 mb-5">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-extrabold mb-2 bg-clip-text text-transparent bg-gradient-to-r from-slate-900 to-slate-700">Pilih Identitas Aktif</h1>
                <p class="text-slate-500 text-sm">Akun SSO Anda memiliki multi-peran. Silakan pilih identitas mana yang ingin Anda gunakan untuk sesi ini.</p>
            </div>

            @if($errors->any())
                <div class="w-full bg-red-50/80 text-red-600 text-sm p-4 rounded-xl mb-6 border border-red-200 flex items-start">
                    <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form action="{{ route('sso.select_identity_submit') }}" method="POST">
                @csrf
                <div class="space-y-4 mb-10">
                    @foreach($identities as $index => $identity)
                        @php
                            $idVal = is_array($identity) ? ($identity['id'] ?? $identity['role'] ?? json_encode($identity)) : $identity;
                            $labelVal = is_array($identity) ? ($identity['name'] ?? $identity['role'] ?? json_encode($identity)) : $identity;
                        @endphp
                        <label class="relative flex cursor-pointer rounded-2xl border bg-white p-5 shadow-sm focus:outline-none 
                                    hover:border-indigo-200 hover:bg-indigo-50/30 transition-all has-[:checked]:border-indigo-500 
                                    has-[:checked]:bg-indigo-50/50 has-[:checked]:ring-1 has-[:checked]:ring-indigo-500">
                            <input type="radio" name="identity" value="{{ $idVal }}" class="sr-only" {{ $index === 0 ? 'checked' : '' }}>
                            <span class="flex flex-1">
                                <span class="flex flex-col">
                                    <span class="block text-sm font-bold text-slate-900 uppercase tracking-wide">{{ $labelVal }}</span>
                                    <span class="mt-1 flex items-center text-sm text-slate-500">Gunakan sistem sebagai {{ $labelVal }}</span>
                                </span>
                            </span>
                            <svg class="h-6 w-6 text-indigo-600 opacity-0 transition-opacity peer-checked:opacity-100 absolute right-5 top-1/2 -translate-y-1/2" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                            </svg>
                            <span class="absolute right-5 top-1/2 -translate-y-1/2 h-5 w-5 rounded-full border border-slate-300 opacity-100 transition-opacity [[data-checked]_&]:opacity-0 hidden"></span>
                        </label>
                    @endforeach
                </div>

                <button type="submit" class="group w-full relative inline-flex items-center justify-center px-8 py-4 text-sm font-bold text-white transition-all duration-300 bg-slate-900 rounded-xl hover:bg-slate-800 hover:shadow-xl hover:shadow-slate-900/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900">
                    <span class="relative flex items-center">
                        Lanjutkan
                        <svg class="w-5 h-5 ml-3 transition-transform duration-300 group-hover:translate-x-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </span>
                </button>
            </form>
            
        </div>
    </div>

    <script>
        // Simple JS to toggle opacity of the checkmark based on radio selection since 'has-[:checked]' in Tailwind might need specific plugin config or v4 support natively.
        // Actually Tailwind v4 supports `has-[:checked]` natively! 
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                document.querySelectorAll('label svg').forEach(svg => svg.style.opacity = '0');
                if(e.target.checked) {
                    e.target.closest('label').querySelector('svg').style.opacity = '1';
                }
            });
        });
        
        // init default checked
        const defaultChecked = document.querySelector('input[type="radio"]:checked');
        if (defaultChecked) {
            defaultChecked.closest('label').querySelector('svg').style.opacity = '1';
        }
    </script>
</body>
</html>
