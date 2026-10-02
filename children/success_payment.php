<?php
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../vendor/autoload.php';
require_once(__DIR__ . "/../core/dbcore.php");
require_once(__DIR__ . "/../core/session.php");
require_once(__DIR__ . "/../core/setting.php");
require_once(__DIR__ . "/../core/email_sender.php");
require_once(__DIR__ . "/basket.php");

SessionHandle::checkingPrivilegeParent();

$session = new stdClass();
function extractGet(string $key):string {
    preg_match('/'. $key .'=([^&]+)/', $_SERVER['REQUEST_URI'], $matches);
    return $matches[1] ?? '';
}
//echo "============== ". extractGet('amount') . " ==>> " . $_SERVER['REQUEST_URI'] ." =================";
if(SessionHandle::isTrialActive() && (extractGet('amount') == 0 || extractGet('amount') == "0" )) {
    $session->payment_status = 'paid';    
}
else {
    \Stripe\Stripe::setApiKey(Settings::$stripeKey);
    $session = \Stripe\Checkout\Session::retrieve( extractGet('session_id') /*$session_id/*$_GET['session_id']*/);
}

$pdo = connectBD();
$basket = extractGet('basket'); //$_GET['basket'];
$basketPattern = "success_payment.php?basket=done";

if(str_contains($_SERVER['REQUEST_URI'], $basketPattern) || $_GET['subpage'] == "success_payment") {
    basketPaid($pdo, $session);
} else {
    itemPaid($pdo, $session);
}

function itemPaid($pdo, $session) {
    $code = extractGet('code'); //$_GET['code'];
    $childid = extractGet('childid'); //$_GET['childid'];
    $amount = extractGet('amount'); //$_GET['amount'];
    $startDate = str_replace("%20", " ", extractGet('startDate'));//substr(str_replace("%20", " ", extractGet('startDate')), 0, -3); //$_GET['startDate'];
    $endDate = str_replace("%20", " ", extractGet('endDate')); //substr(str_replace("%20", " ", extractGet('endDate')), 0, -3); //$_GET['endDate'];
    $msg = extractGet('msg'); //$_GET['msg'];
    $name = extractGet('name'); //$_GET['name'];
    $course_child_id = $_GET['course_child_id'];
    //echo $_SERVER['REQUEST_URI'] . "<br>"; echo $session->payment_status; exit;
    if ($session->payment_status == 'paid') {
        //"children/success_payment.php?code=$code&childid=$childid&amount=". $course['amount'] ."&startDate=". $startDate->format('Y-m-d H:i:s') ."&endDate=". $expiryDate->format('Y-m-d H:i:s') ."&msg=Paiement reussi",   

        $queryUpdateValidity = "UPDATE learn4kids_child_courses SET is_paid=1, expiry_date='$endDate', is_active=1 WHERE id=$course_child_id";
        //echo "*******************$queryUpdateValidity***********************";
        $stmt = $pdo->prepare($queryUpdateValidity);
        $stmt->execute();

        $queryT = "SELECT * FROM learn4kids_children WHERE id = '$childid'";
        $stmt = $pdo->prepare($queryT);
        $stmt->execute();
        $child = $stmt->fetch(PDO::FETCH_ASSOC);

        $queryPayment = "INSERT INTO learn4kids_payments(parent_id, child_id, course_code, amount, is_paid, payment_date, startperiod, endperiod)
                                VALUES('" . $child['parent_id'] . "', '$childid', '$code', $amount, 1, now(), '$startDate', '$endDate')";
        //echo "88888888888888888888888888888  $queryPayment  888888888888888888888888888888";
        $stmt = $pdo->prepare($queryPayment);
        $stmt->execute();
        EmailSender::sendPaymentEmailUniqueCourse($course_child_id);
        header("Location: " . $GLOBALS['LocalHome'] . "courses/childid=$childid/?msg=$msg");
        // Save to database here
    } else {

        header("Location: " . $GLOBALS['LocalHome'] . "courses/childid=$childid/?msg=Echec de paiement");
    }
}

function basketPaid($pdo, $session) {
    if ($session->payment_status == 'paid') {
        Basket::paymentOfBasket();
        header("Location: " . $GLOBALS['LocalHome'] . "manage/?msg=Paiement reussi");
    } else {
        header("Location: " . $GLOBALS['LocalHome'] . "manage/?msg=Echec de paiement");
    }
}
