<?php
// Basket functionality for managing child courses
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . "/dbcore.php");
require_once(__DIR__ . "/menu.php");
require_once(__DIR__ . "/setting.php");
require_once(__DIR__ . "/email_sender.php");
require_once(__DIR__ . "/session.php");

/*$folder = dirname(__DIR__) . "/children/stripe_checkout.php";//"../";
if (is_dir($folder)) {
    echo "The folder exists.";
    $files = array_diff(scandir($folder), array('.', '..'));
    foreach ($files as $file) {
        echo $file . "<br>";
    }
} else {
    echo "The folder does not exist.";
}*/

require_once "stripe_checkout.php";


$pdo = connectBD();

class Basket
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public static function addToBasket($childId, $courseCode, $mode, $automatic = false)
    {
        $data = self::getActiveBaskets();
        $basket = null;
        
        if (!empty($data)) {
            $basket = $data[0];
        } else {
            $basket = self::createBasket();
        }

        $queryCourse = "SELECT * FROM learn4kids_courses WHERE code = '$courseCode'";
        
        $pdo = connectBD();
        $stmt = $pdo->prepare($queryCourse);
        $stmt->execute([]);
        $dataCourse = $stmt->fetch(PDO::FETCH_ASSOC);
        
        try {
            $amount = $mode=="trial" ? 0 : ( $mode=="month" ? $dataCourse['amount'] : ($dataCourse['amount']*12*(100-Settings::annualReduction()) / 100) );
            $amountDescription = ($mode=="trial" ? "Période Essai (Trial)" : ( $mode=="month" ? "Achat Mensuel (Monthly)" : "Achat Annuel (Annually)" )) . " - [$mode] $" . $dataCourse['amount'];
            $addedAutomatically = $automatic ? "'Ajout automatique / automatic add - $amountDescription'" : "'$amountDescription'";
            
            $queryAddItem = "INSERT INTO learn4kids_basket_item(basket_id, child_id, course_code, price, added_date, extra_info)
                        VALUES(" . $basket['id'] . ", '$childId', '$courseCode', " . $amount . ", now(), $addedAutomatically)";
            $stmt = $pdo->prepare($queryAddItem);
            $stmt->execute([]); 

            $queryUpdateBasket = "UPDATE learn4kids_basket SET total_items = total_items + 1, total_amount = total_amount + " . $amount . " WHERE id = " . $basket['id'];
            
            $stmt = $pdo->prepare($queryUpdateBasket);
            $stmt->execute([]);

            // Session Update 
            $_SESSION['basket_selected_courses'][$childId] = isset($_SESSION['basket_selected_courses'][$childId]) ? $_SESSION['basket_selected_courses'][$childId] : "'',";
            $_SESSION['basket_selected_courses'][$childId] .= "'$courseCode',";
            $_SESSION['basket_total_items'] = $basket['total_items'] + 1;
            $_SESSION['basket_total_amount'] = $basket['total_amount'] + $amount;

        } catch (PDOException $e) { // Catch only the database exception
            
        }
        
    }

    public static function removeFromBasket($childId, $courseCode)
    {
        $data = self::getActiveBaskets();
        if (empty($data)) {
            return; // No active basket to remove from
        }
        $basket = $data[0];

        $queryCourse = "SELECT * FROM learn4kids_courses WHERE code = '$courseCode'";
        $pdo = connectBD();
        $stmt = $pdo->prepare($queryCourse);
        $stmt->execute();
        $dataCourse = $stmt->fetch(PDO::FETCH_ASSOC);

        try {
            $queryRemoveItem = "DELETE FROM learn4kids_basket_items WHERE basket_id = " . $basket['id'] . " AND child_id = '$childId' AND course_code = '$courseCode'";
            $stmt = $pdo->prepare($queryRemoveItem);
            $stmt->execute([]);

            $queryUpdateBasket = "UPDATE learn4kids_basket SET total_items = total_items - 1, total_amount = total_amount - " . $dataCourse['amount'] . " WHERE id = " . $basket['id'];
            echo "<br>queryUpdateBasket: $queryUpdateBasket<br>";
            $stmt = $pdo->prepare($queryUpdateBasket);
            $stmt->execute();

            // Session Update 
            $_SESSION['basket_total_items'] = max(0, $basket['total_items'] - 1);
            $_SESSION['basket_total_amount'] = max(0, $basket['total_amount'] - $dataCourse['amount']);
            $_SESSION['basket_selected_courses'][$childId] = isset($_SESSION['basket_selected_courses'][$childId]) ? $_SESSION['basket_selected_courses'][$childId] : "'',";
            $_SESSION['basket_selected_courses'][$childId] = str_replace("'$courseCode',", "", $_SESSION['basket_selected_courses'][$childId]);

        } catch (PDOException $e) { // Catch only the database exception
            
        }
        
    }

    public static function displayBasket(): string
    {
        $data = self::getActiveBaskets();
        if (empty($data)) {
            return "<p>Votre panier est vide.</p>";
        }
        $basket = $data[0];

        $queryItems = "SELECT learn4kids_basket_item.*, learn4kids_courses.name, learn4kids_courses.price 
                       FROM learn4kids_basket_item 
                       JOIN learn4kids_basket ON (learn4kids_basket_item.basket_id = learn4kids_basket.id) 
                       JOIN  	learn4kids_courses  ON (learn4kids_basket_item.course_code = learn4kids_courses.code) 
                       WHERE basket_id = " . $basket['id'];
        $pdo = connectBD();
        $stmt = $pdo->prepare($queryItems);
        $stmt->execute([]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $html = "<h3>Votre Panier</h3><ul>";
        foreach ($items as $item) {
            $html .= "<li>Course Code: " . htmlspecialchars($item['course_code']) . " - Price: $" . htmlspecialchars($item['price']) . "</li>";
        }
        $html .= "</ul>";
        $html .= "<p>Total Items: " . htmlspecialchars($basket['total_items']) . "</p>";
        $html .= "<p>Total Amount: $" . htmlspecialchars($basket['total_amount']) . "</p>";
        return $html;
    }

    public static function prepareSessionBasket($activeBaskets = null) {
        if($activeBaskets==null) {
            $activeBaskets = self::getActiveBaskets();
        }
        if(count($activeBaskets) == 0)return;

        if (!isset($_SESSION['basket_total_items'])) {
            $queryTotalItems = "SELECT count(*) as total_items, sum(price) as total_amount FROM learn4kids_basket_item WHERE basket_id = " . $activeBaskets[0]['id'];
            $pdo = connectBD();
            $stmt = $pdo->prepare($queryTotalItems);
            $stmt->execute([]);
            $dataAll = $stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['basket_total_items'] = $dataAll['total_items'];
            $_SESSION['basket_total_amount'] = $dataAll['total_amount'];

            $queryItems = "SELECT course_code, child_id FROM learn4kids_basket_item WHERE basket_id = " . $activeBaskets[0]['id'];
            $pdo = connectBD();
            $stmt = $pdo->prepare($queryItems);
            $stmt->execute([]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            for ($i=0; $i < count($data) ; $i++) { 
                $childId = $data[$i]['child_id'];
                $courseCode = $data[$i]['course_code'];
                
                $_SESSION['basket_selected_courses'][$childId] = isset($_SESSION['basket_selected_courses'][$childId]) ? $_SESSION['basket_selected_courses'][$childId] : "'',";
                $_SESSION['basket_selected_courses'][$childId] .= "'$courseCode',";
            }
            
        }
    }

    public static function displayIconBasket(): string
    {
        $data = self::getActiveBaskets();
        //$data = array(array('id' => 10), 14, 15); //===to delete after testing
        if (empty($data)) {
            return "<span class='basket-icon'></span>";
        }
        
        self::prepareSessionBasket($data);

        $cssStyle = " style=\"background-color: #000;". Settings::buttonDimensionStyle() ."  color:white; font-weight: bold;\" ";
        if (empty($data)) {
            return "<button $cssStyle><span class='basket-icon'><i class=\"fa-solid fa-shopping-basket\"></i> 0 élèment</span></button>";
        }
        $count = isset($_SESSION['basket_total_items']) ? ($_SESSION['basket_total_items']==null ? 0 : $_SESSION['basket_total_items']) : 0;
        $amount = isset($_SESSION['basket_total_amount']) ? ($_SESSION['basket_total_amount']==null ? 0 : $_SESSION['basket_total_amount']) : 0;
        return "<button $cssStyle onclick=\"location.href='" . Settings::getHomePage() . "children/basket/';\"><span class='basket-icon'><i class=\"fa-solid fa-shopping-basket\"></i> " .
            htmlspecialchars($count) . " élèment(s), $" . htmlspecialchars($amount) . " </span></button>";
    }

    public static function paymentOfBasket()
    {
        $data = self::getActiveBaskets();
        if (empty($data)) {
            return "<p>Votre panier est vide.</p>";
        }
        $basket = $data[0];

        $queryItems = "SELECT learn4kids_basket_item.*, learn4kids_courses.name, learn4kids_courses.amount  
                       FROM learn4kids_basket_item 
                       JOIN learn4kids_basket ON (learn4kids_basket_item.basket_id = learn4kids_basket.id) 
                       JOIN  	learn4kids_courses  ON (learn4kids_basket_item.course_code = learn4kids_courses.code) 
                       WHERE basket_id = " . $basket['id'];
        $pdo = connectBD();
        $stmt = $pdo->prepare($queryItems);
        $stmt->execute([]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $currentDate = date('Y-m-d H:i:s');
        $addTimeSlice = "";
        
        try {
            for ($i = 0; $i < count($items); $i++) {
                $course = $items[$i];
                if(str_contains($course['extra_info'], "[trial]")) $addTimeSlice = " ". SessionHandle::getTrialDaysLeft() ." DAY ";
                if(str_contains($course['extra_info'], "[month]")) $addTimeSlice = " 1 MONTH ";
                if(str_contains($course['extra_info'], "[annual]")) $addTimeSlice = " 12 MONTH ";
                $childid = $course['child_id'];
                $queryExistance = "SELECT * FROM learn4kids_child_courses WHERE child_id='$childid' AND course_code='" . $course['course_code'] . "'";
                $stmt = $pdo->prepare($queryExistance);
                $stmt->execute([]);
                $existingCourse = $stmt->fetch(PDO::FETCH_ASSOC);
                //print_r($existingCourse); exit;
                if (!$existingCourse) {
                    //echo "NON existing************";exit;
                    $queryInsert = " INSERT INTO learn4kids_child_courses(child_id, course_code, is_paid, picked_date, expiry_date, is_active) VALUES ('$childid', '" . $course['course_code'] . "', 1, now(), NOW() + INTERVAL $addTimeSlice, 1)";
                    $stmt = $pdo->prepare($queryInsert);
                    $stmt->execute();
                    $queryInsertpayment = "INSERT INTO learn4kids_payments(parent_id, child_id, course_code, amount, is_paid, payment_date, startperiod, endperiod)
                                    VALUES('" . $_SESSION['current_id'] . "', '$childid', '" . $course['course_code'] . "', " . ( $course['price'] ) . ", 1, '$currentDate', now(), NOW() + INTERVAL $addTimeSlice)";
                    $stmt = $pdo->prepare($queryInsertpayment);
                    $stmt->execute();
                } else {
                    //echo "existing************";exit;
                    $updateExpiringDate = " CASE WHEN expiry_date IS NULL THEN now() + INTERVAL 1 MONTH ELSE (expiry_date + INTERVAL $addTimeSlice)  END ";
                    $queryUpdate = "UPDATE learn4kids_child_courses SET is_paid=1, expiry_date=$updateExpiringDate, is_active=1 WHERE child_id='$childid' AND course_code='" . $course['course_code'] . "'";
                    $stmt = $pdo->prepare($queryUpdate);
                    $stmt->execute();
                    $queryInsertpayment = "INSERT INTO learn4kids_payments(parent_id, child_id, course_code, amount, is_paid, payment_date, startperiod, endperiod)
                                    VALUES('" . $_SESSION['current_id'] . "', '$childid', '" . $course['course_code'] . "', " . ( $course['price'] ) . ", 1, '$currentDate', now(), NOW() + INTERVAL $addTimeSlice)";
                    $stmt = $pdo->prepare($queryInsertpayment);
                    $stmt->execute();
                }
            }

            $queryUpdateBasket = "UPDATE learn4kids_basket SET is_paid=1, payment_date=now() WHERE id = " . $basket['id'];
            $stmt = $pdo->prepare($queryUpdateBasket);
            $stmt->execute();

            unset($_SESSION['basket_total_items']);
            unset($_SESSION['basket_total_amount']);
            unset($_SESSION['basket_selected_courses']);
            EmailSender::sendPaymentEmailForUniqueBasket($basket['id']);
        } catch (PDOException $e) { // Catch only the database exception
            
        }
        
    }

    static private function createBasket(): array
    {
        try {
            $queryInsertBasket = "INSERT INTO learn4kids_basket(current_id, total_items, total_amount, is_paid, created_date, payment_date)
                                VALUES('" . $_SESSION['current_id'] . "', 0, 0, 0, now(), null)";
            $pdo = connectBD();
            $stmt = $pdo->prepare($queryInsertBasket);
            $stmt->execute([]);
            return self::getActiveBaskets()[0];

        } catch (PDOException $e) { // Catch only the database exception
            
        }
        return array();
    }

    static private function getActiveBaskets(): array
    {
        $queryCheck = "SELECT * FROM learn4kids_basket WHERE current_id = '" . $_SESSION['current_id'] . "' AND is_paid = 0";
        $pdo = connectBD();
        $stmt = $pdo->prepare($queryCheck);
        $stmt->execute([]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }
}

?>