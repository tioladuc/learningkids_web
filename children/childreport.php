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

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$child = $_GET['child'] ?? '';
$course = $_GET['course'] ?? '';

$queryChildren = "SELECT id, name FROM learn4kids_children WHERE parent_id = '". $_SESSION['parent_id'] ."'";
$s = $pdo->prepare($queryChildren);
$s->execute();
$rowsChildren = $s->fetchAll(PDO::FETCH_ASSOC);
$selectChildren = "<select name='child'><option value=''>Tous</option>";
$inchildren = "''";
for ($i=0; $i <count($rowsChildren) ; $i++) { 
    $inchildren .= ",'". $rowsChildren[$i]['id'] ."'";
    $selectChildren .= "<option value='". $rowsChildren[$i]['id'] ."' ". ($rowsChildren[$i]['id']==$child ? "SELECTED": "") ." >". $rowsChildren[$i]['name'] ."</option>";
}
$selectChildren .= "</select>";

$queryCourse = "SELECT learn4kids_courses.code, learn4kids_courses.name FROM learn4kids_child_courses 
                JOIN learn4kids_courses ON (learn4kids_courses.code = learn4kids_child_courses.course_code) 
                WHERE learn4kids_child_courses.child_id IN ($inchildren) ";
$s = $pdo->prepare($queryCourse);
$s->execute();
$rowsCourses = $s->fetchAll(PDO::FETCH_ASSOC);
$selectCourse = "<select name='course'><option value=''>Tous</option>";
for ($i=0; $i <count($rowsCourses) ; $i++) { 
    $selectCourse .= "<option value='". $rowsCourses[$i]['code'] ."' ". ($rowsCourses[$i]['code']==$course ? "SELECTED": "") ." >". $rowsCourses[$i]['name'] ."</option>";
}
$selectCourse .= "</select>";



$query = "SELECT
        courses.code as courses_code,
        courses.name as courses_name,


        child.id as child_child_id,
        child.parent_id as parent_id,
        child.name as child_name,
        child.login as child_login,
        child.passwordraw as child_passwordraw,
        child.password as child_password,
        child.level as child_level,


        v.time_spent as duration_minute,
        v.last_connection as last_connection_time,


        v_detail.course_item_id as course_item_id,
        v_detail.access_date as course_item_access_time


        FROM learn4kids_visited_courses_items v_detail 
        JOIN learn4kids_visited_courses v ON(v_detail.course_code = v.course_code)
        JOIN learn4kids_children child ON (child.id =v.child_id)
        JOIN learn4kids_courses courses ON (courses.code = v.course_code)

        WHERE v.child_id LIKE '%". $child ."%' AND v.course_code LIKE '%". $course ."%' 
        ". ($to=='' ? "" : " AND v_detail.access_date <= '$to 23:59:59' ") ." 
        ". ($from=='' ? "" : " AND v_detail.access_date >= '$from' ") ." 
        ORDER BY v.last_connection, child.id";

$s = $pdo->prepare($query);
$s->execute();
$rows = $s->fetchAll(PDO::FETCH_ASSOC);


$queryTotalChildren = "SELECT
        
        child.name as child_name,
        child.login as child_login,
        count(disctint v.time_spent) as duration_minute

        FROM learn4kids_visited_courses_items v_detail 
        JOIN learn4kids_visited_courses v ON(v_detail.course_code = v.course_code)
        JOIN learn4kids_children child ON (child.id =v.child_id)
        JOIN learn4kids_courses courses ON (courses.code = v.course_code)

        WHERE v.child_id LIKE '%". $child ."%' AND v.course_code LIKE '%". $course ."%' 
        ". ($to=='' ? "" : " AND v_detail.access_date <= '$to 23:59:59' ") ." 
        ". ($from=='' ? "" : " AND v_detail.access_date >= '$from' ") ." 
        ORDER BY child.name, child.login";

$s = $pdo->prepare($query);
$s->execute();
$rowsChildTotal = $s->fetchAll(PDO::FETCH_ASSOC);
$childTotal = array();
for ($i=0; $i < count($rowsChildTotal) ; $i++) { 
    $childTotal[ $rowsChildTotal[$i]['child_name'] ] = $rowsChildTotal[$i]['duration_minute'];
}


