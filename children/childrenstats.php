<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
 }
require_once(__DIR__ . "/../core/dbcore.php");
require_once(__DIR__ . "/../core/session.php");
require_once(__DIR__ . "/../core/menu.php");
require_once(__DIR__ . "/../core/setting.php");

SessionHandle::checkingPrivilegeParent();

$childid = $_GET['childid']??'';
$pdo = connectBD();

$parentId = $_SESSION['parent_id'];

$sql = "
SELECT
    vc.course_code,
    co.name AS course_name,
    ch.name AS child_name,
    SUM(vc.time_spent) AS total_time,
    COUNT(vc.id) AS visits,
    MAX(vc.last_connection) AS last_connection
FROM learn4kids_visited_courses vc
INNER JOIN learn4kids_children ch
    ON ch.id = vc.child_id
INNER JOIN learn4kids_courses co
    ON co.code = vc.course_code
WHERE ch.parent_id=?
GROUP BY vc.course_code,vc.child_id
ORDER BY total_time DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$parentId]);
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

/************ Statistiques ************/

$totalChildren = $pdo->prepare("
SELECT COUNT(*)
FROM learn4kids_children
WHERE parent_id=?
");
$totalChildren->execute([$parentId]);
$totalChildren = $totalChildren->fetchColumn();

$totalCourses = count(array_unique(array_column($stats, 'course_code')));

$totalVisits = 0;
$totalMinutes = 0;

$labels = [];
$data = [];

foreach ($stats as $row) {

    $totalVisits += $row['visits'];
    $totalMinutes += $row['total_time'];

    if (isset($labels[$row['course_name']])) {
        $data[$row['course_name']] += $row['total_time'];
    } else {
        $labels[$row['course_name']] = $row['course_name'];
        $data[$row['course_name']] = $row['total_time'];
    }
}

function formatTime($minutes)
{

    $minutes = intval($minutes);

    $h = floor($minutes / 60);

    $m = $minutes % 60;

    if ($h == 0)
        return $m . " min";

    return $h . " h " . $m . " min";
}

$maxTime = 1;

foreach ($stats as $s) {
    if ($s['total_time'] > $maxTime)
        $maxTime = $s['total_time'];
}

?>
<!doctype html>

<html lang="fr">

<head>
    <link rel="icon" type="image/x-icon" href="<?= Settings::getHomePage() ?>favicon/favicon.ico">
    <link rel="icon" type="image/png" href="<?= Settings::getHomePage() ?>favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" href="<?= Settings::getHomePage() ?>favicon/favicon-16x16.png">
    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Statistiques des cours</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <?php echo Menu::menuStaticScriptMenu(); ?>

    <?php echo Menu::menuStaticCSS(); ?>
    <style>        
        body {
            background: #eef3f8;
        }

        .card-stat {

            border: none;

            border-radius: 18px;

            box-shadow: 0 5px 18px rgba(0, 0, 0, .08);

            transition: .25s;
        }

        .card-stat:hover {

            transform: translateY(-4px);

        }

        .big {

            font-size: 34px;

            font-weight: bold;

            color: #0d6efd;

        }

        .table-card {

            border: none;

            border-radius: 18px;

            overflow: hidden;

            box-shadow: 0 5px 18px rgba(0, 0, 0, .08);

        }

        .table tbody tr:hover {

            background: #f7fbff;

        }

        .progress {

            height: 10px;

        }

        @media(max-width:768px) {

            .desktop-table {

                display: none;

            }

            .mobile-card {

                display: block;

            }

        }

        @media(min-width:769px) {

            .mobile-card {

                display: none;

            }

        }
    </style>

</head>

<body>

    <div class="container py-4">
        <?php
            $mnuStatic = "";
            echo Menu::menuStaticMenu($mnuStatic);
        ?>
        <h2 class="mb-4 text-primary">

            <i class="bi bi-bar-chart-fill"></i>

            Statistiques des cours

        </h2>
        
        <div class="row">
            <div class="col-4"><a href="<?= $GLOBALS['LocalHome'] ?>childreport/" style="color:white; <?= Settings::buttonDimensionStyle() ?>" class="btn div-buttom-violet">Rapport Stats</a></div>
            <div class="col-4"><?php echo Basket::displayIconBasket() ?></div>                
            <div class="col-4"><a href="<?= $GLOBALS['LocalHome'] ?>manage/" style="color:white; <?= Settings::buttonDimensionStyle() ?>" class="btn div-buttom-violet">Retour Page Enfants</a></div>
        </div>
        <div class="row g-4 mb-4">

            <div class="col-lg-3 col-md-6">

                <div class="card card-stat">

                    <div class="card-body text-center">

                        <div class="big">

                            <?= $totalChildren ?>

                        </div>

                        <div>

                            👧 Enfants

                        </div>

                    </div>

                </div>

            </div>

            <div class="col-lg-3 col-md-6">

                <div class="card card-stat">

                    <div class="card-body text-center">

                        <div class="big">

                            <?= $totalCourses ?>

                        </div>

                        <div>

                            📚 Cours

                        </div>

                    </div>

                </div>

            </div>

            <div class="col-lg-3 col-md-6">

                <div class="card card-stat">

                    <div class="card-body text-center">

                        <div class="big">

                            <?= formatTime($totalMinutes) ?>

                        </div>

                        <div>

                            ⏱ Temps total

                        </div>

                    </div>

                </div>

            </div>

            <div class="col-lg-3 col-md-6">

                <div class="card card-stat">

                    <div class="card-body text-center">

                        <div class="big">

                            <?= $totalVisits ?>

                        </div>

                        <div>

                            🔄 Visites

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <div class="card table-card mb-4">

            <div class="card-body">

                <canvas id="chart" height="110"></canvas>

            </div>

        </div>

        <div class="card table-card desktop-table">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-primary">

                        <tr>

                            <th>Enfant</th>

                            <th>Cours</th>

                            <th>Temps</th>

                            <th>Progression</th>

                            <th>Visites</th>

                            <th>Dernière connexion</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($stats as $row):

                            $percent = ($row['total_time'] / $maxTime) * 100;

                        ?>

                            <tr>

                                <td>

                                    <strong><?= htmlspecialchars($row['child_name']) ?></strong>

                                </td>

                                <td>

                                    <?= htmlspecialchars($row['course_name']) ?>

                                </td>

                                <td>

                                    <?= formatTime($row['total_time']) ?>

                                </td>

                                <td width="220">

                                    <div class="progress">

                                        <div class="progress-bar bg-success"

                                            style="width:<?= $percent ?>%">

                                        </div>

                                    </div>

                                </td>

                                <td>

                                    <?= $row['visits'] ?>

                                </td>

                                <td>

                                    <?= date("d/m/Y H:i", strtotime($row['last_connection'])) ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

        <?php foreach ($stats as $row):

            $percent = ($row['total_time'] / $maxTime) * 100;

        ?>

            <div class="card card-stat mobile-card mb-3">

                <div class="card-body">

                    <h5>

                        <?= htmlspecialchars($row['child_name']) ?>

                    </h5>

                    <div>

                        <strong>📚 Cours :</strong>

                        <?= htmlspecialchars($row['course_name']) ?>

                    </div>

                    <div>

                        <strong>⏱ Temps :</strong>

                        <?= formatTime($row['total_time']) ?>

                    </div>

                    <div>

                        <strong>🔄 Visites :</strong>

                        <?= $row['visits'] ?>

                    </div>

                    <div>

                        <strong>🕒 Dernière connexion :</strong>

                        <?= date("d/m/Y H:i", strtotime($row['last_connection'])) ?>

                    </div>

                    <div class="progress mt-3">

                        <div class="progress-bar bg-success"

                            style="width:<?= $percent ?>%">

                        </div>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const ctx = document.getElementById('chart');

        new Chart(ctx, {

            type: 'bar',

            data: {

                labels: <?= json_encode(array_values($labels)) ?>,

                datasets: [{

                    label: 'Temps passé (minutes)',

                    data: <?= json_encode(array_values($data)) ?>,

                    borderWidth: 1

                }]

            },

            options: {

                responsive: true,

                plugins: {

                    legend: {
                        display: false
                    }

                },

                scales: {

                    y: {

                        beginAtZero: true

                    }

                }

            }

        });
    </script>

</body>

</html>