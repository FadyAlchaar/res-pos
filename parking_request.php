<?php
require_once 'config/config.php';
require_once 'config/language.php';

$lang = $_SESSION['language'] ?? 'en';
$GLOBALS['lang'] = $lang;
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo get_dir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo t('parking_request'); ?></title>
    <style>
    *{
        margin:0;
        padding:0;
        box-sizing:border-box;
    }

    body{
        font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
        min-height:100vh;
        display:flex;
        justify-content:center;
        align-items:center;
        margin:0;
        padding:20px;

        background:
            radial-gradient(circle at top left,#5a2d20 0%,transparent 35%),
            radial-gradient(circle at bottom right,#c59b6d 0%,transparent 30%),
            linear-gradient(145deg,#1a120f 0%,#2c1d18 35%,#4a2d22 100%);
    }

    .container{
        width:100%;
        max-width:430px;

        background:rgba(255,245,235,0.08);
        backdrop-filter:blur(18px);

        border:1px solid rgba(255,255,255,.12);
        border-radius:36px;

        padding:38px 28px;

        text-align:center;

        box-shadow:
            0 30px 80px rgba(0,0,0,.45),
            inset 0 1px 0 rgba(255,255,255,.08);
    }

    h1{
        font-size:2rem;
        margin-bottom:28px;
        font-weight:800;

        background:linear-gradient(
            135deg,
            #fff7eb 0%,
            #f2d1a5 45%,
            #d9a96b 100%
        );

        -webkit-background-clip:text;
        color:transparent;
    }

    .form-group{
        margin-bottom:22px;
        text-align:left;
    }

    label{
        display:block;
        margin-bottom:10px;
        font-weight:600;
        color:#f3dfc4;
        font-size:.95rem;
    }

    input{
        width:100%;
        padding:15px 18px;

        border-radius:22px;
        border:1px solid rgba(255,255,255,.12);

        background:rgba(255,255,255,.08);
        color:#fff;

        font-size:1rem;
        outline:none;

        transition:.3s ease;
    }

    input::placeholder{
        color:rgba(255,240,225,.45);
    }

    input:focus{
        border-color:#d9a96b;
        background:rgba(255,255,255,.12);

        box-shadow:
            0 0 0 4px rgba(217,169,107,.12);
    }

    button{
        width:100%;
        padding:15px;

        border:none;
        border-radius:30px;

        background:linear-gradient(135deg,#c58e54,#e8c08e);
        color:#2b160d;

        font-size:1rem;
        font-weight:700;
        cursor:pointer;

        transition:.3s ease;

        box-shadow:0 14px 28px rgba(197,142,84,.25);
    }

    button:hover{
        transform:translateY(-3px);
        box-shadow:0 18px 34px rgba(197,142,84,.35);
    }

    .message{
        margin-top:18px;
        padding:14px;
        border-radius:18px;
        display:none;
        font-weight:600;
    }

    .message.success{
        display:block;
        background:rgba(76,175,80,.15);
        border:1px solid rgba(76,175,80,.3);
        color:#b8f5c0;
    }

    .message.error{
        display:block;
        background:rgba(244,67,54,.15);
        border:1px solid rgba(244,67,54,.3);
        color:#ffc9c4;
    }

    .lang-switch{
        margin-top:24px;
        display:flex;
        justify-content:center;
        gap:12px;
    }

    .lang-btn{
        padding:10px 18px;
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

        .container{
            padding:28px 20px;
            border-radius:28px;
        }

        h1{
            font-size:1.6rem;
        }

        input,
        button{
            padding:14px;
        }
    }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo t('parking_request'); ?></h1>
        <form id="parkingForm">
            <div class="form-group">
                <label><?php echo t('parking_lot_numbers'); ?></label>
                <input type="text" id="lot_numbers" inputmode="numeric" pattern="[0-9, ]+" placeholder="e.g., 14, 15, 16" required>
            </div>
            <button type="submit"><?php echo t('send'); ?></button>
        </form>
        <div id="message" class="message"></div>
        <div class="lang-switch">
            <a href="?lang=en" class="lang-btn <?php echo $lang == 'en' ? 'active' : ''; ?>">EN</a>
            <a href="?lang=ar" class="lang-btn <?php echo $lang == 'ar' ? 'active' : ''; ?>">ع</a>
        </div>
    </div>
    <script>
        document.getElementById('parkingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const lotNumbers = document.getElementById('lot_numbers').value.trim();
            if (!lotNumbers) return;
            const msgDiv = document.getElementById('message');
            msgDiv.className = 'message';
            msgDiv.style.display = 'none';
            fetch('api/customer_parking_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    lot_numbers: lotNumbers,
                    lang: '<?php echo $lang; ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                msgDiv.textContent = data.message;
                if (data.success) {
                    msgDiv.className = 'message success';
                    document.getElementById('lot_numbers').value = '';
                } else {
                    msgDiv.className = 'message error';
                }
                msgDiv.style.display = 'block';
                setTimeout(() => msgDiv.style.display = 'none', 4000);
            })
            .catch(error => {
                msgDiv.textContent = '<?php echo t('error'); ?>';
                msgDiv.className = 'message error';
                msgDiv.style.display = 'block';
            });
        });
    </script>
</body>
</html>