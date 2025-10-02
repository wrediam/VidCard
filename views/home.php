<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VidCard - YouTube Video Sharing with Rich Previews</title>
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <link rel="icon" type="image/png" href="/images/icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        border: "hsl(214.3 31.8% 91.4%)",
                        input: "hsl(214.3 31.8% 91.4%)",
                        ring: "hsl(222.2 84% 4.9%)",
                        background: "hsl(0 0% 100%)",
                        foreground: "hsl(222.2 84% 4.9%)",
                        primary: {
                            DEFAULT: "hsl(222.2 47.4% 11.2%)",
                            foreground: "hsl(210 40% 98%)",
                        },
                        secondary: {
                            DEFAULT: "hsl(210 40% 96.1%)",
                            foreground: "hsl(222.2 47.4% 11.2%)",
                        },
                        muted: {
                            DEFAULT: "hsl(210 40% 96.1%)",
                            foreground: "hsl(215.4 16.3% 46.9%)",
                        },
                        accent: {
                            DEFAULT: "hsl(210 40% 96.1%)",
                            foreground: "hsl(222.2 47.4% 11.2%)",
                        },
                    },
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
        
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.6;
            animation: float 20s infinite ease-in-out;
        }
        
        .orb-1 {
            width: 400px;
            height: 400px;
            background: #34b4d9;
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }
        
        .orb-2 {
            width: 500px;
            height: 500px;
            background: #ac3eb7;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: -5s;
        }
        
        .orb-3 {
            width: 350px;
            height: 350px;
            background: #3f46d3;
            bottom: -100px;
            right: -100px;
            animation-delay: -10s;
        }
        
        .orb-4 {
            width: 300px;
            height: 300px;
            background: #db4585;
            top: 20%;
            right: 10%;
            animation-delay: -15s;
        }
        
        .orb-5 {
            width: 450px;
            height: 450px;
            background: #fa7b68;
            bottom: 20%;
            left: 15%;
            animation-delay: -7s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            25% {
                transform: translate(50px, -50px) scale(1.1);
            }
            50% {
                transform: translate(-30px, 30px) scale(0.9);
            }
            75% {
                transform: translate(40px, 20px) scale(1.05);
            }
        }
        
        .content-wrapper {
            position: relative;
            z-index: 10;
        }
    </style>
