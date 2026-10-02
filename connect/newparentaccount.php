<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
 }
require_once __DIR__ . '/../core/dbcore.php';
require_once __DIR__ . '/../core/email_sender.php';

$msg = '';

try {

    $pdo = connectBD();

    if (!$pdo) {
        $msg = "Erreur de connexion à la base de données.";
    }

} catch (Exception $e) {

    $msg = $e->getMessage();
}

if (count($_POST) > 0 && empty($msg)) {

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $codeparent = trim($_POST['codeparent'] ?? '');
    $login      = trim($_POST['login'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $password2  = $_POST['password2'] ?? '';

    if (
        empty($first_name) ||
        empty($last_name) ||
        empty($codeparent) ||
        empty($login) ||
        empty($email) ||
        empty($password) ||
        empty($password2)
    ) {

        $msg = "Tous les champs sont obligatoires.";

    } elseif ($password !== $password2) {

        $msg = "Les mots de passe ne correspondent pas.";

    } else {

        try {

            $sql = "
                SELECT COUNT(*) nb
                FROM learn4kids_parents
                WHERE login = ?
                   OR email = ?
                   OR codeparent = ?
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $login,
                $email,
                $codeparent
            ]);

            $exists = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($exists['nb'] > 0) {

                $msg = "Le login, l'adresse e-mail ou le code parent existe déjà.";

            } else {

                $id = 'parent_' . uniqid();

                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $activationCode = str_pad(
                    random_int(0, 999999),
                    6,
                    '0',
                    STR_PAD_LEFT
                );

                $sql = "
                    INSERT INTO learn4kids_parents
                    (
                        id,
                        first_name,
                        last_name,
                        login,
                        password,
                        email,
                        codeparent,
                        codesecret,
                        is_active,
                        activation_code,
                        trial_end_date

                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        '',
                        1,
                        ?,
                        NOW() + INTERVAL ". Settings::numberOfTrialMonths() ." MONTH
                    )
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $id,
                    $first_name,
                    $last_name,
                    $login,
                    $passwordHash,
                    $email,
                    $codeparent,
                    $activationCode
                ]);
                EmailSender::newAccountWelcomeMessage(
                                $email,
                                $login,
                                $codeparent,
                                $first_name,
                                $last_name
                            );
                header("Location: ". $GLOBALS['LocalHome'] . "login/?msg=Compte parent créé avec succès et vous avez droit à jours de période d'essai");
                exit;
            }

        } catch (Exception $e) {

            $msg = $e->getMessage();
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

    <title>Créer un compte Parent</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #4f46e5;
            --secondary: #7c3aed;
            --success: #10b981;
            --dark: #1f2937;
            --gray: #6b7280;
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
                linear-gradient(135deg,
                    #4f46e5 0%,
                    #7c3aed 50%,
                    #9333ea 100%);
        }

        .container {
            width: 100%;
            max-width: 1100px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            overflow: hidden;
            border-radius: 30px;
            background: rgba(255, 255, 255, .15);
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

        .logo {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            background: rgba(255, 255, 255, .15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin-bottom: 25px;
        }

        .left-panel h1 {
            font-size: 42px;
            margin-bottom: 15px;
        }

        .left-panel p {
            line-height: 1.8;
            opacity: .9;
        }

        .right-panel {
            background: white;
            padding: 40px;
        }

        .header {
            margin-bottom: 25px;
        }

        .header h2 {
            color: var(--dark);
            margin-bottom: 8px;
        }

        .header p {
            color: var(--gray);
            font-size: 14px;
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .input-group {
            margin-bottom: 18px;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
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
            border: none;
            cursor: pointer;
            padding: 16px;
            border-radius: 12px;
            color: white;
            font-size: 15px;
            font-weight: 600;
            background:
                linear-gradient(135deg,
                    var(--primary),
                    var(--secondary));
            transition: .3s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .links {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
        }

        .links a {
            text-decoration: none;
            color: var(--primary);
            font-size: 14px;
            font-weight: 500;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .info {
            margin-top: 25px;
            padding: 15px;
            border-radius: 12px;
            background: #eef2ff;
            border-left: 4px solid var(--primary);
            color: #4338ca;
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
                font-size: 30px;
            }

        }

        @media(max-width:600px) {

            .row {
                grid-template-columns: 1fr;
            }

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

            <div class="logo">👨‍👩‍👧</div>

            <h1>Créer votre compte Parent</h1>

            <p>
                Créez votre espace parent afin de gérer votre profil,
                suivre les activités de vos enfants et créer autant
                de comptes enfants que nécessaire.
            </p>

        </div>

        <div class="right-panel">

            <div class="header">
                <h2>Inscription Parent</h2>
                <p>
                    Remplissez les informations ci-dessous pour créer votre compte.
                </p>
            </div>

            <?php if(!empty($msg)) { ?>
            <div style="
                background:#fee2e2;
                color:#b91c1c;
                padding:12px;
                border-radius:10px;
                margin-bottom:20px;">
                <?= htmlspecialchars($msg) ?>
            </div>
            <?php } ?>

            <form method="POST">

                <div class="row">

                    <div class="input-group">
                        <label>Nom</label>
                        <input type="text" placeholder="Votre nom" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" name="last_name">
                    </div>

                    <div class="input-group">
                        <label>Prénom</label>
                        <input type="text" placeholder="Votre prénom" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" name="first_name">
                    </div>

                </div>

                <div class="input-group">
                    <label>Code Parent</label>
                    <input type="text" placeholder="Choisissez un code parent unique" value="<?= htmlspecialchars($_POST['codeparent'] ?? '') ?>" name="codeparent">
                </div>

                <div class="input-group">
                    <label>Login</label>
                    <input type="text" placeholder="Votre identifiant" value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" name="login">
                </div>

                <div class="input-group">
                    <label>Adresse e-mail</label>
                    <input type="email" placeholder="exemple@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" name="email">
                </div>

                <div class="row">

                    <div class="input-group">
                        <label>Mot de passe</label>
                        <input type="password" placeholder="********" name="password">
                    </div>

                    <div class="input-group">
                        <label>Confirmer le mot de passe</label>
                        <input type="password" placeholder="********" name="password2">
                    </div>

                </div>

                <button class="btn" type="submit">
                    Créer mon compte Parent
                </button>

            </form>

            <div class="links">

                <a href="<?= $GLOBALS['LocalHome'] ?>login/">
                    ← Retour à la connexion
                </a>

                <a href="<?= $GLOBALS['LocalHome'] ?>resetpassword/">
                    Mot de passe oublié ?
                </a>

            </div>

            <div class="info">
                Après création de votre compte parent, vous pourrez
                vous connecter et créer les comptes de vos enfants.
            </div>

        </div>

    </div>

</body>

</html>