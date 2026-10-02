<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
 }
require_once(__DIR__ . "/../core/dbcore.php");
require_once(__DIR__ . "/../core/session.php");
require_once(__DIR__ . "/../core/menu.php");
require_once(__DIR__ . "/../core/setting.php");
require_once(__DIR__ . "/basket.php");
require_once (__DIR__ . "/../core/stripe_checkout.php");

SessionHandle::checkingPrivilegeParent();

$annualReductionMessage = "<strong>&#128073; Vous bénéficiez d'une reduction de " . Settings::annualReduction() . "% si vous prenez un abonnement mensuel.</strong><br>";
$trialMessage = SessionHandle::isTrialActive() ? ("<strong style='color:red;'>&#128073; Vous bénéficiez de ". SessionHandle::getTrialDaysLeft() . " jour(s) de période d'essai. Profitez !</strong><br>") : "";
$messageRetrait = "<strong style='color:red;'>&#128073; Si vous retirer le cours, vous perdrez le reste de votre période valide.</strong><br>";

$childid = $_GET['childid'];
$pdo = connectBD();
$message = str_replace("-", " ", explode('?', $_GET['msg'] ?? "")[0]);

$stmt = $pdo->prepare("SELECT * FROM learn4kids_children WHERE id ='$childid'");
$stmt->execute();
$child = $stmt->fetch(PDO::FETCH_ASSOC);
$_GET['name'] = $child['name'];



function displayPaymentChoice($core, $forceDeactivateTrial) {
    $html = "<div class=\"payment-options\">";

    $trial = "<div class=\"payment-trial\">
                
                <label for=\"trial$core\">
                    <input type=\"radio\" id=\"trial$core\" name=\"mode\" value=\"trial\" checked>
                    <!--div class=\"circle\"></div-->
                    <span>Essai</span>
                </label>
            </div>";
    $monthlyCheck = "<div class=\"payment-month\">
                    
                    <label for=\"month$core\">
                        <input type=\"radio\" id=\"month$core\" name=\"mode\" value=\"month\" checked>
                        <!--div class=\"circle\"></div-->
                        <span>Mensuel</span>
                    </label>
                </div>";
    $monthly = "<div class=\"payment-month\">
                    
                    <label for=\"month$core\">
                        <input type=\"radio\" id=\"month$core\" name=\"mode\" value=\"month\">
                        <!--div class=\"circle\"></div-->
                        <span>Mensuel</span>
                    </label>
                </div>";
    $annually = "<div class=\"payment-annual\">
                    
                    <label for=\"annual$core\">
                        <input type=\"radio\" id=\"annual$core\" name=\"mode\" value=\"annual\">
                        <!--div class=\"circle\"></div-->
                        <span>Annuel</span>
                    </label>
                </div>";

    if( SessionHandle::isTrialActive() && $forceDeactivateTrial == false ) {
        $html .= $trial;
        $html .= $monthly;
        $html .= $annually;
    }
    else {
        $html .= $monthlyCheck;
        $html .= $annually;
    }

    $html .= "</div>";
    return $html;
}


