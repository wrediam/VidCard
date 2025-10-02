<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost in Space - VidCard</title>
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <link rel="icon" type="image/png" href="/images/icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .floating {
            animation: float 3s ease-in-out infinite;
        }
        @keyframes twinkle {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        .star {
            position: absolute;
            background: white;
            border-radius: 50%;
            animation: twinkle 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-b from-slate-900 via-purple-900 to-slate-900 min-h-screen flex items-center justify-center overflow-hidden relative">
    <!-- Animated stars -->
    <div class="star" style="width: 2px; height: 2px; top: 10%; left: 20%; animation-delay: 0s;"></div>
    <div class="star" style="width: 3px; height: 3px; top: 20%; left: 80%; animation-delay: 0.5s;"></div>
    <div class="star" style="width: 2px; height: 2px; top: 60%; left: 10%; animation-delay: 1s;"></div>
    <div class="star" style="width: 3px; height: 3px; top: 80%; left: 70%; animation-delay: 1.5s;"></div>
    <div class="star" style="width: 2px; height: 2px; top: 30%; left: 50%; animation-delay: 0.3s;"></div>
    <div class="star" style="width: 3px; height: 3px; top: 70%; left: 30%; animation-delay: 0.8s;"></div>
    <div class="star" style="width: 2px; height: 2px; top: 15%; left: 60%; animation-delay: 1.2s;"></div>
    <div class="star" style="width: 3px; height: 3px; top: 50%; left: 85%; animation-delay: 0.6s;"></div>

    <div class="relative z-10 text-center px-6 max-w-2xl">
        <!-- Floating astronaut emoji -->
        <div class="floating text-9xl mb-8">
            🧑‍🚀
        </div>

        <!-- Error message -->
        <h1 class="text-6xl font-bold text-white mb-4">
            You Must Be Lost
        </h1>
        
        <p class="text-2xl text-purple-200 mb-8">
            This video has drifted into the void...
        </p>

        <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6 mb-8 border border-white/20">
            <p class="text-white/90 text-lg mb-2">
                <span class="font-semibold">404:</span> Video Not Found
            </p>
            <p class="text-white/70 text-sm">
                This link may have been deleted or never existed in our galaxy.
            </p>
        </div>

        <!-- Action buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a 
                href="/" 
                class="px-8 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white font-semibold rounded-lg hover:from-purple-600 hover:to-pink-600 transition transform hover:scale-105 shadow-lg"
            >
                Return to Earth 🌍
            </a>
            <a 
                href="/dashboard" 
                class="px-8 py-3 bg-white/10 backdrop-blur-sm text-white font-semibold rounded-lg hover:bg-white/20 transition border border-white/30"
            >
                Go to Dashboard
            </a>
        </div>

        <!-- Fun fact -->
        <div class="mt-12 text-white/50 text-sm">
            <p>💡 Fun fact: The average distance to a deleted video is ∞ light-years</p>
        </div>
    </div>

    <!-- Shooting star effect -->
    <script>
        function createShootingStar() {
            const star = document.createElement('div');
            star.className = 'absolute bg-white rounded-full';
            star.style.width = '2px';
            star.style.height = '2px';
            star.style.top = Math.random() * 50 + '%';
            star.style.left = '100%';
            star.style.boxShadow = '0 0 10px 2px rgba(255,255,255,0.5)';
            document.body.appendChild(star);

            const duration = 1000 + Math.random() * 1000;
            const startX = window.innerWidth;
            const endX = -100;
            const startY = parseFloat(star.style.top);
            const endY = startY + 20;

            const startTime = Date.now();
            
            function animate() {
                const elapsed = Date.now() - startTime;
                const progress = elapsed / duration;

                if (progress < 1) {
                    star.style.left = (startX + (endX - startX) * progress) + 'px';
                    star.style.top = (startY + (endY - startY) * progress) + '%';
                    star.style.opacity = 1 - progress;
                    requestAnimationFrame(animate);
                } else {
                    star.remove();
                }
            }

            animate();
        }

        // Create shooting stars periodically
        setInterval(createShootingStar, 3000);
        setTimeout(createShootingStar, 500);
    </script>
</body>
</html>
