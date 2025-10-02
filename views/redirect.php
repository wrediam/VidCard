<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($video['title']); ?></title>
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <link rel="icon" type="image/png" href="/images/icon.png">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="video">
    <meta property="og:title" content="<?php echo htmlspecialchars($video['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($video['description']); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($video['thumbnail_url']); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($video['youtube_url']); ?>">
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($video['title']); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($video['description']); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($video['thumbnail_url']); ?>">
    
    <script>
        window.location.href = "<?php echo htmlspecialchars($video['youtube_url']); ?>";
    </script>
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .redirect-message {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 400px;
        }
        h1 {
            margin: 0 0 16px 0;
            font-size: 24px;
            color: #1a1a1a;
        }
        p {
            margin: 0;
            color: #666;
        }
        a {
            color: #667eea;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="redirect-message">
        <img src="/images/icon.png" alt="VidCard" class="w-16 h-16 mx-auto mb-4">
        <h1>Redirecting to YouTube...</h1>
        <p>If you are not redirected automatically, <a href="<?php echo htmlspecialchars($video['youtube_url']); ?>">click here</a>.</p>
        <p class="text-xs text-slate-400 mt-4">Powered by VidCard</p>
    </div>
</body>
</html>