function h($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

if (isset($_POST['operation'])) {
    $code = $_POST['code'];
    try {    
        //print_r($_POST); exit();
        if($_POST['mode'] == "trial" && SessionHandle::isTrialActive() ) {
            $queryAjust = ""; $message = "";
            if($_POST['operation']=='choosePay') {
                $message = "Cours pris en mode essai!";
                $queryAjust = " INSERT INTO learn4kids_child_courses(child_id, course_code, is_paid, picked_date, expiry_date, is_active) VALUES ('$childid', '$code', 1, now(), '". $_SESSION['trial_end_date'] ."', 1)";
            }
            else if($_POST['operation']=='payment') {
                $message = "Cours pris en mode essai!";
                $queryAjust = " UPDATE learn4kids_child_courses SET is_paid=1, expiry_date='". $_SESSION['trial_end_date'] ."' WHERE child_id='$childid' AND course_code='$code' AND is_active=1";
            }
            if($queryAjust != "") {
                $stmt = $pdo->prepare($queryAjust);
                $stmt->execute();
                header("Location: " . $GLOBALS['LocalHome'] . "courses/?childid=$childid&msg=$message");
                exit;
            }
            
        }
        if ($_POST['operation'] == "choosePay") {
            $queryInsert = " INSERT INTO learn4kids_child_courses(child_id, course_code, is_paid, picked_date, expiry_date, is_active) VALUES ('$childid', '$code', 0, now(), null, 1)";
            $stmt = $pdo->prepare($queryInsert);
            $stmt->execute();
            $_POST['operation'] = "payment";
            $_POST['course_child_id'] = $pdo->lastInsertId();
        } 

        if ($_POST['operation'] == "pick") {
            $queryInsert = " INSERT INTO learn4kids_child_courses(child_id, course_code, is_paid, picked_date, expiry_date, is_active) VALUES ('$childid', '$code', 0, now(), null, 1)";
            $stmt = $pdo->prepare($queryInsert);
            $stmt->execute();
            $message = "Cours reservé avec succès!";
        } 
        else if($_POST['operation'] == "retirer") {
            $course_child_id = $_POST['course_child_id'];
            $queryDelete = "DELETE FROM learn4kids_child_courses WHERE course_code='$code' AND child_id='$childid' AND id='$course_child_id'";
            $stmt = $pdo->prepare($queryDelete);
            $stmt->execute();
            $message = "Cours retiré avec succès!";
        }
        else if($_POST['operation'] == "retrait") {
            $course_child_id = $_POST['course_child_id'];
            $queryDelete = "UPDATE learn4kids_child_courses SET is_active = 0 WHERE course_code='$code' AND child_id='$childid' AND id='$course_child_id'";
            $stmt = $pdo->prepare($queryDelete);
            $stmt->execute();
            $message = "Cours retiré avec succès!";
        }
        else if ($_POST['operation'] == "panier") {
            //$queryInsert = " INSERT INTO learn4kids_child_courses(child_id, course_code, is_paid, picked_date, expiry_date, is_active) VALUES ('$childid', '$code', 0, now(), null, 1)";
            //$stmt = $pdo->prepare($queryInsert);
            //$stmt->execute();
            Basket::addToBasket($childid, $code, $_POST['mode']);
            //$_POST['mode'] ===trial====month=====annual=====
            $message = "Cours ajouté au panier avec succès!";
        }
        else {
            $stmt = $pdo->prepare("SELECT learn4kids_courses.*, learn4kids_child_courses.expiry_date FROM learn4kids_courses, learn4kids_child_courses WHERE learn4kids_child_courses.Course_code = learn4kids_courses.code AND learn4kids_child_courses.id=".$_POST['course_child_id']." AND learn4kids_courses.code='$code'");
            $stmt->execute();
            $course = $stmt->fetch(PDO::FETCH_ASSOC);
            $startDate = null;
            $course_child_id = $_POST['course_child_id'];
            
            if ($_POST['operation'] == "increase") {
                $startDate = new DateTime($course['expiry_date']);
            } else if ($_POST['operation'] == "payment") {
                $startDate = new DateTime();
            }
            //print_r($_POST); print_r($course); echo "====$startDate==="; exit;
            $expiryDate = clone $startDate;
            $mode = $_POST['mode'];

            if( $_POST['mode']=="trial" ) {
                $expiryDate->modify('+'. SessionHandle::getTrialDaysLeft() .' days');
            }else if( $_POST['mode']=="month" ) {
                $expiryDate->modify('+1 month');
            }else if( $_POST['mode']=="annual" ) {
                $expiryDate->modify('+12 month');
            }
            //$expiryDate = Settings::getNextDateFromPeriod($startDate, $course['validity']);
            $amount = $mode=="trial" ? 0 : ( $mode=="month" ? $course['amount'] : ($course['amount']*12*(100-Settings::annualReduction()) / 100) );
            $urlRedirection = Settings::getHomePage() . "children/success_payment/?code=$code&childid=$childid&amount=". $amount ."&startDate=". $startDate->format('Y-m-d H:i:s') ."&endDate=". $expiryDate->format('Y-m-d H:i:s') ."&course_child_id=$course_child_id&msg=Paiement-reussi";
            if(SessionHandle::isTrialActive() && $amount == 0) {
                $urlRedirection = Settings::getHomePage() . "children/success_payment/?code=$code&childid=$childid&amount=0&startDate=". $startDate->format('Y-m-d H:i:s') ."&endDate=". $expiryDate->format('Y-m-d H:i:s') ."&course_child_id=$course_child_id&msg=Paiement-reussi";
                header("Location: " . $urlRedirection);
                exit;
            }
            launchStripePayment(
                $amount/*$course['amount']*/,
                $urlRedirection,
                $GLOBALS['LocalHome'] . "courses/?childid=$childid&msg=Paiement-echoue",
                "Paiement de cours sur Learn4Kids"
            );
            exit;
        }
    } catch (PDOException $e) { // Catch only the database exception
        
    }
    
}

//=======================

$basket_selected_courses = "(" . (isset($_SESSION['basket_selected_courses'][$childid]) ? $_SESSION['basket_selected_courses'][$childid] . "''" : "''") . ")";
$stmt = $pdo->prepare("
SELECT *
FROM learn4kids_courses
WHERE is_active=1 AND 
code NOT IN (SELECT DISTINCT course_code FROM learn4kids_child_courses WHERE child_id = '$childid' AND is_active = 1) AND 
code NOT IN $basket_selected_courses 
ORDER BY level,name
");
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

//===============================
$stmt = $pdo->prepare("
SELECT course.*, taken.expiry_date, taken.picked_date, taken.id as course_child_id
FROM learn4kids_courses course JOIN learn4kids_child_courses taken ON (course.code = taken.course_code)
WHERE course.is_active=1 AND taken.is_active=1 AND taken.child_id = '$childid' AND 
code IN (SELECT DISTINCT course_code FROM learn4kids_child_courses WHERE child_id = '$childid' AND is_active = 1 AND expiry_date IS NOT NULL AND expiry_date > now()) AND 
code NOT IN $basket_selected_courses 
ORDER BY level,name
");
$stmt->execute();
$coursesSouscris = $stmt->fetchAll(PDO::FETCH_ASSOC);


//===============================
/*echo "*******************SELECT course.*, taken.expiry_date, taken.picked_date, taken.id as course_child_id
FROM learn4kids_courses course JOIN learn4kids_child_courses taken ON (course.code = taken.course_code)
WHERE course.is_active=1 AND taken.is_active=1 AND taken.child_id = '$childid' AND 
code IN (SELECT DISTINCT course_code FROM learn4kids_child_courses WHERE child_id = '$childid' AND is_active = 1 AND expiry_date IS NULL)
ORDER BY level,name**********************";*/
$stmt = $pdo->prepare("
SELECT course.*, taken.expiry_date, taken.picked_date, taken.id as course_child_id
FROM learn4kids_courses course JOIN learn4kids_child_courses taken ON (course.code = taken.course_code)
WHERE course.is_active=1 AND taken.is_active=1 AND taken.child_id = '$childid' AND 
code IN (SELECT DISTINCT course_code FROM learn4kids_child_courses WHERE child_id = '$childid' AND is_active = 1 AND expiry_date IS NULL) AND 
code NOT IN $basket_selected_courses 
ORDER BY level,name
");
$stmt->execute();
$coursesPicked = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <link rel="icon" type="image/x-icon" href="<?= Settings::getHomePage() ?>favicon/favicon.ico">
    <link rel="icon" type="image/png" href="<?= Settings::getHomePage() ?>favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" href="<?= Settings::getHomePage() ?>favicon/favicon-16x16.png">
    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Nos cours</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <?php echo Menu::menuStaticScriptMenu(); ?>
    <?php echo Menu::menuStaticCSS(); ?>
    <style>
        body {
            background: #f5f7fb;
        }

        .hero {

            background: linear-gradient(135deg, #0d6efd, #4dabff);

            color: white;

            border-radius: 20px;

            padding: 45px;

            margin-bottom: 40px;

        }

        .course-card {

            border: none;

            border-radius: 18px;

            transition: .3s;

            overflow: hidden;

        }

        .course-card:hover {

            transform: translateY(-8px);

            box-shadow: 0 15px 35px rgba(0, 0, 0, .18);

        }

        .price {

            font-size: 28px;

            font-weight: bold;

            color: #0d6efd;

        }

        .level {

            font-size: 13px;

        }

        .description {

            min-height: 120px;

        }

        .btn-course {

            width: 100%;
            margin-top: 5px;
            margin-bottom: 5px;

        }

        footer {

            margin-top: 60px;

            background: #212529;

            color: white;

            padding: 20px;

        }
    </style>

    
    <style>
        .payment-options{
            display:flex;
            justify-content:space-around;
            align-items:center;
            margin:5px auto;
            max-width:1200px;
            font-family:Arial, Helvetica, sans-serif;
        }

        .payment-option{
            text-align:center;
        }

        .payment-option input[type="radio"]{
            display:none;
        }

        .payment-option label{
            cursor:pointer;
            display:flex;
            flex-direction:column;
            align-items:center;
        }

        .circle{
            width:46px;
            height:46px;
            border:4px solid #000;
            border-radius:50%;
            position:relative;
            margin-bottom:18px;
        }

        .circle::after{
            content:"";
            position:absolute;
            width:22px;
            height:22px;
            border-radius:50%;
            background:#4654d8;
            top:50%;
            left:50%;
            transform:translate(-50%,-50%) scale(0);
            transition:0.2s;
        }

        .payment-option input[type="radio"]:checked + label .circle::after{
            transform:translate(-50%,-50%) scale(1);
        }

        .payment-option span{
            font-size:56px;
            font-weight:bold;
            color:#4654d8;
        }

        @media(max-width:768px){

            .payment-options{
                flex-direction:column;
                gap:35px;
            }

            .payment-option span{
                font-size:38px;
            }

        }
    </style>
    <script>
        function validateOperation(button, operation) {

            // Find the parent form
            const form = button.closest("form");

            if (!form) {
                console.error("No parent form found.");
                return;
            }

            // Find the hidden input
            const operationInput = form.querySelector('input[name="operation"]');

            if (!operationInput) {
                console.error('Input with name="operation" not found.');
                return;
            }

            // Set the value
            operationInput.value = operation;

            // Submit the form
            form.submit();
        }
        function validateAction(button, operation) {
            if (confirm("En retirant le cours, vous perdrez le reste de jours disponibles avant l'expiration. Êtes vous OK avec cela?")) {
                validateOperation(button, operation);
                return true;
            }
            else {
                return false;
            }
        }
    </script>

</head>

<body>

    <div class="container py-4">
        <?php
        $mnuStatic = "";
        echo Menu::menuStaticMenu($mnuStatic);
        ?>
        
        <div class="hero shadow">

            <h1>

                <i class="fa-solid fa-graduation-cap"></i>

                Nos cours pour [<strong> <?php echo $_GET['name'] ?? ""; ?> </strong>]

            </h1>

            <p class="lead">

                Découvrez nos formations disponibles et choisissez le cours qui correspond à votre niveau. 

            </p>
            <div class="row">
                <div class="col-4" style="justify-content: center;display: grid; place-items: center;">
                    <?php echo Basket::displayIconBasket() ?></div>
                <div class="col-4" style="justify-content: center;display: grid; place-items: center;">
                    <?php echo SessionHandle::displayTrialMessage(); ?></div>
                <div class="col-4" style="justify-content: center;display: grid; place-items: center;">
                    <a href="<?= $GLOBALS['LocalHome'] ?>manage/" style="color:white; <?= Settings::buttonDimensionStyle() ?>" class="btn btn-success">Retour Page Enfants</a></div></div>
             

        </div>
        <div class="row"><center><span style="color:red;"><?php echo $message ; ?></span></center></div>
        <div class="row g-4">
            <div class="col-12" style="background-color:#0d6efd; color:white; border-radius:10px;">
                <center>
                    <h2>Cours disponibles à prendre</h2>
                </center>
            </div>
        </div>
        <div class="row g-4">

            <?php foreach ($courses as $course): ?>
                
                <div class="col-lg-4 col-md-6">
                    
                    <div class="card h-100 course-card shadow-sm" style="background-color: #2a75ee1e;">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <h4>

                                    <?= h($course['name']) ?>

                                </h4>

                                <span class="badge bg-primary level">

                                    <?= h($course['level']) ?>

                                </span>

                            </div>

                            <hr>

                            <div class="price">

                                <?= number_format($course['amount'], 2, ",", " ") ?>

                                $

                            </div>

                            <p class="text-muted">

                                <i class="fa-regular fa-clock"></i>

                                Validité :

                                <strong>

                                    <?= h($course['validity']) ?>

                                </strong>

                            </p>

                            <div class="description">

                                <?= nl2br(h($course['description'])) ?>

                            </div>
                            <div> <?= $annualReductionMessage ?> <?= $trialMessage ?> </div>
                        </div>

                        <div class="card-footer bg--white border-0" style="background-color: #2a75ee1e;">
                            <form action="" method="post">
                                <div class="row">
                                    <div class="col-12"><?php echo displayPaymentChoice($course['code'], false) ?></div>
                                </div>
                                <input type="hidden" name="operation" value="pick" />
                                <input type="hidden" name="code" value="<?php echo $course['code'] ?>" />
                                <div class="row">
                                    <div class="col-4">
                                        
                                            <!--input type="hidden" name="operation" value="pick" /-->
                                            <!--input type="hidden" name="code" value="<?php echo $course['code'] ?>" /-->
                                            <button type="submit" class="btn btn-primary btn-course" onclick="return validateOperation(this, 'pick')">
                                                <i class="fa-solid fa-play"></i>
                                                Choisir le cours
                                            </button>
                                        </form>
                                    </div>
                                    <div class="col-4">
                                        <button type="submit" class="btn btn-primary btn-course" onclick="return validateOperation(this, 'choosePay')">
                                                <i class="fa-solid fa-play"></i>
                                                Payer le cours
                                            </button>
                                    </div>
                                    <div class="col-4">
                                        
                                            <!--input type="hidden" name="operation" value="panier" /-->
                                            <!--input type="hidden" name="code" value="<?php echo $course['code'] ?>" /-->
                                            <button type="submit" class="btn btn-success btn-course" onclick="return validateOperation(this, 'panier')">
                                                <i class="fa-solid fa-shopping-basket"></i>
                                                Ajouter au panier
                                            </button>
                                        
                                    </div>
                                    
                                </div>
                            </form>

                        </div>

                    </div>
                    

                </div>
                
            <?php endforeach; ?>

            <?php if (empty($courses)): ?>

                <div class="col-12">

                    <div class="alert alert-warning">

                        Aucun cours n'est disponible actuellement.

                    </div>

                </div>

            <?php endif; ?>

        </div>







        <br /><br />
        <div class="row g-4">
            <div class="col-12" style="background-color:black; color:white; border-radius:10px;">
                <center>
                    <h2>Cours déjà souscrits</h2>
                </center>
            </div>
        </div>
        <div class="row g-4">

            <?php foreach ($coursesSouscris as $course): ?>

                <div class="col-lg-4 col-md-6">

                    <div class="card h-100 course-card shadow-sm" style="background-color: #21252923;">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <h4>

                                    <?= h($course['name']) ?>

                                </h4>

                                <span class="badge bg-primary level">

                                    <?= h($course['level']) ?>

                                </span>

                            </div>

                            <hr>

                            <div class="price">

                                <?= number_format($course['amount'], 2, ",", " ") ?>

                                $

                            </div>

                            <p class="text-muted">

                                <i class="fa-regular fa-clock"></i>

                                Validité :

                                <strong>

                                    <?= h($course['validity']) ?>

                                </strong>

                            </p>

                            <div class="description">

                                <?= nl2br(h($course['description'])) ?> <br />
                                <strong>Fin Validité: <i><?= nl2br(h($course['expiry_date'])) ?></i></strong>

                            </div>
                            <div> <?= $annualReductionMessage ?> <?= $messageRetrait ?> </div>
                        </div>

                        <div class="card-footer bg--white border-0" style="background-color: #21252923;">
                            <form action="" method="post">
                                <div class="row">
                                    <div class="col-12"><?php echo displayPaymentChoice($course['code'], true) ?></div>
                                </div>
                                <input type="hidden" name="operation" value="increase" />
                                <input type="hidden" name="code" value="<?php echo $course['code'] ?>" />
                                <input type="hidden" name="course_child_id" value="<?php echo $course['course_child_id'] ?>" />
                                <div class="row">
                                    <div class="col-4">
                                        
                                            <!--input type="hidden" name="operation" value="increase" />
                                            <input type="hidden" name="code" value="<?php echo $course['code'] ?>" />
                                            <input type="hidden" name="course_child_id" value="<?php echo $course['course_child_id'] ?>" /-->
                                            <button type="submit" class="btn btn-primary btn-course" style="background-color:black !important; color:white;" onclick="return validateOperation(this, 'increase')">
                                                <i class="fa-solid fa-play"></i>
                                                Etendre validité
                                            </button>
                                        
                                    </div>
                                    <div class="col-4">
                                        
                                            <!--input type="hidden" name="operation" value="panier" />
                                            <input type="hidden" name="code" value="<?php echo $course['code'] ?>" />                                
                                            <input type="hidden" name="course_child_id" value="<?php echo $course['course_child_id'] ?>" /-->
                                            <button type="submit" class="btn btn-success btn-course" onclick="return validateOperation(this, 'panier')">
                                                <i class="fa-solid fa-shopping-basket"></i>
                                                Ajouter au panier
                                            </button>
                                        
                                    </div>
                                    <div class="col-4">
                                        
                                            <!--input type="hidden" name="operation" value="retrait" />
                                            <input type="hidden" name="code" value="<?php echo $course['code'] ?>" />                                
                                            <input type="hidden" name="course_child_id" value="<?php echo $course['course_child_id'] ?>" /-->
                                            <button type="submit" class="btn btn-danger btn-course" onclick="return validateAction(this, 'retrait')">
                                                <i class="fa-solid fa-trash"></i>
                                                Retirer le cours
                                            </button>
                                        
                                    </div>
                                </div>
                            </form>
                            
                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

            <?php if (empty($coursesSouscris)): ?>

                <div class="col-12">

                    <div class="alert alert-warning">

                        Aucun cours n'est disponible actuellement.

                    </div>

                </div>

            <?php endif; ?>

        </div>
        <br /><br />







        <div class="row g-4">
            <div class="col-12" style="background-color:blueviolet; color:white; border-radius:10px;">
                <center>
                    <h2>Cours choisis et non encore validés</h2>
                </center>
            </div>
        </div>
        <div class="row g-4">

            <?php foreach ($coursesPicked as $course): ?>

                <div class="col-lg-4 col-md-6">

                    <div class="card h-100 course-card shadow-sm" style="background-color: #c472d138;">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <h4>

                                    <?= h($course['name']) ?>

                                </h4>

                                <span class="badge bg-primary level">

                                    <?= h($course['level']) ?>

                                </span>

                            </div>

                            <hr>

                            <div class="price">

                                <?= number_format($course['amount'], 2, ",", " ") ?>

                                $

                            </div>

                            <p class="text-muted">

                                <i class="fa-regular fa-clock"></i>

                                Validité :

                                <strong>

                                    <?= h($course['validity']) ?>

                                </strong>

                            </p>

                            <div class="description">

                                <?= nl2br(h($course['description'])) ?>

                            </div>
                            <div> <?= $annualReductionMessage ?> <?= $trialMessage ?> </div>

                        </div>

                        <div class="card-footer bg--white border-0" style="background-color: #c472d138;">
                            <form action="" method="post">
                                <div class="row">
                                    <div class="col-12"><?php echo displayPaymentChoice($course['code'], false) ?></div>
                                </div>
                                <input type="hidden" name="operation" value="payment" />
                                <input type="hidden" name="code" value="<?php echo $course['code'] ?>" />
                                <input type="hidden" name="course_child_id" value="<?php echo $course['course_child_id'] ?>" />
                                <div class="row">
                                    <div class="col-4">
                                        
                                            <!--input type="hidden" name="operation" value="payment" />
                                            <input type="hidden" name="code" value="<?php echo $course['code'] ?>" />
                                            <input type="hidden" name="course_child_id" value="<?php echo $course['course_child_id'] ?>" /-->
                                            <button type="submit" class="btn btn-primary btn-course" style="background-color:blueviolet !important; color:white;" onclick="return validateOperation(this, 'payment')">
                                                <i class="fa-solid fa-play"></i>
                                                Payer le cours
                                            </button>
                                        
                                    </div>
                                    <div class="col-4">
                                        <button type="submit" class="btn btn-success btn-course" onclick="return validateOperation(this, 'panier')">
                                                <i class="fa-solid fa-shopping-basket"></i>
                                                Ajouter au panier
                                            </button>
                                    </div>
                                    <div class="col-4">
                                        
                                            <!--input type="hidden" name="operation" value="retirer" />
                                            <input type="hidden" name="code" value="<?php echo $course['code'] ?>" />
                                            <input type="hidden" name="course_child_id" value="<?php echo $course['course_child_id'] ?>" /-->
                                            <button type="submit" class="btn btn-danger btn-course" style=" color:white;" onclick="return validateOperation(this, 'retirer')">
                                                <i class="fa-solid fa-trash"></i>
                                                Retirer le cours
                                            </button>
                                        
                                    </div>
                                </div>
                            </form>
                            

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

            <?php if (empty($coursesPicked)): ?>

                <div class="col-12">

                    <div class="alert alert-warning">

                        Aucun cours n'est disponible actuellement.

                    </div>

                </div>

            <?php endif; ?>

        </div>


    </div>

    <footer class="text-center">

        © <?= date('Y') ?> Learn4Kids

    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>