</head>
<body class="bg-white min-h-screen overflow-hidden relative">
    <!-- Animated Gradient Orbs Background -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
        <div class="orb orb-4"></div>
        <div class="orb orb-5"></div>
    </div>
    <div class="container mx-auto px-4 py-16 max-w-6xl content-wrapper">
        <!-- Header -->
        <div class="text-center mb-16">
            <div class="flex items-center justify-center mb-6">
                <img src="/images/full_logo.png" alt="VidCard" class="h-20 w-auto">
            </div>
            <p class="text-xl text-slate-600 max-w-2xl mx-auto">
                Create beautiful, shareable links for YouTube videos with rich social media previews
            </p>
        </div>

        <!-- Main Card -->
        <div class="max-w-md mx-auto">
            <div class="bg-white rounded-lg shadow-xl border border-slate-200 p-8">
                <div id="emailStep" class="space-y-6">
                    <div class="text-center">
                        <h2 class="text-2xl font-semibold mb-2">Get Started</h2>
                        <p class="text-slate-600 text-sm">Enter your email to receive a login code</p>
                    </div>
                    
                    <div class="space-y-2">
                        <label for="email" class="text-sm font-medium text-slate-700">Email</label>
                        <input 
                            type="email" 
                            id="email" 
                            placeholder="you@example.com"
                            class="w-full px-4 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-transparent transition"
                        />
                    </div>
                    
                    <button 
                        onclick="sendCode()"
                        class="w-full bg-slate-900 text-white py-2.5 rounded-md font-medium hover:bg-slate-800 transition disabled:opacity-50 disabled:cursor-not-allowed"
                        id="sendCodeBtn"
                    >
                        Send Code
                    </button>
                    
                    <div id="emailError" class="text-sm text-red-600 hidden"></div>
                </div>

                <div id="codeStep" class="space-y-6 hidden">
                    <div>
                        <button 
                            onclick="backToEmail()" 
                            class="text-sm text-slate-600 hover:text-slate-900 mb-4 flex items-center gap-1"
                        >
                            ← Back
                        </button>
                        <h2 class="text-2xl font-semibold mb-2">Check your email</h2>
                        <p class="text-slate-600 text-sm">We sent a 6-digit code to <span id="emailDisplay" class="font-medium"></span></p>
                    </div>
                    
                    <div class="space-y-2">
                        <label for="code" class="text-sm font-medium text-slate-700">Verification Code</label>
                        <input 
                            type="text" 
                            id="code" 
                            placeholder="000000"
                            maxlength="6"
                            class="w-full px-4 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-transparent transition text-center text-2xl tracking-widest font-mono"
                        />
                    </div>
                    
                    <button 
                        onclick="verifyCode()"
                        class="w-full bg-slate-900 text-white py-2.5 rounded-md font-medium hover:bg-slate-800 transition disabled:opacity-50 disabled:cursor-not-allowed"
                        id="verifyCodeBtn"
                    >
                        Verify & Continue
                    </button>
                    
                    <div id="codeError" class="text-sm text-red-600 hidden"></div>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <p class="text-xs text-blue-800">
                            <strong>Note:</strong> The email may take up to 3 minutes to arrive. Please check your spam folder if you don't see it in your inbox.
                        </p>
                    </div>
                    
                    <div class="text-center">
                        <button 
                            onclick="sendCode()" 
                            class="text-sm text-slate-600 hover:text-slate-900"
                        >
                            Didn't receive it? Resend code
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div class="mt-20 grid md:grid-cols-3 gap-8 max-w-4xl mx-auto">
            <div class="text-center">
                <div class="w-12 h-12 bg-slate-900 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                    </svg>
                </div>
                <h3 class="font-semibold mb-2">Rich Previews</h3>
                <p class="text-sm text-slate-600">Beautiful cards with thumbnails and metadata on all social platforms</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-slate-900 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h3 class="font-semibold mb-2">Track Analytics</h3>
                <p class="text-sm text-slate-600">Monitor clicks, referrers, and engagement on your shared videos</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-slate-900 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <h3 class="font-semibold mb-2">Lightning Fast</h3>
                <p class="text-sm text-slate-600">Instant redirects to YouTube while capturing visit data</p>
            </div>
        </div>
    </div>

    <script>
        let userEmail = '';

        function sendCode() {
            const email = document.getElementById('email').value;
            const btn = document.getElementById('sendCodeBtn');
            const error = document.getElementById('emailError');
            
            error.classList.add('hidden');
            
            if (!email || !email.includes('@')) {
                error.textContent = 'Please enter a valid email address';
                error.classList.remove('hidden');
                return;
            }
            
            btn.disabled = true;
            btn.textContent = 'Sending...';
            
            fetch('/', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'send_code', email })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    userEmail = email;
                    document.getElementById('emailDisplay').textContent = email;
                    document.getElementById('emailStep').classList.add('hidden');
                    document.getElementById('codeStep').classList.remove('hidden');
                    document.getElementById('code').focus();
                } else {
                    error.textContent = data.error || 'Failed to send code';
                    error.classList.remove('hidden');
                }
            })
            .catch(err => {
                error.textContent = 'Network error. Please try again.';
                error.classList.remove('hidden');
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = 'Send Code';
            });
        }

        function verifyCode() {
            const code = document.getElementById('code').value;
            const btn = document.getElementById('verifyCodeBtn');
            const error = document.getElementById('codeError');
            
            error.classList.add('hidden');
            
            if (!code || code.length !== 6) {
                error.textContent = 'Please enter the 6-digit code';
                error.classList.remove('hidden');
                return;
            }
            
            btn.disabled = true;
            btn.textContent = 'Verifying...';
            
            fetch('/', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'verify_code', email: userEmail, code })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    error.textContent = data.error || 'Invalid code';
                    error.classList.remove('hidden');
                }
            })
            .catch(err => {
                error.textContent = 'Network error. Please try again.';
                error.classList.remove('hidden');
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = 'Verify & Continue';
            });
        }

        function backToEmail() {
            document.getElementById('codeStep').classList.add('hidden');
            document.getElementById('emailStep').classList.remove('hidden');
            document.getElementById('code').value = '';
            document.getElementById('codeError').classList.add('hidden');
        }

        // Auto-submit on 6 digits
        document.getElementById('code').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '');
            if (this.value.length === 6) {
                verifyCode();
            }
        });

        // Enter key handlers
        document.getElementById('email').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') sendCode();
        });
        
        document.getElementById('code').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') verifyCode();
        });
    </script>
</body>
</html>
