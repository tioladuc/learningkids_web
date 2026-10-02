<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
 }
require_once __DIR__ . '/../core/dbcore.php';
require_once __DIR__ . '/../core/email_sender.php';

$msg = '';
$success = false;

$pdo = connectBD();

if (
    isset($_POST['action'])
    && $_POST['action'] === 'send_code'
) {

    $email = trim($_POST['email']);

    $code = str_pad(
        random_int(0, 999999),
        6,
        '0',
        STR_PAD_LEFT
    );
    
    try {
        $stmt = $pdo->prepare("
            SELECT *
            FROM learn4kids_parents
            WHERE email = ?
            LIMIT 1
        ");
        
        $stmt->execute([$email]);

        $parent = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($parent) {

            $stmt = $pdo->prepare("
                UPDATE learn4kids_parents
                SET activation_code = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $code,
                $parent['id']
            ]);
            
            EmailSender::sendPasswordResetCode(
                $email,
                $parent['first_name'],
                $code,
                $parent['codeparent']
            );
            //print_r($_POST);echo"<br><br>zzzzzzzzzz $email zzz///zzzzzz ==$code== zzzzzz??++???zzzzzzzzzzz<br><br>";print_r($parent); exit;
        }

        $success = true;
        $msg = "Si un compte associé à cette adresse e-mail existe, un code de vérification a été envoyé.";

    } catch (PDOException $e) { // Catch only the database exception
        
    }
    
}

