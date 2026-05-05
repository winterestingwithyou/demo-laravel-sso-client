<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Demo SSO Client Laravel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen antialiased text-gray-900">

    <div class="max-w-md w-full p-8 bg-white rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-gray-100 flex flex-col items-center text-center">
        <!-- Logo / Icon -->
        <div class="mb-6 h-16 w-16 bg-indigo-50 rounded-full flex items-center justify-center border border-indigo-100">
            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
        </div>

        <h1 class="text-2xl font-bold mb-2">Welcome to Demo App</h1>
        <p class="text-gray-500 mb-8 text-sm">Securely access your account using your institutional credentials.</p>

        @if($errors->any())
            <div class="w-full bg-red-50 text-red-600 text-sm p-4 rounded-lg mb-6 border border-red-100">
                {{ $errors->first() }}
            </div>
        @endif

        <a href="{{ route('sso.redirect') }}" class="w-full relative inline-flex items-center justify-center px-6 py-3.5 text-sm font-semibold text-white transition-all duration-200 bg-indigo-600 border border-transparent rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-600 group">
            <span>Login via SSO FASILKOM UNSRI</span>
            <svg class="w-5 h-5 ml-2 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
        </a>

        <div class="mt-8 text-xs text-gray-400">
            &copy; {{ date('Y') }} Demo SSO Client Laravel. All rights reserved.
        </div>
    </div>

</body>
</html>
