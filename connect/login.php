<?php
//error_reporting(E_ALL);
//ini_set('display_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once  __DIR__ . '/../core/dbcore.php';
require_once __DIR__ . '/../core/session.php';
require_once __DIR__ . '/../core/setting.php';


$msg = '';
if(isset($_GET['msg']) && count($_POST) == 0) {
    $msg = htmlspecialchars($_GET['msg']);
}

try {
    $pdo = connectBD();

    if (!$pdo) {
        $msg = "Erreur de connexion à la base de données.";
    }

} catch (Exception $e) {
    $msg = $e->getMessage();
}

if (count($_POST) > 0 && empty($msg)) {
    
    $login      = trim($_POST['login'] ?? '');
    $password   = $_POST['password'] ?? '';
    $codeParent = trim($_POST['parentCode'] ?? '');
    $typeLogin  = $_POST['typelogin'] ?? '';
    
    if ($typeLogin === 'parent') {
        
        $sql = "SELECT * 
                FROM learn4kids_parents
                WHERE login = ?
                AND codeparent = ?
                AND is_active = 1
                LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([$login, $codeParent]);
        
        $parent = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($parent && password_verify($password, $parent['password'])) {
            
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            //print_r($parent);exit;
            /*if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }*/
            SessionHandle::setSessionParent($parent);

            header('Location: ' . Settings::getHomePage());
            exit;
        } else {
            $msg = "Code parent, login ou mot de passe incorrect.";
        }

    } 
    elseif ($typeLogin === 'child') {

        $sql = "SELECT * 
                FROM learn4kids_children
                WHERE login = ?
                AND codeparent = ?
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$login, $codeParent]);

        $child = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($child && password_verify($password, $child['password'])) {

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            SessionHandle::setSessionChild($child);

            header('Location: '. Settings::getHomePage());
            exit;
        } else {
            $msg = "Code parent, login ou mot de passe incorrect.";
        }
    }
}



