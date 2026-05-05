<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Demo SSO Client Laravel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen antialiased text-gray-900">

    <!-- Top Navigation -->
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center gap-2">
                        <div class="h-8 w-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <span class="font-bold text-xl text-gray-900 tracking-tight">Demo App</span>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-sm text-right hidden sm:block">
                        <p class="font-medium text-gray-900">{{ request()->ssoUser->name ?? request()->ssoUser->email }}</p>
                        <p class="text-gray-500 text-xs">{{ request()->ssoUser->active_identity ?? 'No Identity' }}</p>
                    </div>
                    
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800 transition-colors px-3 py-2 rounded-md hover:bg-red-50">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Welcome back, {{ request()->ssoUser->name ?? 'User' }}</h1>
                <p class="mt-2 text-sm text-gray-500">You have successfully authenticated via SSO FASILKOM UNSRI.</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="{{ env('SSO_PROFILE_URL') }}" target="_blank" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="-ml-1 mr-2 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287-.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                    </svg>
                    Account Settings
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Identity Card -->
            <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200">
                <div class="px-6 py-5 border-b border-gray-200 bg-gray-50/50">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Your Identity</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">SSO provided personal details.</p>
                </div>
                <div class="px-6 py-5">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Full name</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-semibold">{{ request()->ssoUser->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Email address</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ request()->ssoUser->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Active Role</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                    {{ request()->ssoUser->active_identity ?? 'Unknown' }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Profile Metadata Preview -->
            <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200 lg:col-span-2">
                <div class="px-6 py-5 border-b border-gray-200 bg-gray-50/50">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Profile Metadata Payload</h3>
                    <p class="mt-1 text-sm text-gray-500">Raw profile data stored locally to prevent excessive API calls.</p>
                </div>
                <div class="p-6 bg-gray-900 text-gray-100 rounded-b-xl overflow-x-auto">
                    <pre class="text-sm font-mono whitespace-pre-wrap">{{ json_encode(request()->ssoUser->profilemetadata, JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
            
        </div>

    </main>

</body>
</html>
