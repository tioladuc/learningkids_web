<?php
include_once( __DIR__ . '/../core/setting.php');
$GLOBALS['LocalHome'] = Settings::getHomePage() . "situationprobleme/";
$GLOBALS['CourseCode'] = "C006";

$page = $_GET['subpage'] ?? 'work';

if($page == "work") {
    require_once("work.php");
    exit;
}
else if($page == "manage") {
    require_once("manage.php");
    exit;
}
SessionHandle::displayNotAutorizedBecauseOfTimeOutMobile();