<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Demo App</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-header {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        .premium-shadow {
            box-shadow: 0 4px 24px -6px rgba(0, 0, 0, 0.05), 0 0 1px 0 rgba(0, 0, 0, 0.1);
        }
        .bg-pattern {
            background-color: #f8fafc;
            background-image: radial-gradient(#e2e8f0 1px, transparent 1px);
            background-size: 24px 24px;
        }
    </style>
</head>
<body class="bg-pattern min-h-screen text-slate-800 antialiased selection:bg-indigo-100 selection:text-indigo-900">

    <!-- Navbar -->
    <nav class="glass-header sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/20">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <span class="font-extrabold text-xl tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-slate-900 to-slate-700">Demo App</span>
                </div>
                
                <div class="flex items-center gap-6">
                    <div class="text-right hidden md:block">
                        <p class="font-bold text-sm text-slate-900">{{ request()->ssoUser->name ?? request()->ssoUser->email }}</p>
                        <p class="text-slate-500 text-xs font-medium">{{ request()->ssoUser->active_identity ?? 'No Identity' }}</p>
                    </div>
                    
                    <div class="h-8 w-px bg-slate-200 mx-2 hidden md:block"></div>

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="group flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-rose-600 transition-colors px-4 py-2.5 rounded-xl hover:bg-rose-50">
                            <span>Logout</span>
                            <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-12 px-6 lg:px-8">
        
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-10 gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-600 text-xs font-bold tracking-wide uppercase mb-3">
                    <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                    SSO Session Active
                </div>
                <h1 class="text-4xl font-extrabold tracking-tight text-slate-900">Welcome back, {{ explode(' ', request()->ssoUser->name ?? 'User')[0] }}!</h1>
                <p class="mt-3 text-slate-500 font-medium max-w-xl leading-relaxed">Here's the data we securely retrieved from the SSO provider. Your authentication payload is displayed below.</p>
            </div>
            
            <a href="{{ env('SSO_PROFILE_URL') }}" target="_blank" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-white border border-slate-200 text-sm font-bold text-slate-700 shadow-sm hover:border-indigo-300 hover:text-indigo-600 hover:shadow-md transition-all group">
                <svg class="w-5 h-5 text-slate-400 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Account Settings
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Identity Widget -->
            <div class="bg-white rounded-3xl premium-shadow border border-slate-100 overflow-hidden flex flex-col relative">
                <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-indigo-500 to-purple-500"></div>
                <div class="px-8 py-6 border-b border-slate-50 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-lg text-slate-900">Your Identity</h3>
                        <p class="text-slate-400 text-sm font-medium mt-1">Core details passed via token</p>
                    </div>
                </div>
                
                <div class="p-8 flex-1 flex flex-col justify-center space-y-6">
                    <div class="group">
                        <p class="text-xs font-bold tracking-wider text-slate-400 uppercase mb-1">Full Name</p>
                        <p class="text-base font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">{{ request()->ssoUser->name }}</p>
                    </div>
                    
                    <div class="group">
                        <p class="text-xs font-bold tracking-wider text-slate-400 uppercase mb-1">Email Address</p>
                        <p class="text-base font-medium text-slate-700">{{ request()->ssoUser->email }}</p>
                    </div>
                    
                    <div>
                        <p class="text-xs font-bold tracking-wider text-slate-400 uppercase mb-2">Active Role</p>
                        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 font-bold text-sm border border-indigo-100/50">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a1 1 0 00-1 1v1a1 1 0 002 0V3a1 1 0 00-1-1zM4 4h3a3 3 0 006 0h3a2 2 0 012 2v9a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2zm2.5 7a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm2.45 4a2.5 2.5 0 10-4.9 0h4.9zM12 9a1 1 0 100 2h3a1 1 0 100-2h-3zm-1 4a1 1 0 011-1h2a1 1 0 110 2h-2a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>
                            {{ request()->ssoUser->active_identity ?? 'Unknown' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Metadata JSON -->
            <div class="lg:col-span-2 bg-[#0f172a] rounded-3xl premium-shadow overflow-hidden flex flex-col border border-slate-800 relative">
                <!-- Mac-like Window Controls -->
                <div class="px-6 py-4 border-b border-white/5 bg-white/5 flex justify-between items-center backdrop-blur-sm">
                    <div class="flex items-center gap-4">
                        <div class="flex gap-2">
                            <div class="w-3 h-3 rounded-full bg-rose-500 shadow-sm shadow-rose-500/50"></div>
                            <div class="w-3 h-3 rounded-full bg-amber-500 shadow-sm shadow-amber-500/50"></div>
                            <div class="w-3 h-3 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/50"></div>
                        </div>
                        <h3 class="font-semibold text-sm text-slate-300">payload.json</h3>
                    </div>
                    <div class="text-slate-500 text-xs font-mono bg-white/5 px-2 py-1 rounded">Cached locally</div>
                </div>
                <div class="p-6 overflow-x-auto h-full">
                    <pre class="text-sm font-mono text-emerald-400 leading-relaxed selection:bg-emerald-400/30"><code>{!! htmlspecialchars(json_encode(request()->ssoUser->profilemetadata, JSON_PRETTY_PRINT)) !!}</code></pre>
                </div>
            </div>

        </div>
    </main>

</body>
</html>