$query = "SELECT
        courses.code as courses_code,
        courses.name as courses_name,

        child.name as child_name,
        child.login as child_login,

        count(distinct v.time_spent) as duration_minute

        FROM learn4kids_visited_courses_items v_detail 
        JOIN learn4kids_visited_courses v ON(v_detail.course_code = v.course_code)
        JOIN learn4kids_children child ON (child.id =v.child_id)
        JOIN learn4kids_courses courses ON (courses.code = v.course_code)

        WHERE v.child_id LIKE '%". $child ."%' AND v.course_code LIKE '%". $course ."%' 
        ". ($to=='' ? "" : " AND v_detail.access_date <= '$to 23:59:59' ") ." 
        ". ($from=='' ? "" : " AND v_detail.access_date >= '$from' ") ." 
        ORDER BY child.name, child.login, courses.code, courses.name";

$s = $pdo->prepare($query);
$s->execute();
$rowsChildCourseTotal = $s->fetchAll(PDO::FETCH_ASSOC);
$childDetailTotal = array();
for ($i=0; $i < count($rowsChildCourseTotal) ; $i++) { 
    $childDetailTotal[ $rowsChildCourseTotal[$i]['child_name'] ][ $rowsChildCourseTotal[$i]['courses_name'] ] = $rowsChildCourseTotal[$i]['duration_minute'];
}
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
    <title>Statistique d'etude des enfants</title>
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

    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChartChildrenTotal);//google.charts.setOnLoadCallback(drawChart);

      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['Task', 'Hours per Day'],
          ['Work',     11],
          ['Eat',      2],
          ['Commute',  2],
          ['Watch TV', 2],
          ['Sleep',    7]
        ]);

        var options = {
          title: 'My Daily Routine'
        };

        var chart = new google.visualization.PieChart(document.getElementById('piechart'));

        chart.draw(data, options);
      }

      <?php
        $compteur = 0;
        $titles = array();
        $titles[count($titles)] = "Chart Tous Enfants";
        $dataJsChildren = "['Enfant', 'Total minutes']";
        foreach($childTotal as $k => $v) {
            $dataJsChildren .= ",['$k',  $v]";
        }
        echo "function drawChartChildrenTotal$compteur() {
        var position = $compteur;
        var data = google.visualization.arrayToDataTable([
          $dataJsChildren
        ]);

        var options = {
          title: 'Total de minutes de travail par enfants'
        };
        
        var chart = new google.visualization.PieChart(document.getElementById('piechartChildrenTotal$compteur'));
        
        chart.draw(data, options);
      }
        ";
      
     foreach($childDetailTotal as $key => $val) {
        $dataJsChildren = "['Enfant', 'Total minutes']";
        $compteur++;
        foreach($val as $k => $v) {
            $dataJsChildren .= ",['$k',  $v]";
        }
        $titles[count($titles)] = "Chart $key"; 
        echo "function drawChartChildrenTotal$compteur() {
        var position = $compteur;
        var data = google.visualization.arrayToDataTable([
          $dataJsChildren
        ]);

        var options = {
          title: 'Total de minutes de travail par cours pour $key'
        };
        
        var chart = new google.visualization.PieChart(document.getElementById('piechartChildrenTotal$compteur'));
        
        chart.draw(data, options);
      }
        ";
     }
     echo "function drawChartChildrenTotal() {";
     for ($i=0; $i <=$compteur ; $i++) { 
        echo "drawChartChildrenTotal". $i ."();";
     }
     echo "}";

      ?>
    function choix(i) {
        document.querySelectorAll('[id^="piechartChildrenTotal"]').forEach(function(element) {
            element.style.display = 'none';
        });

        document.querySelectorAll('[id^="choix_"]').forEach(function(element) {
            element.style.backgroundColor = "#0000ff21";
        });

        element = document.getElementById("piechartChildrenTotal" + i);
        if (element) {
            element.style.display = "block";
        }

        element = document.getElementById("choix_" + i);
        if (element) {
            element.style.backgroundColor = "#0000ff7d";
        }
        
    }
    window.addEventListener("load", function () {
        choix(0);
    });
    
    </script>