//echo "*********$success**********";exit;
if (
    isset($_POST['action'])
    && $_POST['action'] === 'reset_password'
) {

    $email = trim($_POST['email']);
    $code = trim($_POST['code']);
    $password = $_POST['password'];
    $password2 = $_POST['password2'];

    if ($password !== $password2) {

        $msg = "Les mots de passe ne correspondent pas.";
        $success = true;
    } else {

        $stmt = $pdo->prepare("
            SELECT *
            FROM learn4kids_parents
            WHERE email = ?
            AND activation_code = ?
            LIMIT 1
        ");

        $stmt->execute([
            $email,
            $code
        ]);

        $parent = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$parent) {

            $msg = "Code de vérification/email invalide .";
            $success = true;
        } else {
            try {
                $newPassword =
                    password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                $stmt = $pdo->prepare("
                    UPDATE learn4kids_parents
                    SET password = ?,
                        activation_code = NULL
                    WHERE id = ?
                ");

                $stmt->execute([
                    $newPassword,
                    $parent['id']
                ]);

                header(
                    "Location: ". Settings::getHomePage() ."connect/login.php?msg="
                        . urlencode(
                            "Votre mot de passe a été réinitialisé avec succès. Connectez-vous avec votre nouveau mot de passe."
                        )
                );

                exit;

            } catch (PDOException $e) { // Catch only the database exception
                
            }
            
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

    <title>Réinitialisation du mot de passe</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #4f46e5;
            --secondary: #7c3aed;
            --success: #10b981;
            --light: #f8fafc;
            --dark: #1f2937;
            --gray: #6b7280;
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
                linear-gradient(135deg,
                    #4f46e5,
                    #7c3aed,
                    #9333ea);
        }

        .container {
            width: 100%;
            max-width: 1100px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            overflow: hidden;
            border-radius: 30px;
            background: rgba(255, 255, 255, .12);
            backdrop-filter: blur(20px);
            box-shadow: 0 25px 60px rgba(0, 0, 0, .25);
        }

        .left-panel {
            color: white;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            background: rgba(255, 255, 255, .15);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 40px;
            margin-bottom: 25px;
        }

        .left-panel h1 {
            font-size: 42px;
            margin-bottom: 15px;
        }

        .left-panel p {
            line-height: 1.8;
            opacity: .95;
        }

        .right-panel {
            background: white;
            padding: 40px;
        }

        

        .step.active {
            display: block;
        }

        .step h2 {
            margin-bottom: 8px;
            color: var(--dark);
        }

        .subtitle {
            color: var(--gray);
            margin-bottom: 25px;
            font-size: 14px;
        }

        .input-group {
            margin-bottom: 18px;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
        }

        .input-group input {
            width: 100%;
            padding: 15px;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            font-size: 15px;
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
            color: white;
            font-weight: 600;
            font-size: 15px;
            background:
                linear-gradient(135deg,
                    var(--primary),
                    var(--secondary));
        }

        .btn:hover {
            opacity: .95;
        }

        .info {
            margin-top: 20px;
            padding: 15px;
            border-radius: 12px;
            background: #eef2ff;
            color: #4338ca;
            font-size: 13px;
            border-left: 4px solid var(--primary);
        }

        .links {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }

        .links a {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .success {
            margin-bottom: 20px;
            padding: 14px;
            background: #ecfdf5;
            color: #166534;
            border-left: 4px solid var(--success);
            border-radius: 12px;
            display: none;
        }

        @media(max-width:900px) {

            .container {
                grid-template-columns: 1fr;
            }

            .left-panel {
                text-align: center;
                padding: 40px 30px;
            }

            .icon {
                margin: auto auto 20px;
            }

            .left-panel h1 {
                font-size: 30px;
            }

        }

        @media(max-width:600px) {

            .right-panel {
                padding: 25px;
            }

            .links {
                flex-direction: column;
            }

        }
    </style>
</head>

<body>

    <div class="container">

        <div class="left-panel">

            <div class="icon">🔐</div>

            <h1>Mot de passe oublié ?</h1>

            <p>
                Saisissez votre adresse e-mail pour recevoir
                un code de vérification. Ce code vous permettra
                de définir un nouveau mot de passe sécurisé.
            </p>

        </div>

        <div class="right-panel">


            <!-- ETAPE 1 -->
            <?php if($success == false) { ?>
            <form action="" method="post">
                <div style="color: red; margin-bottom: 20px;">
                    
                </div>

                <div class="active" id="step1">

                    <h2>Recevoir un code</h2>

                    <p class="subtitle">
                        Entrez votre adresse e-mail.
                    </p>

                    <div class="input-group">
                        <label>Adresse e-mail</label>
                        <input
                            type="email"
                            id="email"
                            placeholder="exemple@email.com" name="email" required>
                        <input
                            type="hidden"
                            name="action"
                            value="send_code">
                    </div>

                    <button
                        class="btn"
                        onclick="sendCode()">
                        Envoyer le code
                    </button>

                </div>
            </form>
            <?php } ?>
            <!-- ETAPE 2 -->

            <?php if($success) { ?>
            
            <form action="" method="post">
                <div class="step" id="step2">
                <span style="color: red;"><i><strong><?php if($msg) { echo htmlspecialchars($msg); } ?></strong></i></span><br>
                    <h2>Réinitialiser le mot de passe</h2>

                    <p class="subtitle">
                        
                        Saisissez le code reçu puis choisissez
                        votre nouveau mot de passe.
                    </p>

                    <div class="input-group">
                        <label>Adresse e-mail</label>
                        <input
                            type="email"
                            id="email2" name="email" required>

                        <input
                            type="hidden"
                            name="action"
                            value="reset_password">
                    </div>

                    <div class="input-group">
                        <label>Code de vérification</label>
                        <input
                            type="text"
                            placeholder="123456" name="code" required>
                    </div>

                    <div class="input-group">
                        <label>Nouveau mot de passe</label>
                        <input
                            type="password"
                            placeholder="********" name="password" required>
                    </div>

                    <div class="input-group">
                        <label>Confirmer le mot de passe</label>
                        <input
                            type="password"
                            placeholder="********" name="password2" required>
                    </div>

                    <button class="btn">
                        Réinitialiser le mot de passe
                    </button>

                </div>
            </form>

            <div class="info">
                Pour des raisons de sécurité, le code de vérification
                expire généralement après quelques minutes.
            </div>
            <?php } ?>

            <div class="links">

                <a href="<?= $GLOBALS['LocalHome'] ?>login/">
                    ← Retour à la connexion
                </a>

                <a href="<?= $GLOBALS['LocalHome']  ?>newaccount/">
                    Créer un compte parent
                </a>

            </div>

        </div>

    </div>

    <script>
        
    </script>

</body>

</html>