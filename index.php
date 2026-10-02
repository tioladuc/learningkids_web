<?php
// Source - https://stackoverflow.com/a/6768831
// Posted by ax., modified by community. See post 'Timeline' for change history
// Retrieved 2026-08-22, License - CC BY-SA 4.0
$page = $_GET['page'] ?? 'home';
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/

if($page == "home") {
    require_once("home.php");
    exit;
}



else if($page == "calcul") {
    //echo file_exists("calcul/index.php") ? ("Exist calcul/index.php") : ("File not found: calcul/index.php");
    include_once("calcul/index.php");
    //echo file_exists("calcul/index.php") ? ("Exist calcul/index.php") : ("File not found: calcul/index.php");
    //print_r($_GET);echo "asdfsdffsd fsdf sdf sdfsdfsdfsdfd===";//exit;
    
    exit;
}
else if($page == "children") {
    require_once("children/index.php");
    exit;
}
else if($page == "comprehension") {
    include_once("comprehension/index.php");
    exit;
}
else if($page == "dictation") {
    require_once("dictation/index.php");
    exit;
}

else if($page == "dictee") {
    require_once("dictee/index.php");
    exit;
}
else if($page == "epreuves") {
    require_once("epreuves/index.php");
    exit;
}
else if($page == "exposes") {
    require_once("exposes/index.php");
    exit;
}
else if($page == "selfdictee") {
    require_once("selfdictee/index.php");
    exit;
}
else if($page == "situationprobleme") {
    require_once("situationprobleme/index.php");
    exit;
}
else if($page == "talk") {
    require_once("talk/index.php");
    exit;
}
else if($page == "connect") {
    require_once("connect/index.php");
    exit;
}

