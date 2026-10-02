<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
 }
require_once(__DIR__ . "/../core/setting.php");
require_once(__DIR__ . "/../core/dbcore.php");
require_once(__DIR__ . "/../core/menu.php");
require_once(__DIR__ . "/../core/dbcore.php");
require_once(__DIR__ . "/../core/session.php");

SessionHandle::checkingPrivilegeChild();
$codeCourse = $GLOBALS['CourseCode'] ;
$codeLesson = $_GET['codeLesson'] ?? '';
SessionHandle::start($codeCourse, $codeLesson);

$pdo = connectBD();

function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

$levelsManage = Settings::getLevelsInFrench();

function xmlTextToHtml($xml)
{
    preg_match('/<text>(.*?)<\/text>/s', $xml, $m);
    return $m[1] ?? '';
}
function xmlQuestionsToHtml($xml)
{
    $html = '';
    preg_match_all('/<question>(.*?)<\/question>/s', $xml, $m);
    foreach ($m[1] as $it) {
        preg_match('/<number>(.*?)<\/number>/s', $it, $n);
        preg_match('/<text>(.*?)<\/text>/s', $it, $t);
        $html .= "<div class='question-item'><span class='num'>" . ($n[1] ?? '') . "</span>" . ($t[1] ?? '') . "</div>";
    }
    return $html;
}
function xmlReponsesToHtml($xml)
{
    $html = '';
    preg_match_all('/<reponse>(.*?)<\/reponse>/s', $xml, $m);
    foreach ($m[1] as $it) {
        preg_match('/<number>(.*?)<\/number>/s', $it, $n);
        preg_match('/<text>(.*?)<\/text>/s', $it, $t);
        $html .= "<div class='answer-item'><span class='num'>" . ($n[1] ?? '') . "</span><strong>" . ($t[1] ?? '') . "</strong></div>";
    }
    return $html;
}

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM learn4kids_comprehension WHERE id=? AND is_active=1");
    $stmt->execute([(int)$_GET['id']]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$item) die("Texte introuvable");

    $sections = [
        'texte' => ['title' => '📖 Texte', 'content' => xmlTextToHtml($item['text_comprehension'])],
        'questions' => ['title' => '❓ Questions', 'content' => xmlQuestionsToHtml($item['questions_comprehension'])],
        'reponses' => ['title' => '✅ Réponses', 'content' => xmlReponsesToHtml($item['reponses_comprehension'])]
    ];
    $current = $_GET['section'] ?? 'texte';
    if (!isset($sections[$current])) $current = 'texte';
?>
    <!DOCTYPE html>
    <html lang="fr">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= h($item['title']) ?></title>
        <?= Menu::menuStaticScriptMenu(); ?>
        <?php echo Menu::menuStaticCSS(); ?>
        <style>
            body {
                margin: 0;
                font-family: Segoe UI, sans-serif;
                background: linear-gradient(135deg, #4f46e5, #7c3aed)
            }

            .container {
                max-width: 1100px;
                margin: auto;
                padding: 20px
            }

            .card {
                background: #fff;
                padding: 30px;
                border-radius: 25px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, .15)
            }

            .badge {
                display: inline-block;
                padding: 8px 14px;
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
                color: #fff
            }

            .question-item,
            .answer-item {
                background: #f8fafc;
                padding: 15px;
                border-radius: 12px;
                margin-bottom: 10px
            }

            .num {
                display: inline-block;
                width: 60px;
                font-weight: bold;
                color: #4f46e5
            }

            .reading-content {
                font-size: 1.1rem;
                line-height: 1.8
            }
        </style>
    </head>

    <body>
        <div class="container">
            <?= Menu::menuStaticMenu("<a href='" . $GLOBALS['LocalHome'] . "work/" . "'>← Retour</a>"); ?>
            <div class="card">
                <h1><?= h($item['title']) ?></h1>
                <div class="badge"><?= h($levelsManage[$item['level']] ?? $item['level']) ?></div>
                <div class="badge"><?= h($item['category']) ?></div>
                <div class="tabs">
                    <?php foreach ($sections as $k => $s) { ?>
                        <a class="<?= $current == $k ? 'active' : '' ?>" href="<?= $GLOBALS['LocalHome'] ?>work/id=<?= $item['id'] ?>/section=<?= $k ?>/"><?= $s['title'] ?></a>
                    <?php } ?>
                </div>
                <div class="reading-content"><?= $sections[$current]['content'] ?></div>
            </div>
        </div>
    </body>

    </html>
