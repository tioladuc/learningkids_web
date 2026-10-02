<?php
// Basket functionality for managing child courses
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . "/../core/dbcore.php");
require_once(__DIR__ . "/../core/menu.php");
require_once(__DIR__ . "/../core/session.php");
require_once(__DIR__ . "/../core/setting.php");
require_once(__DIR__ . "/../core/basketcore.php");
require_once(__DIR__ . "/../core/stripe_checkout.php");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
SessionHandle::checkingPrivilegeParent();

$pdo = connectBD();
function manageBasket()
{
    $currentId = $_SESSION['current_id'];
    $pdo = connectBD();

    /************************************************
    REMOVE ITEM
     ************************************************/

    if (isset($_GET['remove'])) {
        try {
            $basketId = intval($_GET['basket']);
            $course = $_GET['course'];
            $child = $_GET['child'];

            $sql = "
                    DELETE FROM learn4kids_basket_item
                    WHERE basket_id=?
                    AND course_code=?
                    AND child_id=?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$basketId, $course, $child]);

            // Recalculate totals

            $sql = "
                    SELECT
                        COUNT(*) total_items,
                        IFNULL(SUM(price),0) total_amount
                    FROM learn4kids_basket_item
                    WHERE basket_id=?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$basketId]);

            $totals = $stmt->fetch(PDO::FETCH_ASSOC);

            $sql = "
                    UPDATE learn4kids_basket
                    SET
                        total_items=?,
                        total_amount=?
                    WHERE id=?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $totals['total_items'],
                $totals['total_amount'],
                $basketId
            ]);

            $_SESSION['basket_total_items'] = $totals['total_items'];
            $_SESSION['basket_total_amount'] = $totals['total_amount'];
            $_SESSION['basket_selected_courses'][$child] = isset($_SESSION['basket_selected_courses'][$child]) ? $_SESSION['basket_selected_courses'][$child] : "'',";
            $_SESSION['basket_selected_courses'][$child] = str_replace("'$course',", "", $_SESSION['basket_selected_courses'][$child]);
            header("Location: ". $GLOBALS['LocalHome'] . "basket/");
            exit;

        } catch (PDOException $e) { // Catch only the database exception
            
        }
        
    }
    if (isset($_GET['checkout'])) {
        $urlRedirection = $GLOBALS['LocalHome'] . "success_payment/?basket=done&amount=" . $_SESSION['basket_total_amount'];
        if(SessionHandle::isTrialActive() && $_SESSION['basket_total_amount'] == 0) {
                header("Location: " . $urlRedirection);
                exit;
        }
        launchStripePayment(
            $_SESSION['basket_total_amount'],
            $urlRedirection,
            $GLOBALS['LocalHome'] . "manage/?msg=Paiement-echoue",
            "Paiement de cours sur Learn4Kids"
        );
        exit;
    }

    /************************************************
    LOAD BASKET
     ************************************************/

    $sql = "

    SELECT

    b.id basket_id,
    b.total_items,
    b.total_amount,
    c.name as child_name,

    bi.course_code,
    bi.child_id,
    bi.price,
    bi.added_date,
    bi.extra_info,
    crs.name as course_name

    FROM learn4kids_basket b

    INNER JOIN learn4kids_basket_item bi
    ON bi.basket_id=b.id 
    INNER JOIN learn4kids_children c
    ON c.id=bi.child_id
    INNER JOIN learn4kids_courses crs
    ON crs.code=bi.course_code

    WHERE b.current_id=? and b.is_paid=0

    ORDER BY c.name, bi.added_date DESC

    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$currentId]);

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
    <!doctype html>
    <html lang="en">

    <head>
        <link rel="icon" type="image/x-icon" href="<?= Settings::getHomePage() ?>favicon/favicon.ico">
        <link rel="icon" type="image/png" href="<?= Settings::getHomePage() ?>favicon/favicon-32x32.png">
        <link rel="icon" type="image/png" href="<?= Settings::getHomePage() ?>favicon/favicon-16x16.png">
        <meta charset="UTF-8">

        <meta name="viewport"
            content="width=device-width,initial-scale=1">

        <title>Mon Panier</title>

        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
            rel="stylesheet">

        <?php echo Menu::menuStaticCSS(); ?>
        <style>
            :root {
                --primary: #4f46e5;
                --secondary: #7c3aed;
                --success: #10b981;
                --danger: #ef4444;
                --dark: #1f2937;
                --gray: #6b7280;
                --light: #f8fafc;
            }
            body {

                background: #f4f6fb;

            }

            .card {

                border: none;

                border-radius: 15px;

                box-shadow: 0 10px 25px rgba(0, 0, 0, .08);

            }

            .table td {

                vertical-align: middle;

            }

            .price {

                font-weight: bold;
                color: #198754;

            }
        </style>
        <?php echo Menu::menuStaticScriptMenu(); ?>

    </head>

    <body>

        <div class="container py-5">
            <?php 
                $mnuStatic = "";
                echo Menu::menuStaticMenu($mnuStatic);
            ?>

            <div class="card">

                <div class="card-header bg-primary text-white">

                    <h3 class="mb-0">
                        🛒 Mon Panier
                    </h3>
                    <div class="row">
                        <div class="col-4" style="justify-content: center;display: grid; place-items: center;">
                            <a href="<?= $GLOBALS['LocalHome'] ?>payments/" style="color:white; <?= Settings::buttonDimensionStyle() ?>" class="btn div-buttom-violet">Paiements</a></div>
                        <div class="col-4" style="justify-content: center;display: grid; place-items: center;">
                            <?php echo SessionHandle::displayTrialMessage(); ?></div>
                        <div class="col-4" style="justify-content: center;display: grid; place-items: center;">
                            <a href="<?= $GLOBALS['LocalHome'] ?>manage/" style="color:white; <?= Settings::buttonDimensionStyle() ?>" class="btn div-buttom-violet">Retour Page Enfants</a></div>
                    </div>
                    
                </div>

                <div class="card-body">

                    <?php

                    if (empty($items)) {

                    ?>

                        <div class="alert alert-info">

                            Votre panier est vide.

                        </div>

                        <a
                            href="<?= $GLOBALS['LocalHome'] ?>manage/"
                            class="btn btn-primary">

                            Continuer vos achats

                        </a>

                    <?php

                    } else {

                    ?>

                        <div class="table-responsive">

                            <table class="table table-hover">

                                <thead>

                                    <tr>

                                        <th>#</th>

                                        <th>Cours</th>

                                        <th>Enfant</th>

                                        <th>Prix</th>

                                        <th>Autre</th>

                                        <th>Date</th>

                                        <th width="120">
                                            Action
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php

                                    $total = 0;

                                    foreach ($items as $i => $item) {

                                        $total += $item['price'];

                                    ?>

                                        <tr>

                                            <td><?= $i + 1 ?></td>

                                            <td>

                                                <strong>

                                                    <?= htmlspecialchars($item['course_name']) ?> [ <?= htmlspecialchars($item['course_code']) ?> ]

                                                </strong>

                                            </td>

                                            <td>

                                                <?= htmlspecialchars($item['child_name']) ?>
                                            </td>

                                            <td class="price">

                                                $<?= number_format($item['price'], 2) ?>

                                            </td>

                                            <td>
                                                <?= ($item['extra_info'] == null || $item['extra_info'] == '') ? '--/--' : (' - ' . htmlspecialchars($item['extra_info'])) ?>
                                            </td>

                                            <td>

                                                <?= $item['added_date'] ?>

                                            </td>


                                            <td>

                                                <a

                                                    class="btn btn-danger btn-sm"

                                                    onclick="return confirm('Remove this item?');"

                                                    href="<?= $GLOBALS['LocalHome'] ?>basket/?remove=1&basket=<?= $item['basket_id'] ?>&course=<?= urlencode($item['course_code']) ?>&child=<?= urlencode($item['child_id']) ?>">

                                                    Retirer

                                                </a>

                                            </td>

                                        </tr>

                                    <?php

                                    }

                                    ?>

                                </tbody>

                                <tfoot>

                                    <tr>

                                        <th colspan="3">

                                            Total

                                        </th>

                                        <th>

                                            $<?= number_format($total, 2) ?>

                                        </th>

                                        <th colspan="3"></th>

                                    </tr>

                                </tfoot>

                            </table>

                        </div>

                        <hr>

                        <div class="row">

                            <div class="col-md-6">

                                <h5>

                                    Elements dans le panier :

                                    <?= count($items) ?>

                                </h5>

                            </div>

                            <div class="col-md-6 text-end">

                                <h4>

                                    Total :

                                    <span class="text-success">

                                        $<?= number_format($total, 2) ?>

                                    </span>

                                </h4>

                            </div>

                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">

                            <a
                                href="<?= $GLOBALS['LocalHome'] ?>manage/"
                                class="btn btn-secondary">

                                ← Continuer vos achats

                            </a>

                            <a
                                href="<?= $GLOBALS['LocalHome'] ?>basket/?checkout=1"
                                class="btn btn-success btn-lg">

                                Proceder au paiement →

                            </a>

                        </div>

                    <?php

                    }

                    ?>

                </div>

            </div>

        </div>

    </body>

    </html>