</head>

<body>
    <div class="container py-4">
        <?php 
                $mnuStatic = "";
                echo Menu::menuStaticMenu($mnuStatic);
        ?>
        <div class="card p-3 mb-3" style="background-color:#bcc1eaf5;">
            <h1>Statistique de Travail <?php echo isset($_GET['name']) ? " pour <strong>". $_GET['name'] ."</strong> " : "" ?></h1>
            <div class="row">
                <div class="col-4"><a href="<?= $GLOBALS['LocalHome'] ?>childrenstats/" style="color:white; <?= Settings::buttonDimensionStyle() ?>" class="btn div-buttom-violet">Autre Rapport Stats</a></div>
                <div class="col-4"><?php echo Basket::displayIconBasket() ?></div>                
                <div class="col-4"><a href="<?= $GLOBALS['LocalHome'] ?>manage/" style="color:white; <?= Settings::buttonDimensionStyle() ?>" class="btn div-buttom-violet">Retour Page Enfants</a></div>
            </div>
            
        </div>
        <div class="card p-3 mb-3" style="background-color:#bcc1eaf5;">
            <h2>Recherche </h2>            
            <form class="row g-2">
                <div class="col-2"><input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="form-control"></div>
                <div class="col-2"><input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="form-control"></div>
                <div class="col-2">Enfant: <?=  $selectChildren ?></div>
                <div class="col-4">Cours: <?= $selectCourse ?></div>
                <div class="col-2"><button class="btn btn-primary w-100">Filter</button></div>
            </form>
        </div>
        <!--div class="card">            
            <div id="piechart" style="width: 900px; height: 500px;"></div>
        </div-->
        <div class="card">
            <table><tr>
                <?php 
                    for ($i=0; $i < ($compteur+1) ; $i++) { 
                        echo "<td align='center' onclick='choix($i)' ><div id='choix_$i' style='padding:10px; border-radius:10px; width:200px;'><strong>". $titles[$i] ."</strong></div></td>";
                    }
                    echo "<td align='center' onclick='choix(".($compteur+1).")' ><div id='choix_".($compteur+1)."' style='padding:10px; border-radius:10px; width:200px;'><strong>Details</strong></div></td>";
                ?>
            </tr></table>
            <?php 
                for ($i=0; $i <= $compteur ; $i++) { 
                    echo "<div id='piechartChildrenTotal$i' class='piechartChildrenTotal$i' style='min-width: 900px; height: 500px;'></div>";
                }
            ?>
            <div class="card-body tt-table-responsive piechartChildrenTotal<?php echo ($compteur+1); ?>" id="piechartChildrenTotal<?php echo ($compteur+1); ?>">
                <table class="table table-hover align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>Date</th>
                            <th>Durée</th>
                            
                            <th>Enfant</th>
                            <th>Cours</th>
                            <th>Element Cours</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $ro = 0;
                        foreach ($rows as $r) { $ro++; ?>
                            <tr>
                                <td valign="top"<?=  ($ro%2) ? " style='background: ghostwhite;' " : "" ?>><?= htmlspecialchars($r['last_connection_time']) ?></td>
                                <td valign="top"<?=  ($ro%2) ? " style='background: ghostwhite;' " : "" ?>><?= $r['duration_minute'] ?></td>
                                <td valign="top"<?=  ($ro%2) ? " style='background: ghostwhite;' " : "" ?>><?= $r['child_name'] ?> / <?= $r['child_passwordraw'] ?> / <?= $r['child_level'] ?></td>
                                <td valign="top"<?=  ($ro%2) ? " style='background: ghostwhite;' " : "" ?>><?= $r['courses_name'] ?> [<?= $r['courses_code'] ?>]</td>
                                <td valign="top"<?=  ($ro%2) ? " style='background: ghostwhite;' " : "" ?>><?= $r['course_item_id'] ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                    
                </table>
            </div>
        </div>
    </div>
</body>

</html>