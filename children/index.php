<?php
include_once( __DIR__ . '/../core/setting.php');
$GLOBALS['LocalHome'] = Settings::getHomePage() . "children/";

$page = $_GET['subpage'] ?? 'manage';

if($page == "manage") {
    require_once("manage.php");
    exit;
}
else if($page == "basket") {
    require_once("basket.php");
    exit;
}
else if($page == "courses") {
    require_once("courses.php");
    exit;
}
else if($page == "paymenthistory") {
    require_once("paymenthistory.php");
    exit;
}
else if($page == "childrenstats") {
    require_once("childrenstats.php");
    exit;
}
else if($page == "payments") {
    require_once("payments.php");
    exit;
}
else if($page == "childreport") {
    require_once("childreport.php");
    exit;
}
else if($page == "success_payment") {
    require_once("success_payment.php");
    exit;
}
SessionHandle::displayNotAutorizedBecauseOfTimeOutMobile();