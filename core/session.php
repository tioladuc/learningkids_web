<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
 }
require_once __DIR__ . "/dbcore.php";
require_once __DIR__ . "/setting.php";
require_once __DIR__ . "/basketcore.php";
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

class SessionHandle
{
    public static function checkingPrivilegeParent() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['current_id'])) {
            header("Location: " . Settings::getHomePage() . "connect/login/?msg=Veuillez vous connecter pour accéder");
            exit;
        }
        if(!self::isParent()) {
            self::redirectionHome();
            exit;
        }
    }

    public static function checkingPrivilegeChild() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['current_id'])) {
            header("Location: " . Settings::getHomePage() . "connect/login/?msg=Veuillez vous connecter pour accéder");
            exit;
        }
    }

    public static function checkingPrivilegeChildAdmin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['current_id'])) {
            header("Location: " . Settings::getHomePage() . "connect/login/?msg=Veuillez vous connecter pour accéder");
            exit;
        }
        if(!self::isParent()) {
            self::redirectionHome();
            exit;
        }
        if(!self::isAdministrator()) {
            self::redirectionHome();
            exit;
        }
    }

    public static function chargerImage() {
        $path = dirname(__DIR__) . "/images-animees";
        $files = array_diff(scandir($path), array('.', '..'));
        $_SESSION['images'] = "const images = [";
        for ($i=0; $i <count($files) ; $i++) 
            $_SESSION['images'] .= ($i==0 ? ("\"images-animees/" . $files[$i] . "\"") : (", \"images-animees/" . $files[$i] . "\"") );
        $_SESSION['images'] .= "];";
    }

    public static function childrenCourses($idParent) {
        $queryChildren = "SELECT * FROM learn4kids_children WHERE parent_id = '$idParent'";
        $pdo = connectBD();
        $stmt = $pdo->prepare($queryChildren);
        $stmt->execute([]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if(count($data)>0) {
            $children = "''";
            for ($i=0; $i < count($data) ; $i++) { 
                $_SESSION[ $data[$i]['id'] ]['courses'] = array();
                $children .= ",'" . $data[$i]['id'] . "'";
            }

            $queryCourses = "SELECT * FROM learn4kids_child_courses WHERE child_id IN ($children) AND expiry_date >= now() AND is_active = 1 AND is_paid = 1";
            $stmt = $pdo->prepare($queryCourses);
            $stmt->execute([]);
            $dataCourse = $stmt->fetchAll(PDO::FETCH_ASSOC);

            for ($j=0; $j < count($dataCourse); $j++) { 
                $_SESSION[ $dataCourse[$j]['child_id'] ]['courses'][ $dataCourse[$j]['course_code'] ] = true;
            }
        }
    }

    public static function setSessionParent($parent, $connectByWeb = true)   {
        self::chargerImage();
        $_SESSION['user_type'] = 'parent';
        $_SESSION['parent_id'] = $parent['id'];
        $_SESSION['current_id'] = $parent['id'];
        $_SESSION['codeparent'] = $parent['codeparent'];
        $_SESSION['login'] = $parent['login'];
        $_SESSION['trial_end_date'] = $parent['trial_end_date'];
        $_SESSION['first_name'] = $parent['first_name'];
        $_SESSION['last_name'] = $parent['last_name'];
        $_SESSION['full_name'] = $parent['first_name'] . ' ' .$parent['last_name'];
        $_SESSION['data'] = $parent;
        $_SESSION['webconnect'] = $connectByWeb;
        $_SESSION['isAdministrator'] = self::isAdministrator();
        self::getAllCourseRegistered();
        $_SESSION['lastConnectionDurationInMinute'] = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        Basket::prepareSessionBasket();
        self::childrenCourses($parent['id']);
    }

    public static function setSessionChild($child, $connectByWeb = true)    {
        self::chargerImage();        
        $_SESSION['user_type'] = 'child';
        $_SESSION['child_id'] = $child['id'];
        $_SESSION['current_id'] = $child['id'];
        $_SESSION['parent_id'] = $child['parent_id'];
        $_SESSION['codeparent'] = $child['codeparent'];
        $_SESSION['login'] = $child['login'];
        $_SESSION['child_name'] = $child['name'];
        $_SESSION['full_name'] = $child['name'];
        $_SESSION['data'] = $child;
        $_SESSION['webconnect'] = $connectByWeb;
        $_SESSION['isAdministrator'] = self::isAdministrator();
        self::getAllCourseRegistered();
        $_SESSION['lastConnectionDurationInMinute'] = (new DateTimeImmutable())->format('Y-m-d H:i:s');
    }
    public static function isTrialActive() {
        if(isset($_SESSION['trial_end_date']) && $_SESSION['trial_end_date'] != null ) {
            $dateString = $_SESSION['trial_end_date'];

            $date = new DateTime($dateString);
            $now = new DateTime();
            
            if ($date > $now) {
                return true;
            }
        }
        return false;
    }
    public static function displayTrialMessage() {
        if(isset($_SESSION['trial_end_date']) && $_SESSION['trial_end_date'] != null ) {
            $dateString = $_SESSION['trial_end_date'];

            $date = new DateTime($dateString);
            $now = new DateTime();
            $interval = $now->diff($date);
            $jours = $interval->days;

            if ($date > $now) {
                $jours = abs($jours);
                return "<div style='animation: blink 1s infinite;". Settings::buttonDimensionStyle() ."' class='btn div-buttom-red'>$jours jours d'essai</div>";
            }
        }
        return "";
    }
    public static function getTrialDaysLeft() {
        if(isset($_SESSION['trial_end_date']) && $_SESSION['trial_end_date'] != null ) {
            $dateString = $_SESSION['trial_end_date'];

            $date = new DateTime($dateString);
            $now = new DateTime();
            $interval = $now->diff($date);
            $jours = $interval->days;

            if ($date > $now) {
                $jours = abs($jours);
                return $jours;
            }
        }
        return 0;
    }

    public static function desactiverSession()    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['user_type']);
        unset($_SESSION['child_id']);
        unset($_SESSION['current_id']);
        unset($_SESSION['parent_id']);
        unset($_SESSION['codeparent']);
        unset($_SESSION['login']);
        unset($_SESSION['child_name']);
        unset($_SESSION['data']);
        unset($_SESSION['webconnect']);
        unset($_SESSION['isAdministrator']);
        unset($_SESSION['lastConnectionDurationInMinute']);
        unset($_SESSION['full_name']);
        unset($_SESSION['courses']);
        unset($_SESSION['courseDate']);
        unset($_SESSION['done_course']);
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_unset();
        session_destroy();
        header("Location: ". Settings::getHomePage() ."connect/login/?msg=Vous êtes deconnecté!");
        exit;
    }

    public static function isConnected() {
        return isset($_SESSION['login']) && self::distanceInMinutes($_SESSION['lastConnectionDurationInMinute']) <= 120;
    }

    public static function isParent():bool {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] == "parent";
    }

    public static function isModeParentWorkingChild():bool {
        return self::isParent() &&  $_SESSION['parent_id'] != $_SESSION['current_id'];
    }

    public static function redirectionHome() {
        header("Location:" . Settings::getHomePage());
    }

    public static function workasParent() {
        $name = trim(explode("/", $_SESSION['full_name'])[0]);
        $_SESSION['full_name'] = $name;
        $_SESSION['current_id'] = $_SESSION['parent_id'];
        unset($_SESSION['courses']);
        header("Location:" . Settings::getHomePage());
    }

    public static function workasChild($childId, $childName) {
        $name = trim(explode("/", $_SESSION['full_name'])[0]);
        $_SESSION['full_name'] = $name . " / $childName";
        $_SESSION['current_id'] = $childId;
        self::getAllCourseRegistered();
        header("Location:" . Settings::getHomePage());
    }

    public static function workasChildStop() {
        $name = trim(explode("/", $_SESSION['full_name'])[0]);
        $_SESSION['full_name'] = $name ;
        $_SESSION['current_id'] = $_SESSION['parent_id'];
        self::getAllCourseRegistered();
    }

    public static function distanceInMinutes($lastTime):float {
        $lastConnection = new DateTime($lastTime);
        $now = new DateTime();
        $diffInSeconds = $now->getTimestamp() - $lastConnection->getTimestamp();
        $diffInMinutes = floor($diffInSeconds / 60);
        return $diffInMinutes;
    }

    public static function isAdministrator(): bool {
        return  isset($_SESSION['data']['email']) && str_starts_with($_SESSION['data']['email'], 'tioladuc@');
    }

    public static function start($courseCode = "", $codeLesson = "") {
       $GLOBALS['CourseCode'] = $courseCode;
       if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if(!self::isConnected()) {            
            $loginId = $_GET['login'] ?? '';
            $codesecret = $_GET['codesecret'] ?? '';
            if ($loginId == '' || $codesecret == '') { // connected by web
                header("Location: ". Settings::getHomePage() ."connect/login/");
                exit;
            }
            else { 
                self::loginByMobileInformationUserParentChild($loginId, $codesecret);
            }
        }
        
        if(isset($_SESSION['child_id'])){
            self::record_statics_information($_SESSION['child_id'], $courseCode, connectBD());
            self::check_done_items($_SESSION['child_id'], $courseCode, connectBD());
        }
        $allRegisteredCourses = self::getAllCourseRegistered();
        
        if ($courseCode == null || trim($courseCode) == "") {
            if ($_SESSION['webconnect'] == false) {
                self::displayNotAutorizedBecauseOfTimeOutMobile();
                exit;
            } else {
                header("Location: ". Settings::getHomePage() ."?msg=Sorry... You do not have no access to this section !");
                exit;
            }
        }
        
        if (!isset($allRegisteredCourses[$courseCode])) {
            if ($_SESSION['webconnect'] == false) {
                self::displayNotAutorizedBecauseOfTimeOutMobile();
                exit;
            } 
            else {
                header("Location: ". Settings::getHomePage() ."?msg=Sorry... You do not have no access to this section !");
                exit;
            }
        }

        self::procedeedWithStatistics($courseCode, $codeLesson);
    }

    public static function check_done_items($childid, $course_id, $pdo) {
        $query = "SELECT child_id, course_code,	course_item_id,	access_date FROM learn4kids_complete_courses_items WHERE child_id = '$childid' AND course_code = '$course_id'	";
        $stmt = $pdo->prepare($query);
        $stmt->execute([]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $dataPrepared = array();
        for ($i=0; $i < count($data) ; $i++) { 
            $dataPrepared[$childid][$course_id][$data[$i]['course_item_id']] = $data[$i]['access_date'];            
        }
        $_SESSION['done_course'] = $dataPrepared;
    }
    
    public static function record_statics_information($childid, $course_id, $pdo) {
        $queryCheckPresent = "SELECT * FROM learn4kids_visited_courses WHERE child_id='$childid' 
                                AND last_connection >= DATE_SUB(NOW(), INTERVAL 2 HOUR) 
                                ORDER BY last_connection desc limit 0 , 1";
        $stmt = $pdo->prepare($queryCheckPresent);
        $stmt->execute([]);
        $static = $stmt->fetch(PDO::FETCH_ASSOC);
        $query = null;

        if($static == null || count($static)==0) { 
            $query = "INSERT INTO learn4kids_visited_courses(child_id, course_code, time_spent, last_connection) VALUES('$childid', '$course_id', 0, now())";
        } else { // donnée presente
            if($static['course_code'] == $course_id) { // course id already exist
                $timeSpent = $static['time_spent'];
                $date1 = new DateTime($static['last_connection']);
                $date2 = new DateTime(); // current date/time
                $diffSeconds = $date2->getTimestamp() - $date1->getTimestamp();
                $diffMinutes = floor($diffSeconds / 60);
                $timeSpent += $diffMinutes;
                $query = "UPDATE learn4kids_visited_courses SET time_spent = $timeSpent, last_connection = now() WHERE child_id = '$childid' AND course_code = '$course_id' ";
            } else { // course does not existing
                $query = "INSERT INTO learn4kids_visited_courses(child_id, course_code, time_spent, last_connection) VALUES('$childid', '$course_id', 0, now())";
            }
        }

        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute([]);
            if(isset($_GET['id'])) {
                $queryDel = "DELETE FROM learn4kids_visited_courses_items WHERE child_id = '$childid' AND course_code = '$course_id' AND course_item_id = ". $_GET['id'];
                $queryInsert = "INSERT INTO learn4kids_visited_courses_items(child_id, course_code, course_item_id, access_date) VALUES('$childid', '$course_id', ". $_GET['id'] .", now())";
                $stmt = $pdo->prepare($queryDel); $stmt->execute([]);
                $stmt = $pdo->prepare($queryInsert); $stmt->execute([]);
            }

        } catch (PDOException $e) { // Catch only the database exception
            
        }
        
    }

    public static function loginByMobileInformationUserParentChild(string $loginId, string $codesecret)   {
        $pdo = connectBD();

        $sql = "SELECT * 
                FROM learn4kids_parents
                WHERE id = ? AND codesecret = ?
                AND is_active = 1
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$loginId, $codesecret]);
        $parent = $stmt->fetch(PDO::FETCH_ASSOC);

        if($parent){
            self::setSessionParent($parent, false);
            return $parent;
        }


        $sql = "SELECT * 
                FROM learn4kids_children
                WHERE id = ? AND codesecret = ?
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$loginId, $codesecret]);
        $child = $stmt->fetch(PDO::FETCH_ASSOC);

        if($child){
            self::setSessionChild($child, false);
            return $child;
        }

        self::displayNotAutorizedBecauseOfTimeOutMobile();
        exit;
    }


    public static function getAllCourseRegistered()    {
        $loginId = $_SESSION['current_id'];
        if(self::isAdministrator()) {
            $registeredCourses['C001'] = true; // course de test pour les enfants qui n'ont pas encore de cours enregistré
            $registeredCourses['C002'] = true;
            $registeredCourses['C003'] = true;

            $registeredCourses['C004'] = true; // course de test pour les enfants qui n'ont pas encore de cours enregistré
            $registeredCourses['C005'] = true;
            $registeredCourses['C006'] = true;

            $registeredCourses['C007'] = true;
            $registeredCourses['C008'] = true;
            $registeredCourses['C009'] = true;

            $_SESSION['courses'] = $registeredCourses;
            return $registeredCourses;
        }
                
        $pdo = connectBD();
        $query = "SELECT * FROM learn4kids_child_courses WHERE child_id = '" . $_SESSION['current_id'] . "' AND is_active = 1 AND is_paid = 1 and now() < expiry_date";
        $stmt = $pdo->prepare($query);
        $stmt->execute([]);
        $coursesDB = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $registeredCourses = array(); // course de test pour les enfants qui n'ont pas encore de cours enregistré
 
        for ($i=0; $i < count($coursesDB); $i++) { 
            $registeredCourses[ $coursesDB[$i]['course_code'] ] = true;
        }

        $_SESSION['courses'] = $registeredCourses;
        $_SESSION['courses'] = $registeredCourses;
        $_SESSION['courseDate'] = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        return $registeredCourses;
    }



    public static function procedeedWithStatistics() {}


    public static function displayNotAutorizedBecauseOfTimeOutMobile()
    { ?>

        <!DOCTYPE html>
        <html lang="en">

            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Unauthorized Access</title>

                <style>
                    * {
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                    }

                    body {
                        font-family: Arial, Helvetica, sans-serif;
                        min-height: 100vh;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        background: linear-gradient(135deg, #4f46e5, #7c3aed);
                        padding: 20px;
                    }

                    .container {
                        background: #fff;
                        max-width: 500px;
                        width: 100%;
                        text-align: center;
                        padding: 40px 30px;
                        border-radius: 20px;
                        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
                    }

                    .icon {
                        font-size: 70px;
                        margin-bottom: 15px;
                    }

                    h1 {
                        color: #222;
                        margin-bottom: 15px;
                    }

                    p {
                        color: #666;
                        line-height: 1.6;
                        margin-bottom: 30px;
                    }

                    .buttons {
                        display: flex;
                        gap: 15px;
                        justify-content: center;
                        flex-wrap: wrap;
                    }

                    .btn {
                        padding: 12px 28px;
                        border: none;
                        border-radius: 8px;
                        text-decoration: none;
                        font-size: 16px;
                        font-weight: bold;
                        cursor: pointer;
                        transition: 0.3s;
                    }

                    .login {
                        background: #4f46e5;
                        color: white;
                    }

                    .login:hover {
                        background: #4338ca;
                    }

                    .logout {
                        background: #ef4444;
                        color: white;
                    }

                    .logout:hover {
                        background: #dc2626;
                    }

                    @media (max-width: 480px) {
                        .container {
                            padding: 30px 20px;
                        }

                        .buttons {
                            flex-direction: column;
                        }

                        .btn {
                            width: 100%;
                        }
                    }
                </style>
            </head>

            <body>

                <div class="container">
                    <div class="icon">🔒</div>

                    <h1>Unauthorized Access</h1>

                    <p>
                        You do not have permission to access this page.
                        Please log in with an authorized account or log out and try again.
                    </p>


                </div>

            </body>

        </html>
<?php
    }
}



?>