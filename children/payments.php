<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
 }
require_once(__DIR__ . "/../core/dbcore.php");
require_once(__DIR__ . "/../core/session.php");
require_once(__DIR__ . "/../core/menu.php");
require_once(__DIR__ . "/../core/setting.php");

SessionHandle::checkingPrivilegeParent();

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$q = "SELECT payment_date AS payment_day,
COUNT(*) total_items,
SUM(learn4kids_payments.amount) total_amount,
GROUP_CONCAT(CONCAT(learn4kids_courses.name,' - ',learn4kids_children.name, ' - $ ', learn4kids_payments.amount) SEPARATOR '<br>') courses
FROM learn4kids_payments
JOIN learn4kids_courses ON learn4kids_courses.code=learn4kids_payments.course_code
JOIN learn4kids_children ON learn4kids_children.id=learn4kids_payments.child_id
WHERE 1";

$pdo = connectBD();
$p = [];
if ($from) {
    $q .= " AND DATE(payment_date)>=?";
    $p[] = $from;
}
if ($to) {
    $q .= " AND DATE(payment_date)<=?";
    $p[] = $to;
}
$q .= " GROUP BY payment_date ORDER BY payment_day DESC";
$s = $pdo->prepare($q);
$s->execute($p);
$rows = $s->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html>

<head>
    <link rel="icon" type="image/x-icon" href="<?= Settings::getHomePage() ?>favicon/favicon.ico">
    <link rel="icon" type="image/png" href="<?= Settings::getHomePage() ?>favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" href="<?= Settings::getHomePage() ?>favicon/favicon-16x16.png">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Payments / Paiement</title>
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
            background: #f4f6f9
        }

        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .08)
        }

        thead th {
            position: sticky;
            top: 0;
            background: #0d6efd;
            color: #fff
        }

        .table-responsive {
            max-height: 70vh
        }
    </style>
    <?php echo Menu::menuStaticScriptMenu(); ?>
</head>

<body>
    <div class="container py-4">
        <?php 
                $mnuStatic = "";
                echo Menu::menuStaticMenu($mnuStatic);
        ?>
        <div class="card p-3 mb-3" style="background-color:#bcc1eaf5;">
            
            <h1>Payments / Paiements</h1>
            <div class="row">
                <div class="col-4"><a href="<?= $GLOBALS['LocalHome'] ?>childreport/" style="color:white; <?= Settings::buttonDimensionStyle() ?>" class="btn div-buttom-violet">Rapport</a></div>
                <div class="col-4"><?php echo Basket::displayIconBasket() ?></div>                
                <div class="col-4"><a href="<?= $GLOBALS['LocalHome'] ?>manage/" style="color:white; <?= Settings::buttonDimensionStyle() ?>" class="btn div-buttom-violet">Retour Page Enfants</a></div>
            </div>
            
        </div>
        <div class="card p-3 mb-3" style="background-color:#bcc1eaf5;">
            <h2>Recherche</h2>
            
            <form class="row g-2">
                <div class="col-md-3"><input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="form-control"></div>
                <div class="col-md-3"><input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="form-control"></div>
                <div class="col-md-2"><button class="btn btn-primary w-100">Filter</button></div>
            </form>
        </div>
        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Courses / Children</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $gI = 0;
                        $gA = 0;
                        foreach ($rows as $r) {
                            $gI += $r['total_items'];
                            $gA += $r['total_amount']; ?>
                            <tr>
                                <td valign="top"><?= htmlspecialchars($r['payment_day']) ?></td>
                                <td valign="top"><?= $r['total_items'] ?></td>
                                <td valign="top">$ <?= number_format($r['total_amount'], 2) ?></td>
                                <td valign="top"><?= $r['courses'] ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th>Total</th>
                            <th><?= $gI ?></th>
                            <th><?= number_format($gA, 2) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</body>

</html>