<?php exit;
}

$level = $_GET['level'] ?? '';
$category = $_GET['category'] ?? '';
$q = trim($_GET['q'] ?? '');
$order = $_GET['order'] ?? 'recent';

$sql = "SELECT * FROM learn4kids_comprehension WHERE is_active=1";
$params = [];
if ($level != '') {
    $sql .= " AND level=?";
    $params[] = $level;
}
if ($category != '') {
    $sql .= " AND category=?";
    $params[] = $category;
}
if ($q != '') {
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
    default:
        $sql .= " ORDER BY id DESC";
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cats = $pdo->query("SELECT DISTINCT category FROM learn4kids_comprehension ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Compréhension de Lecture</title>
    <?= Menu::menuStaticScriptMenu(); ?>
    <?= Menu::menuStaticCSS(); ?>
    <style>
        body {
            margin: 0;
            font-family: Segoe UI, sans-serif;
            background: linear-gradient(135deg, #4f46e5, #7c3aed)
        }

        .container {
            max-width: 1300px;
            margin: auto;
            padding: 20px
        }

        .hero {
            text-align: center;
            color: #fff;
            padding: 40px 20px
        }

        .filters {
            background: #fff;
            padding: 20px;
            border-radius: 25px;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 15px
        }

        input,
        select {
            padding: 14px;
            border: 1px solid #ddd;
            border-radius: 12px
        }

        button {
            background: #4f46e5;
            color: #fff;
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
            background: #fff;
            border-radius: 25px;
            padding: 25px;
            text-decoration: none;
            color: #111;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .12)
        }

        .badge {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 999px;
            background: #eef2ff;
            color: #4338ca;
            font-weight: bold;
            margin-right: 5px
        }

        .start {
            display: inline-block;
            margin-top: 15px;
            background: #4f46e5;
            color: #fff;
            padding: 10px 16px;
            border-radius: 10px
        }

        @media(max-width:900px) {
            .filters {
                grid-template-columns: 1fr
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <?= Menu::menuStaticMenu(""); ?>
        <div class="hero">
            <h1>📖 Compréhension de Lecture</h1>
            <p>Lis et réponds aux questions.</p>
        </div>

        <form class="filters">
            <input type="text" name="q" placeholder="Rechercher..." value="<?= h($q) ?>">
            <select name="level" class='animated-select'>
                <option value="">Tous les niveaux</option>
                <?php foreach ($levelsManage as $c => $l) { ?>
                    <option value="<?= $c ?>" <?= $level == $c ? 'selected' : '' ?>><?= $l ?></option>
                <?php } ?>
            </select>
            <select name="category" class='animated-select'>
                <option value="">Toutes catégories</option>
                <?php foreach ($cats as $cat) { ?>
                    <option value="<?= h($cat) ?>" <?= $category == $cat ? 'selected' : '' ?>><?= h($cat) ?></option>
                <?php } ?>
            </select>
            <select name="order" class='animated-select'>
                <option value="recent">Plus récents</option>
                <option value="title_asc" <?= $order == 'title_asc' ? 'selected' : '' ?>>Titre A-Z</option>
                <option value="title_desc" <?= $order == 'title_desc' ? 'selected' : '' ?>>Titre Z-A</option>
            </select>
            <button class='input-submit-black-bottom'><strong>Filtrer</strong></button>
        </form>

        <div class="grid">
            <?php foreach ($list as $row) { ?>
                <a class="card" href="<?= $GLOBALS['LocalHome'] ?>work/id=<?= $row['id'] ?>/">
                    <div>
                        <span class="badge"><?= h($levelsManage[$row['level']] ?? $row['level']) ?></span>
                        <span class="badge"><?= h($row['category']) ?></span>
                    </div>
                    <h2><?= h($row['title']) ?></h2>
                    <p>Texte de lecture avec questions de compréhension.</p>
                    <div class="div-buttom-violet">Lire →</div>
                </a>
            <?php } ?>
        </div>
    </div>
</body>

</html>