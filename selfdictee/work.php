<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
 }
require_once( __DIR__ . '/../core/recorder.php');
require_once( __DIR__ . '/../core/setting.php');
require_once( __DIR__ . '/../core/dbcore.php');
require_once( __DIR__ . '/../core/session.php');
require_once( __DIR__ . '/../core/menu.php');

SessionHandle::checkingPrivilegeChild();
//exit;
$codeCourse = $GLOBALS['CourseCode'];
$codeLesson = $_GET['codeLesson'] ?? '';
//exit;
SessionHandle::start($codeCourse, $codeLesson);


$levelsManage = Settings::getLevelsInFrench() ;

$pdo = connectBD();
$recorder = new Recorder();

$table = "learn4kids_selfdictation";

if(isset($_POST['id'])) {
    $dictee = "<ol>";
    for ($i=0; $i <100 ; $i++) { 
        $elt = "element$i";
        if(isset($_POST[$elt]) && trim($_POST[$elt]) != "") {
            $dictee .= "<li>". str_replace("'", "ʼ", $_POST[$elt]) ."</li>";
        }
    }
    $dictee .= "</ol>";
    $id = $_POST['id'];

    if($_POST['id'] == 0 || $_POST['id'] == '0') {
        $sql = "INSERT INTO $table(title, level, dictee, astuces, active, created_on, created_by)
                    VALUES('". $_POST['title'] ."', '". $_POST['level'] ."', '$dictee', NULL, 1, now(), '". $_SESSION['current_id'] ."')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        $query = "SELECT * FROM $table WHERE created_by = '". $_SESSION['current_id'] ."' ORDER BY id desc";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $id = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
        $message = "Element créé avec succès";
    } 
    else {
        $sql = "UPDATE $table SET title = '". $_POST['title'] ."', level = '". $_POST['level'] ."', dictee = '$dictee' WHERE id = " . $_POST['id'];
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $message = "Element mise à jour avec succès";
    }
    $url = Settings::getHomePage() . "selfdictee/work/id=". $id ."/section=solution/?msg=" . $message;
    header("Location: " . $url);
}

function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

function beautifyHtml($html)
{
    $html = str_replace("<ul>", "<ul class='beautiful-list'>", $html);
    //$html = str_replace("<ol>", "<ol class='beautiful-list'>", $html);
    $html = str_replace("<ol>", "<ol class='animated-list'>", $html);
    $html = str_replace("<li>", "<li tabindex='0'><div style='border: 2px solid #3498db; padding: 10px; margin: 5px; border-radius: 10px;'>", $html);
    $html = str_replace("</li>", "</div></li>", $html);
    $html = str_replace("<p>", "<p class='beautiful-p'>", $html);
    return $html;
}

function make_dictation_form_display($title, $dictee, $id, $level, $levelsManage, $totalItem = 30) {
    $message = $_GET['msg']?? "";
    $dictee = str_replace("<ol>", "", $dictee);
    $dictee = str_replace("</ol>", "", $dictee);
    $dictee = explode("<li>", $dictee);
    $arrayDictee = array();

    for ($i=0; $i < count($dictee) ; $i++) { 
        $dictee[$i] = str_replace("<li>", "", $dictee[$i]);
        $dictee[$i] = str_replace("</li>", "", $dictee[$i]);
        if( trim($dictee[$i]) != "") {
            $arrayDictee[ count($arrayDictee) ] = trim($dictee[$i]);
        }
    }

    $html = ($message=="" ? "" : "<span style='color:red;'>$message</span><br>") . "<form method='post'>";
    $html.= "<strong>Niveau : </strong><select name='level' class='animated-select'>";
    foreach($levelsManage as $k => $v) $html .= "<option value='$k' ". ($k==$level ? " SELECTED ":"") ." >$v</option>";
    $html .= "</select><br><br>";
    $html .= "<strong>Titre Dictée : </strong><input name='title' value='$title' type='text' class='animated-input-text' style='background-color: #faf5f5ec;'/>
                <input name='id' value='$id' type='hidden' /><ol class='animated-list'>";
    for ($i=0; $i <$totalItem ; $i++) { 
        $html .= "<li tabindex='0'><input type='text' name='element$i' value='". ( ($i< count($arrayDictee)) ? $arrayDictee[$i] : "" ) ."' class='animated-input-text' ></li><br/>";
    }
    $html .= "</ol><input type='submit' class='input-submit-blue-bottom' name='submit' value='Enregistrer'>";
    $html .= "</form>";
    return $html;
}



