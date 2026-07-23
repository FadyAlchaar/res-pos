<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/language.php';

$lang = $_SESSION['language'] ?? 'en';
$GLOBALS['lang'] = $lang;

$linksFile = __DIR__ . '/../config/hub_links.json';
$links = [];
if (file_exists($linksFile)) {
    $data = json_decode(file_get_contents($linksFile), true);
    $links = $data['links'] ?? [];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo get_dir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo t('restaurant_name'); ?> – Hub</title>
<!--     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
 -->    <link rel="stylesheet" href="/res-pos/assets/fontawesome/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(145deg, #fffffd 0%, #ffc4bd 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            /* background-image: url(assets/images/img.png);
            background-position: center;
            background-repeat: no-repeat; */
        }
        .hub-container {
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
        }
        .hub-card {
            background: rgb(6 0 0 / 12%);
            backdrop-filter: blur(6px);
            border-radius: 50px;
            padding: 30px 20px;
            box-shadow: 0 25px 45px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.5);
            text-align: center;
        }
        .restaurant-name {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #2c3e50, #1a2632);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            margin-bottom: 10px;
        }
        .tagline {
            color: #6c757d;
            margin-bottom: 30px;
            font-size: 0.9rem;
        }
        .links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .hub-link {
            /* background: #ffffff4d; */
            border-radius: 24px;
            padding: 15px 10px;
            text-decoration: none;
            color: #ffffff;
            transition: all 0.2s;
            /* box-shadow: 0 8px 15px rgba(0,0,0,0.05); */
            /* border: 1px solid rgba(0,0,0,0.05); */
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        .hub-link:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 25px rgba(0,0,0,0.1);
            background: #f8fafc;
        }
        .hub-icon {
            width: 55px;
            height: 55px;
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            background: #3498db;
            transition: all 0.2s;
        }
        .hub-link:hover .hub-icon {
            transform: scale(1.05);
        }
        .hub-text {
            font-size: 0.9rem;
            font-weight: 600;
            text-align: center;
            line-height: 1.2;
        }
        .lang-switch {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        .lang-btn {
            padding: 8px 18px;
            border-radius: 40px;
            text-decoration: none;
            background: rgba(255,255,255,0.7);
            color: #2c3e50;
            font-weight: 600;
            transition: all 0.2s;
        }
        .lang-btn.active {
            background: #3498db;
            color: white;
        }
        @media (max-width: 480px) {
            .hub-card { padding: 20px; }
            .links-grid { gap: 15px; }
            .hub-icon { width: 45px; height: 45px; font-size: 1.4rem; }
            .hub-text { font-size: 0.9rem; color: black;}
        }
    </style>
</head>
<body>
<div class="hub-container">
    <div class="hub-card">
        <div class="restaurant-name"><?php echo htmlspecialchars(t('restaurant_name')); ?></div>
        <div class="tagline"><?php echo $lang === 'ar' ? 'مرحباً بك' : 'Welcome'; ?></div>
        <div class="links-grid">
            <?php foreach ($links as $link): ?>
                <a href="<?php echo htmlspecialchars($link['url']); ?>" class="hub-link" target="_blank" rel="noopener noreferrer">
                    <div class="hub-icon" style="background: <?php echo htmlspecialchars($link['color']); ?>;">
                        <i class="<?php echo htmlspecialchars($link['icon']); ?>"></i>
                    </div>
                    <div class="hub-text"><?php echo htmlspecialchars($link['title_' . $lang]); ?></div>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="lang-switch">
            <a href="?lang=en" class="lang-btn <?php echo $lang == 'en' ? 'active' : ''; ?>">EN</a>
            <a href="?lang=ar" class="lang-btn <?php echo $lang == 'ar' ? 'active' : ''; ?>">ع</a>
        </div>
    </div>
</div>
</body>
</html>