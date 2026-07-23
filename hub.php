<?php
require_once 'config/config.php';
require_once 'config/language.php';

$lang = $_SESSION['language'] ?? 'en';
$GLOBALS['lang'] = $lang;

$linksFile = __DIR__ . '/config/hub_links.json';
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
    *{
        margin:0;
        padding:0;
        box-sizing:border-box;
    }

    body{
        font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;
        min-height:100vh;
        display:flex;
        justify-content:center;
        align-items:center;
        padding:20px;

        background:
            radial-gradient(circle at top left,#5a2d20 0%,transparent 35%),
            radial-gradient(circle at bottom right,#c59b6d 0%,transparent 30%),
            linear-gradient(145deg,#1a120f 0%,#2c1d18 35%,#4a2d22 100%);
    }

    /* Main card */
    .hub-container{
        max-width:620px;
        width:100%;
    }

    .hub-card{
        background:rgba(255,245,235,0.08);
        backdrop-filter:blur(18px);
        border:1px solid rgba(255,255,255,0.12);
        border-radius:36px;
        padding:42px 28px;
        box-shadow:
            0 30px 80px rgba(0,0,0,.45),
            inset 0 1px 0 rgba(255,255,255,.08);
        text-align:center;
    }

    /* Restaurant name */
    .restaurant-name{
        font-size:2.3rem;
        font-weight:800;
        letter-spacing:.8px;

        background:linear-gradient(
            135deg,
            #fff7eb 0%,
            #f2d1a5 40%,
            #d9a96b 100%
        );

        -webkit-background-clip:text;
        color:transparent;

        margin-bottom:12px;
    }

    /* Welcome */
    .tagline{
        color:rgba(255,240,225,.75);
        font-size:.95rem;
        letter-spacing:2px;
        text-transform:uppercase;
        margin-bottom:34px;
    }

    /* Grid */
    .links-grid{
        display:grid;
        grid-template-columns:repeat(auto-fill,minmax(115px,1fr));
        gap:18px;
        margin-bottom:34px;
    }

    /* Link card */
    .hub-link{
        text-decoration:none;
        border-radius:24px;
        padding:18px 12px;

        background:rgba(255,255,255,.06);
        border:1px solid rgba(255,255,255,.08);

        color:#fff;
        display:flex;
        flex-direction:column;
        align-items:center;
        gap:14px;

        transition:.35s ease;
    }

    .hub-link:hover{
        transform:translateY(-8px);
        background:rgba(255,255,255,.12);

        box-shadow:
            0 18px 35px rgba(0,0,0,.25);
    }

    /* Icons */
    .hub-icon{
        width:60px;
        height:60px;
        border-radius:20px;

        display:flex;
        align-items:center;
        justify-content:center;

        font-size:1.6rem;
        color:white;

        box-shadow:
            inset 0 1px 0 rgba(255,255,255,.3),
            0 10px 20px rgba(0,0,0,.25);

        transition:.35s ease;
    }

    .hub-link:hover .hub-icon{
        transform:scale(1.08) rotate(3deg);
    }

    /* Text */
    .hub-text{
        font-size:.92rem;
        font-weight:600;
        color:#fff6eb;
        line-height:1.3;
    }

    /* Language switch */
    .lang-switch{
        display:flex;
        justify-content:center;
        gap:12px;
    }

    .lang-btn{
        padding:10px 20px;
        border-radius:40px;
        text-decoration:none;

        background:rgba(255,255,255,.08);
        border:1px solid rgba(255,255,255,.12);

        color:#f3dfc4;
        font-weight:600;

        transition:.3s ease;
    }

    .lang-btn:hover{
        background:rgba(255,255,255,.16);
    }

    .lang-btn.active{
        background:linear-gradient(135deg,#c58e54,#e8c08e);
        color:#2b160d;
        box-shadow:0 10px 25px rgba(197,142,84,.35);
    }

    @media(max-width:480px){

        .hub-card{
            padding:28px 18px;
            border-radius:28px;
        }

        .restaurant-name{
            font-size:1.8rem;
        }

        .hub-icon{
            width:50px;
            height:50px;
            font-size:1.3rem;
        }

        .hub-text{
            font-size:.85rem;
        }
    }
    .footer-credit{
    margin-top:28px;
    padding-top:18px;
    border-top:1px solid rgba(255,255,255,.08);

    font-size:.78rem;
    letter-spacing:.8px;
    color:rgba(255,240,225,.55);

    display:flex;
    justify-content:center;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
    }

    .footer-credit .designed{
        font-style:italic;
        color:rgba(255,240,225,.45);
    }

    .footer-credit .designer-name{
        font-weight:600;

        background:linear-gradient(
            135deg,
            #f2d1a5,
            #d9a96b
        );

        -webkit-background-clip:text;
        color:transparent;
    }

    .footer-credit .phone{
        color:rgba(255,240,225,.7);
        font-weight:500;
    }

    @media(max-width:480px){
        .footer-credit{
            font-size:.72rem;
            gap:6px;
        }
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
        <div class="footer-credit">
            <span class="designed">Designed by</span>
            <span class="designer-name">Fady Alchaar</span>
            <span class="phone">• +963-937764548</span>
        </div>
    </div>
</div>
</body>
</html>