/* DETAIL */
if (isset($_GET['id'])) {
    $item = array('title' => date('Y-m-d H:i'), 'dictee' => null, 'id' => 0, 'level' => 'Level3');
    if($_GET['id'] != 0 && $_GET['id'] != '0') {
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE id=? AND active=1");
        $stmt->execute([(int)$_GET['id']]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
    }    

    if (!$item) {
        die("Exposé introuvable");
    }

    $sections = [
        'subject' => [
            'title' => 'Faire Dictée',
            'content' => '<div class="subject-box"><br>'. 
                                ($recorder->is_audio_existing($codeCourse ?? 'Self-Dictee', 'selfdictee_' . $item['id'], $_SESSION['current_id']) ? 
                                        $recorder->displayRecorderTag($codeCourse ?? 'Expose', 'selfdictee_' . $item['id'], $_SESSION['current_id']) : 
                                        "<p><strong>Enregistrer</strong> d'abord votre dictée afin d'avoir l'audio.</p>") .'<br><br></div>'
        ],
        'description' => [
            'title' => 'Vérifier',
            'content' => beautifyHtml($item['dictee']) . "<div style='margin-top:20px'>" . ($recorder->is_audio_existing($codeCourse ?? 'Self-Dictee', 'selfdictee_' . $item['id'], $_SESSION['current_id']) ? 
                                        $recorder->displayRecorderTag($codeCourse ?? 'Expose', 'selfdictee_' . $item['id'], $_SESSION['current_id']) : 
                                        "<p><strong>Enregistrer</strong> votre dictée afin d'avoir l'audio.</p>") . "</div>"
        ],
        'solution' => [
            'title' => 'Enregistrer',
            'content' => make_dictation_form_display($item['title'], $item['dictee'], $item['id'], $item['level'], $levelsManage, 30) . "<div style='margin-top:20px'>" . ( ($item['dictee']!=null && trim($item['dictee'])!="") ? 
                                        $recorder->displayRecorderTag($codeCourse ?? 'Expose', 'selfdictee_' . $item['id'], $_SESSION['current_id']) : 
                                        "<p><strong>Enregistrer</strong> votre dictée afin d'avoir l'audio.</p>") . "</div>"
        ]
    ];

    $current = $_GET['section'] ?? 'subject';
    if (!isset($sections[$current])) $current = 'subject';
?>
    <!DOCTYPE html>
    <html lang="fr">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title><?= h($item['title']) ?></title>
        <?php echo Menu::menuStaticScriptMenu(); ?>
        <?php $recorder->javascript(); ?>
        <?php echo Menu::menuStaticCSS(); ?>
        <style>
            <?php echo $recorder->css(); ?>
            body {
                margin: 0;
                font-family: Segoe UI;
                background: linear-gradient(135deg, #4f46e5, #7c3aed)
            }                               
            
            .container {
                max-width: 1100px;
                margin: auto;
                padding: 20px
            }

            .card {
                background: #fff;
                border-radius: 25px;
                padding: 30px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, .08)
            }

            h1 {
                margin-top: 0
            }

            .badge {
                display: inline-block;
                padding: 8px 15px;
                border-radius: 999px;
                background: #eef2ff;
                color: #4338ca;
                font-weight: bold
            }

            .tabs {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
                margin: 20px 0
            }

            .tabs a {
                text-decoration: none;
                padding: 12px 18px;
                border-radius: 12px;
                background: #f1f5f9;
                color: #111
            }

            .tabs a.active {
                background: black;
                color: white
            }

            .beautiful-p {
                font-size: 1.1rem;
                line-height: 1.9
            }

            .beautiful-list li {
                background: #f8fafc;
                margin: 12px 0;
                padding: 15px;
                border-radius: 15px
            }

            .back {
                display: inline-block;
                margin-bottom: 15px;
                text-decoration: none;
                background: white;
                padding: 12px 18px;
                border-radius: 12px
            }




        </style>
    </head>

    <body>
        <div class="container">
            <?php 
                echo Menu::menuStaticMenu("<a class='back' href='" . $GLOBALS['LocalHome'] . "work/'>← Retour</a>");
            ?>
            
            <div class="card">
                <h1><?= h($item['title']) ?></h1>
                <div class="badge"><?= h($levelsManage[$item['level']]??current($levelsManage)) ?></div>

                <div class="tabs">
                    <?php foreach ($sections as $k => $s) { ?>
                        <a class="<?= ($k == $current ? 'active' : '') ?>"
                            href="<?= $GLOBALS['LocalHome'] ?>work/id=<?= $item['id'] ?>/section=<?= $k ?>/">
                            <?= $s['title'] ?>
                        </a>
                    <?php } ?>
                </div>

                <div>
                    <?= $sections[$current]['content'] ?>
                </div>

            </div>
        </div>
    </body>

    </html>
<?php
    exit;
}

$msg = "";
if($_POST['operation'] ?? '' === 'DELETE' && isset($_POST['valID'])) {
    $valID = (int)$_POST['valID'];
    $stmt = $pdo->prepare("DELETE FROM $table WHERE id=? AND created_by=?");
    $stmt->execute([$valID, $_SESSION['current_id']]);
    $fileExist = __DIR__ . "/../audio-recorder-v2/audio/". $GLOBALS['CourseCode'] ."/". $_SESSION['current_id'] . "_selfdictee_" . $valID . "_" . $GLOBALS['CourseCode'] . ".mp3";
    
    if(file_exists($fileExist)) {
        $msg .= "Element supprimé avec succès ! ....";
        unlink($fileExist);
    } else {
        $msg .= "Fichier audio non existant mais element supprimé avec succès ! ....";        
    }
    
}
$level = $_GET['level'] ?? '';
$q = trim($_GET['q'] ?? '');
$order = $_GET['order'] ?? 'recent';

$sql = "SELECT * FROM $table WHERE created_by = '". $_SESSION['current_id'] ."' AND active=1";
$params = [];

if ($level !== '') {
    $sql .= " AND level=?";
    $params[] = $level;
}

if ($q !== '') {
    $sql .= " AND title LIKE ?";
    $params[] = "%$q%";
}

switch ($order) {
    case 'title_asc':
        $sql .= " ORDER BY title ASC";
        break;
    case 'title_desc':
        $sql .= " ORDER BY title DESC";
        break;
    case 'old':
        $sql .= " ORDER BY created_on ASC";
        break;
    default:
        $sql .= " ORDER BY created_on DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$levels = array();//["Level1", "Level2", "Level3", "Level4", "Level5", "Level6", "Expert"];
foreach ($levelsManage as $key => $value) {
    $levels[] = $key;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Ta Dictée</title>
    <?php echo Menu::menuStaticScriptMenu(); ?>
    <?php echo Menu::menuStaticCSS(); ?>
    <style>
        <?php echo $recorder->css(); ?>
        body {
            margin: 0;
            font-family: Segoe UI;
            background: linear-gradient(135deg, #4f46e5, #7c3aed)
        }

        .container {
            max-width: 1300px;
            margin: auto;
            padding: 20px
        }

        .hero {
            text-align: center;
            color: white;
            padding: 40px 20px
        }

        .hero h1 {
            font-size: 3rem
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 25px;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .15)
        }

        input,
        select {
            padding: 14px;
            border-radius: 12px;
            border: 1px solid #ddd
        }

        button {
            background: #4f46e5;
            color: white;
            border: none;
            padding: 14px 22px;
            border-radius: 12px
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-top: 25px
        }

        .card {
            background: white;
            border-radius: 25px;
            padding: 25px;
            text-decoration: none;
            color: #111;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .12);
            transition: .3s
        }

        .card:hover {
            transform: translateY(-6px)
        }

        .badge {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 999px;
            background: #eef2ff;
            color: #4338ca;
            font-weight: bold
        }

        .desc {
            color: #666;
            margin-top: 15px
        }

        .date {
            margin-top: 15px;
            color: #888;
            font-size: .9rem
        }

        @media(max-width:900px) {
            .filters {
                grid-template-columns: 1fr
            }

            .hero h1 {
                font-size: 2rem
            }
        }
    </style>
    <script>
        
        function deleteItemButton(valID, event) {
            event.preventDefault();

            if (!confirm("Are you sure you want to delete this item / Voulez vous vraiment supprimer cet élément ?")) {
                return;
            }

            const form = document.createElement("form");
            form.method = "POST";
            form.action = "<?= $GLOBALS['LocalHome'] ?>work/";

            form.innerHTML = `
                <input type="hidden" name="valID" value="${valID}">
                <input type="hidden" name="operation" value="DELETE">
            `;

            document.body.appendChild(form);
            form.submit();
        }
    </script>
</head>

<body>
    <div class="container">
        <?php 
                echo Menu::menuStaticMenu("");
        ?>
        <div class="hero">
            <h1>🎤 Enregistre tes dictées et fais les !</h1>
            <p>Ecrire tes propres dictées, lire tes propres dictées et faires tes propres dictées.</p>
            <center><font style="color: red;"><?= $msg ?></font></center>
        </div>

        <form class="filters" style="grid-template-columns: 2fr 1fr 1fr 1fr auto !important;">
            <input name="q" placeholder="Rechercher un sujet..." value="<?= h($q) ?>">

            <select name="level" class='animated-select'>
                <option value="">Tous les niveaux</option>
                <?php foreach ($levels as $l) { ?>
                    <option value="<?= $l ?>" <?= ($level == $l ? 'selected' : '') ?>><?= $levelsManage[$l] ?></option>
                <?php } ?>
            </select>

            <select name="order" class='animated-select'>
                <option value="recent">Plus récents</option>
                <option value="old" <?= ($order == 'old' ? 'selected' : '') ?>>Plus anciens</option>
                <option value="title_asc" <?= ($order == 'title_asc' ? 'selected' : '') ?>>Titre A-Z</option>
                <option value="title_desc" <?= ($order == 'title_desc' ? 'selected' : '') ?>>Titre Z-A</option>
            </select>

            <button class='input-submit-black-bottom'><strong>Filtrer</strong></button>
            <a href='<?= $GLOBALS['LocalHome'] ?>work/id=0/section=solution/' class='input-submit-blue-bottom'
                ><strong>Ajouter Dictée</strong></a>
        </form>

        <form action="<?= $GLOBALS['LocalHome'] ?>work/" method="post">
                        
            <div class="grid">
                <?php foreach ($list as $row) { ?>
                    <a class="card" href="<?= $GLOBALS['LocalHome'] ?>work/id=<?= $row['id'] ?>/<?php echo (!isset($_GET['login']) || $_GET['login'] === '') ? "" : ("login=" . $_GET['login'] . '/') ?>">
                        <div class="badge"><?= h($levelsManage[$row['level']] ?? $row['level']) ?></div>
                        <h2><?= h($row['title']) ?></h2>
                        <div class="desc">Prépare, lis et fais ta dictée. Tu es à la fois le maître et l'élève: C'est fantastique!.</div>
                        <div class="date"><?= date('d/m/Y H:i:s', strtotime($row['created_on'])) ?></div>
                        <div class="div-buttom-violet">Commencer →</div>
                        &nbsp;&nbsp;<div class="div-buttom-red"  onclick="deleteItemButton(<?= $row['id'] ?>, event)">&nbsp;&nbsp;&nbsp;&nbsp;Supprimer&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    </a>
                <?php } ?>
            </div>
        </form>

    </div>
</body>

</html>