<?php
}

function addElementsAutomaticallyToBasket()
{
    $pdo = connectBD();
    $currentId = $_SESSION['current_id'];
    $queryChildren = "SELECT * FROM learn4kids_children WHERE parent_id = '$currentId'";
    $stmt = $pdo->prepare($queryChildren);
    $stmt->execute([]);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $alertNumberOfDaysBeforeExpiration = Settings::warningDaysBeforeExpiration(); // days

    foreach ($children as $child) {
        $childId = $child['id'];
        // Add all courses for each child
        $queryCourses = "SELECT course_code FROM learn4kids_child_courses WHERE child_id = '$childId' AND 
                        ( learn4kids_child_courses.expiry_date IS NULL OR learn4kids_child_courses.expiry_date <= DATE_ADD(NOW(), INTERVAL $alertNumberOfDaysBeforeExpiration DAY) )
                        AND learn4kids_child_courses.is_active = 1";
        $stmtCourses = $pdo->prepare($queryCourses);
        $stmtCourses->execute([]);
        $courses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

        foreach ($courses as $course) {
            Basket::addToBasket($childId, $course['course_code'], true);
        }
    }
}


$current_file = explode('.', basename(__FILE__))[0];
$url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (isset($_GET['automate']) && $_GET['automate'] == 'true') {
    addElementsAutomaticallyToBasket();
    header("Location: " . Settings::getHomePage() . "children/basket/");
    exit;
}
//echo "***************************** $url_path, $current_file ////////////////////";
if (str_contains($url_path, $current_file)) {
    manageBasket();
}

?>