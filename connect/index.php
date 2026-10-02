<?php
include_once( __DIR__ . '/../core/setting.php');
$GLOBALS['LocalHome'] = Settings::getHomePage() . "connect/";
$page = $_GET['subpage'] ?? 'logout';

if($page == "backasparent") {
    require_once("backasparent.php");
    exit;
}
else if($page == "login") {
    require_once("login.php");
    exit;
}
else if($page == "logout") {
    require_once("logout.php");
    exit;
}
else if($page == "newaccount") {
    require_once("newparentaccount.php");
    exit;
}
else if($page == "resetpassword") {
    require_once("resetpassword.php");
    exit;
}

SessionHandle::displayNotAutorizedBecauseOfTimeOutMobile();