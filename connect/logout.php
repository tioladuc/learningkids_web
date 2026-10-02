<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
 }
/*error_reporting(E_ALL);
ini_set('display_errors', 1);*/
require_once(__DIR__ . '/../core/session.php');

SessionHandle::desactiverSession();


?>