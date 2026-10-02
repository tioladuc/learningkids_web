<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
 }
require_once(__DIR__ . "/../core/dbcore.php");
require_once(__DIR__ . "/../core/session.php");
require_once(__DIR__ . "/../core/menu.php");
require_once(__DIR__ . "/../core/setting.php");

SessionHandle::checkingPrivilegeParent();

$pdo = connectBD();
$parentId = $_SESSION['parent_id'];

$sql = "
SELECT
    p.*,
    c.name AS child_name,
    co.name AS course_name
FROM learn4kids_payments p
LEFT JOIN learn4kids_children c
    ON c.id = p.child_id
LEFT JOIN learn4kids_courses co
    ON co.code = p.course_code
WHERE p.parent_id = ? ". ( isset($_GET['childid']) ? ( " AND p.child_id = '". $_GET['childid'] ."' " ):"" ) ."
ORDER BY p.child_id, p.payment_date DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$parentId]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <link rel="icon" type="image/x-icon" href="<?= Settings::getHomePage() ?>favicon/favicon.ico">
    <link rel="icon" type="image/png" href="<?= Settings::getHomePage() ?>favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" href="<?= Settings::getHomePage() ?>favicon/favicon-16x16.png">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Mes paiements </title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php echo Menu::menuStaticScriptMenu(); ?>
    <?php echo Menu::menuStaticCSS(); ?>
    <style>        
        body {
            background: #f4f7fb;
        }

        .page-title {
            font-weight: 700;
            color: #0d6efd;
        }

        .payment-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 18px rgba(0, 0, 0, .08);
            transition: .3s;
        }

        .payment-card:hover {
            transform: translateY(-3px);
        }

        .amount {
            font-size: 26px;
            font-weight: bold;
            color: #198754;
        }

        .badge-paid {
            background: #198754;
        }

        .badge-unpaid {
            background: #dc3545;
        }

        .table td {
            vertical-align: middle;
        }
    </style>

</head>

<body>

    <div class="container py-5">
        <?php
            $mnuStatic = "";
            echo Menu::menuStaticMenu($mnuStatic);
        ?>

        <div class="d-flex justify-content-between align-items-center mb-4">

            <h2 class="page-title">
                💳 Mes paiements <?php echo isset($_GET['name']) ? ( " pour <strong>". $_GET['name'] ."</strong> " ):"" ?>
            </h2>

        </div>

        <div class="row">
            <div class="col-4"><a href="<?= $GLOBALS['LocalHome'] ?>childreport/child=<?= isset($_GET['childid']) ? $_GET['childid'] : '' ?>/name=<?= isset($_GET['name']) ? $_GET['name'] : '' ?>/" style="color:white; <?= Settings::buttonDimensionStyle() ?>" class="btn btn-success">Rapport Stats</a></div>
            <div class="col-4"><?php echo Basket::displayIconBasket() ?></div>                
            <div class="col-4"><a href="<?= $GLOBALS['LocalHome'] ?>manage/" style="color:white; <?= Settings::buttonDimensionStyle() ?>" class="btn btn-success">Retour Page Enfants</a></div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">

            <span class="badge bg-primary fs-6">
                <?= count($payments) ?> paiement(s)
            </span>

            <span class="badge bg-success fs-6">
                <a href="?name=tout le monde" style="color:black;">Voir tous les paiements</a>                
            </span>

        </div>

        <?php if (empty($payments)): ?>

            <div class="alert alert-info text-center p-5">

                <h4>Aucun paiement trouvé</h4>

                <p class="mb-0">
                    Votre historique de paiements apparaîtra ici.
                </p>

            </div>

        <?php else: ?>

            <div class="card payment-card">

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-primary">

                            <tr>

                                <th>Enfant</th>

                                <th>Cours</th>

                                <th>Montant</th>

                                <th>Période</th>

                                <th>Date</th>

                                <th>Statut</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($payments as $row): ?>

                                <tr>

                                    <td>

                                        <strong><?= htmlspecialchars($row['child_name']) ?></strong>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars($row['course_name']) ?>

                                    </td>

                                    <td class="amount">

                                        <?= number_format($row['amount'], 2) ?> $

                                    </td>

                                    <td>

                                        <div>

                                            <?= date('d/m/Y', strtotime($row['startperiod'])) ?>

                                        </div>

                                        <small class="text-muted">

                                            au

                                            <?= date('d/m/Y', strtotime($row['endperiod'])) ?>

                                        </small>

                                    </td>

                                    <td>

                                        <?= date('d/m/Y H:i', strtotime($row['payment_date'])) ?>

                                    </td>

                                    <td>

                                        <?php if ($row['is_paid']): ?>

                                            <span class="badge badge-paid">

                                                Payé

                                            </span>

                                        <?php else: ?>

                                            <span class="badge badge-unpaid">

                                                En attente

                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        <?php endif; ?>

    </div>

</body>

</html>