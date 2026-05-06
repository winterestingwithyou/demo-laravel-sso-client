<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SSO Client</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .blob {
            position: absolute;
            filter: blur(80px);
            z-index: 0;
            opacity: 0.6;
        }
        @keyframes blob-bounce {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0px, 0px) scale(1); }
        }
        .animate-blob { animation: blob-bounce 7s infinite; }
        .animation-delay-2000 { animation-delay: 2s; }
        .animation-delay-4000 { animation-delay: 4s; }
    </style>
</head>
<body class="min-h-screen bg-slate-50 flex items-center justify-center relative text-slate-900">

    <!-- Decorative Blobs -->
    <div class="blob bg-purple-300 w-96 h-96 rounded-full top-0 left-10 animate-blob"></div>
    <div class="blob bg-indigo-300 w-96 h-96 rounded-full top-20 right-20 animate-blob animation-delay-2000"></div>
    <div class="blob bg-pink-300 w-80 h-80 rounded-full bottom-[-10%] left-1/3 animate-blob animation-delay-4000"></div>

    <div class="relative z-10 w-full max-w-lg px-6">
        <div class="glass-panel rounded-3xl shadow-[0_20px_40px_-15px_rgba(0,0,0,0.1)] p-10 flex flex-col items-center text-center">
            
            <div class="mb-6 h-20 w-20 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/30 transform rotate-3 hover:rotate-6 transition duration-300">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>

            <h1 class="text-3xl font-extrabold mb-3 bg-clip-text text-transparent bg-gradient-to-r from-indigo-900 to-purple-800">Demo SSO Client</h1>
            <p class="text-slate-500 mb-10 text-sm leading-relaxed">Masuk dengan aman dan mudah menggunakan kredensial institusi Anda melalui SSO FASILKOM UNSRI.</p>

            @if($errors->any())
                <div class="w-full bg-red-50/80 backdrop-blur-md text-red-600 text-sm p-4 rounded-xl mb-8 border border-red-200 shadow-sm flex items-start text-left">
                    <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <a href="{{ route('sso.redirect') }}" class="group w-full relative inline-flex items-center justify-center px-8 py-4 text-sm font-bold text-white transition-all duration-300 bg-slate-900 rounded-xl hover:bg-slate-800 hover:shadow-xl hover:shadow-slate-900/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900 overflow-hidden">
                <span class="absolute inset-0 w-full h-full -mt-1 rounded-lg opacity-30 bg-gradient-to-b from-transparent via-transparent to-black"></span>
                <span class="relative flex items-center">
                    Login dengan SSO FASILKOM UNSRI
                    <svg class="w-5 h-5 ml-3 transition-transform duration-300 group-hover:translate-x-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </span>
            </a>

            <div class="mt-10 text-xs font-medium text-slate-400">
                &copy; {{ date('Y') }} SIMLAB Integration Demo.
            </div>
        </div>
    </div>

</body>
</html>
