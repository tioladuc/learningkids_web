<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
 }
require_once( __DIR__ . '/core/dbcore.php');
require_once( __DIR__ . '/core/session.php');
require_once( __DIR__ . '/core/menu.php');

if (!isset($_SESSION['current_id'])) {
    header("Location: ". Settings::getHomePage() ."connect/login/");
    exit;
}

//print_r($_SESSION[$_SESSION['current_id']]['courses']); exit;

function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <link rel="icon" type="image/x-icon" href="<?= Settings::getHomePage() ?>favicon/favicon.ico">
    <link rel="icon" type="image/png" href="<?= Settings::getHomePage() ?>favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" href="<?= Settings::getHomePage() ?>favicon/favicon-16x16.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Accueil</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: #f4f6f9;
        }

        .navbar {
            background: #0d6efd;
        }

        .hero {
            background: linear-gradient(135deg, #0d6efd, #4dabff);
            color: white;
            border-radius: 20px;
            padding: 45px;
            margin-top: 30px;
        }

        .card-action {
            transition: .3s;
            border: none;
            border-radius: 15px;
        }

        .card-action:hover {
            transform: translateY(-6px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, .15);
        }

        .icon {
            font-size: 45px;
            color: #0d6efd;
        }

        .stat {
            text-align: center;
            padding: 25px;
        }

        .stat h2 {
            font-weight: bold;
        }

        footer {
            margin-top: 50px;
            background: #212529;
            color: white;
            padding: 20px;
        }
    </style>

</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark">

        <div class="container">

            <a class="navbar-brand fw-bold" href="#">
                <i class="fa-solid fa-house"></i>
                Mon Application
            </a>

            <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="menu">

                <ul class="navbar-nav ms-auto">

                    <li class="nav-item">
                        <a class="nav-link active" href="#">
                            Accueil
                        </a>
                    </li>

                    <?php if(SessionHandle::isParent() && !SessionHandle::isModeParentWorkingChild()) { ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= Settings::getHomePage() ?>/children/manage/">
                            Enfants
                        </a>
                    </li>

                    <?php } else if(SessionHandle::isParent() && SessionHandle::isModeParentWorkingChild()) {?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= Settings::getHomePage() ?>connect/backasparent/">
                            Revenir Mode parent Simple
                        </a>
                    </li>
                    <?php } ?>

                    <li class="nav-item">
                        <a class="nav-link text-warning" href="<?= Settings::getHomePage() ?>connect/logout/">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            Déconnexion
                        </a>
                    </li>

                </ul>

            </div>

        </div>

    </nav>

    <div class="container">

        <div class="hero shadow">

            <h1 class="display-5">

                Bonjour
                <?= h($_SESSION['full_name']) ?>

                👋

            </h1>
            <p class="lead mt-3"><strong style="color:red;"><?=  $_GET['msg']??''  ?></strong></p>
            <?php if(SessionHandle::isParent()) { ?>
            <p class="lead mt-3">

                Bienvenue sur votre espace personnel.

                Vous pouvez gérer vos informations, consulter vos enfants, accéder à vos paramètres et utiliser toutes les fonctionnalités disponibles.

            </p>
            <?php if(SessionHandle::isParent()) {?>
            <a href="<?= Settings::getHomePage() ?>children/manage/" class="btn btn-light btn-lg mt-3">

                <i class="fa-solid fa-users"></i>

                Gérer les enfants

            </a>
            <?php } ?>
            <?php } ?>

        </div>

        <div class="row mt-5">




            <?php
                Menu::menuStaticMenu("");
                $elmentsMenu = explode("<li>", trim($GLOBALS['menu']));
                for ($i=0; $i < count($elmentsMenu) ; $i++) { 
                    $composant = trim(str_replace("<\li>", "", $elmentsMenu[$i]));
                    if($composant != "") { 
                        // Extraire le href
                        preg_match('/href="([^"]+)"/', $composant, $url);
                        $url = $url[1] ?? '';

                        // Extraire la balise <i>...</i>
                        preg_match('/(<i\b[^>]*><\/i>)/i', $composant, $icone);
                        $icone = $icone[1] ?? '';

                        // Extraire le texte
                        preg_match('/<\/i>\s*(.*?)\s*<\/a>/i', $composant, $texte);
                        $texte = trim($texte[1] ?? '');

                        $isChildren = str_contains($elmentsMenu[$i], "/children/");
                        $isConnect = str_contains($elmentsMenu[$i], "/connect/") || str_contains($elmentsMenu[$i], Settings::getHomePage() . "index.php");
                        
                        ?>
                        <div class="col-md-4" style="margin-bottom: 20px; height:150px;">

                            <a style="text-decoration-color: rgba(13, 110, 253, 0);" href="<?= $url ?>">
                                <div class="card stat shadow-sm" style="background-color: <?php echo $isChildren ? "#dbb195;":($isConnect ? "#95dbc0;" : "#98bfe0;") ?> " >

                                    <?= $icone ?>

                                    <h2><?= $texte ?></h2>

                                    <!--p>Connexion active</p-->

                                </div>
                            </a>

                        </div>
                    <?php
                    }
                }
            
            ?>




        </div>

























        <div class="row mt-5 g-4">
            <div class="col-md-4">

                <div class="card stat shadow-sm">

                    <i class="fa-solid fa-user icon mb-3"></i>

                    <h2><?= h($_SESSION['full_name']) ?></h2>

                    <p>Compte connecté</p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="card stat shadow-sm">

                    <i class="fa-solid fa-shield-halved icon mb-3"></i>

                    <h2>Sécurisé</h2>

                    <p>Connexion active</p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="card stat shadow-sm">

                    <i class="fa-solid fa-clock icon mb-3"></i>

                    <h2><?= date("H:i") ?></h2>

                    <p>Heure actuelle</p>

                </div>

            </div>

        </div>


        <?php if(SessionHandle::isParent()) { ?>
        <div class="row mt-5 g-4">

            <div class="col-md-4">

                <div class="card card-action shadow h-100">

                    <div class="card-body text-center">

                        <i class="fa-solid fa-users icon mb-3"></i>

                        <h4>Gestion des enfants</h4>

                        <p>

                            Ajouter, modifier ou supprimer les enfants associés à votre compte.

                        </p>

                        <a href="<?= Settings::getHomePage() ?>children/manage/" class="btn btn-primary">

                            Accéder

                        </a>

                    </div>

                </div>

            </div>

            <div class="col-md-4">

                <div class="card card-action shadow h-100">

                    <div class="card-body text-center">

                        <i class="fa-solid fa-gear icon mb-3"></i>

                        <h4>Administration</h4>

                        <p>

                            Consultez les données de votre espace et effectuez les opérations disponibles.

                        </p>

                        <a href="<?= Settings::getHomePage() ?>manage/" class="btn btn-success">

                            Ouvrir

                        </a>

                    </div>

                </div>

            </div>

            <div class="col-md-4">

                <div class="card card-action shadow h-100">

                    <div class="card-body text-center">

                        <i class="fa-solid fa-user-gear icon mb-3"></i>

                        <h4>Mon profil</h4>

                        <p>

                            Modifiez vos informations personnelles et vos paramètres.

                        </p>

                        <a href="<?= Settings::getHomePage() ?>profile/" class="btn btn-warning">

                            Modifier

                        </a>

                    </div>

                </div>

            </div>

        </div>
        <?php } ?>

    </div>

    <footer class="text-center mt-5">

        <div class="container">

            © <?= date('Y') ?>

            Tous droits réservés.

        </div>

    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>