?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <link rel="icon" type="image/x-icon" href="<?= Settings::getHomePage() ?>favicon/favicon.ico">
    <link rel="icon" type="image/png" href="<?= Settings::getHomePage() ?>favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" href="<?= Settings::getHomePage() ?>favicon/favicon-16x16.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Connexion - Parent & Enfant</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #4f46e5;
            --secondary: #7c3aed;
            --success: #10b981;
            --danger: #ef4444;
            --white: #ffffff;
            --text: #1f2937;
            --light: #f8fafc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background:
                linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #9333ea 100%);
        }

        .container {
            width: 100%;
            max-width: 1100px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            overflow: hidden;
            box-shadow:
                0 25px 60px rgba(0, 0, 0, .25);
        }

        .left-panel {
            padding: 60px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            background: rgba(255, 255, 255, .15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 35px;
            margin-bottom: 25px;
        }

        .left-panel h1 {
            font-size: 42px;
            margin-bottom: 15px;
            line-height: 1.2;
        }

        .left-panel p {
            opacity: .9;
            line-height: 1.8;
            font-size: 15px;
        }

        .right-panel {
            background: white;
            padding: 40px;
        }

        .tabs {
            display: flex;
            background: #f3f4f6;
            border-radius: 15px;
            padding: 5px;
            margin-bottom: 30px;
        }

        .tab {
            flex: 1;
            padding: 14px;
            border: none;
            background: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: .3s;
        }

        .tab.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .form {
            display: none;
        }

        .form.active {
            display: block;
        }

        .form h2 {
            color: var(--text);
            margin-bottom: 8px;
        }

        .subtitle {
            color: #6b7280;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .input-group {
            margin-bottom: 18px;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #374151;
            font-weight: 500;
        }

        .input-group input {
            width: 100%;
            padding: 15px;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            font-size: 15px;
            transition: .3s;
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, .15);
        }

        .btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: .3s;
        }

        .btn-login {
            color: white;
            background: linear-gradient(135deg,
                    var(--primary),
                    var(--secondary));
        }

        .btn-login:hover {
            transform: translateY(-2px);
        }

        .links {
            display: flex;
            justify-content: space-between;
            margin-top: 18px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .links a {
            text-decoration: none;
            font-size: 14px;
            color: var(--primary);
            font-weight: 500;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .info-box {
            margin-top: 25px;
            padding: 15px;
            border-radius: 12px;
            background: #eef2ff;
            border-left: 4px solid var(--primary);
            font-size: 13px;
            color: #4338ca;
        }

        .child-note {
            margin-top: 20px;
            padding: 15px;
            background: #f0fdf4;
            border-left: 4px solid var(--success);
            border-radius: 12px;
            color: #166534;
            font-size: 13px;
        }

        @media(max-width:900px) {

            .container {
                grid-template-columns: 1fr;
            }

            .left-panel {
                text-align: center;
                padding: 40px 30px;
            }

            .logo {
                margin: auto auto 20px;
            }

            .left-panel h1 {
                font-size: 32px;
            }
        }

        @media(max-width:500px) {

            .right-panel {
                padding: 25px;
            }

            .links {
                flex-direction: column;
            }

            .left-panel h1 {
                font-size: 26px;
            }
        }
    </style>
</head>

<body>

    <div class="container">

        <div class="left-panel">

            <div class="logo">👨‍👩‍👧</div>

            <h1>Espace Famille</h1>

            <p>
                Connectez-vous en tant que parent ou enfant.
                Les comptes enfants sont créés et gérés exclusivement
                par un parent authentifié afin de garantir la sécurité
                et le suivi pédagogique de chaque enfant.
            </p>

        </div>

        <div class="right-panel">
            <div class="icon" style="font-size: 15px; margin-bottom: 15px; color:#ef4444;">🔒
                <?php
                    echo $msg;
                ?>
            </div>
            <div class="tabs">
                <button class="tab active" onclick="showForm('parent')">
                    Parent
                </button>

                <button class="tab" onclick="showForm('child')">
                    Enfant
                </button>
            </div>

            <!-- FORMULAIRE PARENT -->

            <form class="form active" id="parentForm" method="POST" action="">

                <h2>Connexion Parent</h2>

                <p class="subtitle">
                    Connectez-vous à votre espace parent.
                </p>
                
                <input type="hidden" name="typelogin" value="parent">

                <div class="input-group">
                    <label>Code Parent</label>
                    <input type="text" name="parentCode" placeholder="Ex : PAR-2025-001">
                </div>

                <div class="input-group">
                    <label>Login</label>
                    <input type="text" name="login" placeholder="Votre identifiant">
                </div>

                <div class="input-group">
                    <label>Mot de passe</label>
                    <input type="password" name="password" placeholder="********">
                </div>

                <button class="btn btn-login">
                    Se connecter
                </button>

                <div class="links">
                    <a href="<?= $GLOBALS['LocalHome'] ?>resetpassword/">
                        Mot de passe oublié ?
                    </a>

                    <a href="<?= $GLOBALS['LocalHome'] ?>newaccount/">
                        Créer un compte parent
                    </a>
                </div>

                <div class="info-box">
                    La création des comptes enfants est disponible
                    uniquement après connexion d'un parent.
                </div>

            </form>

            <!-- FORMULAIRE ENFANT -->

            <form class="form" id="childForm" method="POST" action="">

                <h2>Connexion Enfant</h2>

                <p class="subtitle">
                    Connecte-toi avec les informations fournies
                    par ton parent.
                </p>

                <input type="hidden" name="typelogin" value="child">

                <div class="input-group">
                    <label>Code Parent</label>
                    <input type="text" name="parentCode" placeholder="Code fourni par le parent">
                </div>

                <div class="input-group">
                    <label>Login</label>
                    <input type="text" name="login" placeholder="Ton identifiant">
                </div>

                <div class="input-group">
                    <label>Mot de passe</label>
                    <input type="password" name="password" placeholder="********">
                </div>

                <button class="btn btn-login">
                    Se connecter
                </button>

                <div class="child-note">
                    Les comptes enfants sont créés exclusivement
                    par un parent déjà connecté.
                </div>

            </form>

        </div>

    </div>

    <script>
        function showForm(type) {

            document
                .querySelectorAll('.form')
                .forEach(form => form.classList.remove('active'));

            document
                .querySelectorAll('.tab')
                .forEach(tab => tab.classList.remove('active'));

            if (type === 'parent') {
                document
                    .getElementById('parentForm')
                    .classList.add('active');

                document
                    .querySelectorAll('.tab')[0]
                    .classList.add('active');
            }

            if (type === 'child') {
                document
                    .getElementById('childForm')
                    .classList.add('active');

                document
                    .querySelectorAll('.tab')[1]
                    .classList.add('active');
            }
        }
        document.addEventListener('DOMContentLoaded', () => {
            // Your code to execute once the HTML structure is ready
            console.log('The DOM is fully parsed and available.');

            <?php 
                if(count($_POST) > 0 ) {
                    if($_POST['typelogin'] === 'child') {
                        echo "showForm('child');";
                    } else {
                        echo "showForm('parent');";
                    }
                }
            ?>
        });
        
    </script>

</body>

</html>