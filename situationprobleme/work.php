<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
 }

require_once( __DIR__ . '/../core/setting.php');
require_once( __DIR__ . '/../core/dbcore.php');
require_once( __DIR__ . '/../core/menu.php');
require_once( __DIR__ . '/../core/dbcore.php');
require_once( __DIR__ . '/../core/session.php');

SessionHandle::checkingPrivilegeChild();
$codeCourse = $GLOBALS['CourseCode'];
$codeLesson = $_GET['codeLesson'] ?? '';
SessionHandle::start($codeCourse, $codeLesson);

$pdo = connectBD();

function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

$levelsManage = Settings::getLevelsInFrench();

function situationToHtml($xml)
{
    preg_match('/<text>(.*?)<\/text>/s', $xml, $m);

    return "
    <div class='story-box'>
        " . ($m[1] ?? '') . "
    </div>";
}

function questionsToHtml($xml)
{
    $html = '';

    preg_match_all('/<question>(.*?)<\/question>/s', $xml, $matches);

    foreach ($matches[1] as $item) {

        preg_match('/<number>(.*?)<\/number>/s', $item, $n);
        preg_match('/<text>(.*?)<\/text>/s', $item, $t);

        $html .= "
        <div class='question-item'>
            <span class='num'>" . ($n[1] ?? '') . "</span>
            <span>" . ($t[1] ?? '') . "</span>
        </div>";
    }

    return $html;
}

function reponsesToHtml($xml)
{
    $html = '';

    preg_match_all('/<reponse>(.*?)<\/reponse>/s', $xml, $matches);

    foreach ($matches[1] as $item) {

        preg_match('/<number>(.*?)<\/number>/s', $item, $n);
        preg_match('/<text>(.*?)<\/text>/s', $item, $t);

        $html .= "
        <div class='answer-item'>
            <span class='num'>" . ($n[1] ?? '') . "</span>
            <strong>" . ($t[1] ?? '') . "</strong>
        </div>";
    }

    return $html;
}

/***************************************************
 * DETAIL
 ***************************************************/
if (isset($_GET['id'])) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM learn4kids_situation_probleme
        WHERE id=?
        AND is_active=1
    ");

    $stmt->execute([(int)$_GET['id']]);

    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        die("Situation introuvable");
    }

    $sections = [

        'situation' => [
            'title' => '📖 Situation',
            'content' => situationToHtml($item['situation_probleme'])
        ],

        'questions' => [
            'title' => '❓ Questions',
            'content' => questionsToHtml($item['questions'])
        ],

        'reponses' => [
            'title' => '✅ Réponses',
            'content' => reponsesToHtml($item['reponses'])
        ]

    ];

    $current = $_GET['section'] ?? 'situation';

    if (!isset($sections[$current])) {
        $current = 'situation';
    }

?>
    <!DOCTYPE html>
    <html lang="fr">

    <head>

        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">

        <title><?= h($item['title']) ?></title>

        <?= Menu::menuStaticScriptMenu(); ?>
        <?php echo Menu::menuStaticCSS(); ?>
        <style>
            body {
                margin: 0;
                font-family: Segoe UI, Arial, sans-serif;
                background: linear-gradient(135deg, #4f46e5, #7c3aed);
            }

            .container {
                max-width: 1100px;
                margin: auto;
                padding: 20px;
            }

            .back {
                display: inline-block;
                padding: 12px 18px;
                background: white;
                border-radius: 12px;
                text-decoration: none;
                color: #111;
                margin-bottom: 15px;
            }

            .card {
                background: white;
                padding: 30px;
                border-radius: 25px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, .15);
            }

            .badge {
                display: inline-block;
                padding: 8px 15px;
                border-radius: 999px;
                background: #eef2ff;
                color: #4338ca;
                font-weight: bold;
                margin-right: 8px;
            }

            .tabs {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
                margin: 25px 0;
            }

            .tabs a {
                text-decoration: none;
                padding: 12px 18px;
                border-radius: 12px;
                background: #f1f5f9;
                color: #111;
            }

            .tabs a.active {
                background: black;
                color: white;
            }

            .story-box {
                background: #f8fafc;
                padding: 25px;
                border-radius: 20px;
                line-height: 1.8;
                font-size: 1.1rem;
            }

            .story-box p {
                margin: 0 0 15px;
            }

            .question-item,
            .answer-item {
                background: #f8fafc;
                padding: 15px;
                border-radius: 15px;
                margin-bottom: 12px;
                font-size: 1.05rem;
            }

            .num {
                display: inline-block;
                width: 55px;
                font-weight: bold;
                color: #4f46e5;
            }

            h1 {
                margin-top: 0;
            }
        </style>
    </head>

    <body>

        <div class="container">

            <?php
            echo Menu::menuStaticMenu("
<a href='" . $GLOBALS['LocalHome'] . "work/' class='back'>
← Retour
</a>
");
            ?>

            <div class="card">

                <h1><?= h($item['title']) ?></h1>

                <br>

                <span class="badge">
                    <?= h($levelsManage[$item['level']] ?? $item['level']) ?>
                </span>

                <span class="badge">
                    <?= h($item['category']) ?>
                </span>

                <div class="tabs">

                    <?php foreach ($sections as $k => $s) { ?>

                        <a
                            class="<?= $current == $k ? 'active' : '' ?>"
                            href="<?= $GLOBALS['LocalHome'] ?>work/id=<?= $item['id'] ?>/section=<?= $k ?>/">
                            <?= $s['title'] ?>
                        </a>

                    <?php } ?>

                </div>

                <?= $sections[$current]['content'] ?>

            </div>

        </div>

    </body>

    </html>
<?php
    exit;
}

/***************************************************
 * LISTE
 ***************************************************/
$level = $_GET['level'] ?? '';
$category = $_GET['category'] ?? '';
$q = trim($_GET['q'] ?? '');
$order = $_GET['order'] ?? 'recent';

$sql = "
SELECT *
FROM learn4kids_situation_probleme
WHERE is_active=1
";

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

    case 'old':
        $sql .= " ORDER BY level ASC";
        break;

    default:
        $sql .= " ORDER BY level DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cats = $pdo->query("
SELECT DISTINCT category
FROM learn4kids_situation_probleme
ORDER BY category
")->fetchAll(PDO::FETCH_COLUMN);

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>Situations-Problèmes</title>

    <?= Menu::menuStaticScriptMenu(); ?>
    <?php echo Menu::menuStaticCSS(); ?>

    <style>
        body {
            margin: 0;
            font-family: Segoe UI, Arial, sans-serif;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
        }

        .container {
            max-width: 1300px;
            margin: auto;
            padding: 20px;
        }

        .hero {
            text-align: center;
            color: white;
            padding: 40px 20px;
        }

        .hero h1 {
            font-size: 3rem;
            margin: 0;
        }

        .hero p {
            font-size: 1.2rem;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 25px;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .15);
        }

        input,
        select {
            padding: 14px;
            border-radius: 12px;
            border: 1px solid #ddd;
        }

        button {
            background: #4f46e5;
            color: white;
            border: none;
            padding: 14px 22px;
            border-radius: 12px;
            cursor: pointer;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 25px;
            text-decoration: none;
            color: #111;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .12);
            transition: .3s;
        }

        .card:hover {
            transform: translateY(-6px);
        }

        .badge {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 999px;
            background: #eef2ff;
            color: #4338ca;
            font-weight: bold;
            margin-right: 5px;
        }

        .desc {
            margin-top: 15px;
            color: #666;
        }

        .start {
            margin-top: 20px;
            display: inline-block;
            background: #4f46e5;
            color: white;
            padding: 12px 18px;
            border-radius: 12px;
        }

        @media(max-width:900px) {

            .filters {
                grid-template-columns: 1fr;
            }

            .hero h1 {
                font-size: 2rem;
            }

        }
    </style>
</head>

<body>

    <div class="container">

        <?= Menu::menuStaticMenu(""); ?>

        <div class="hero">

            <h1>📚 Situations-Problèmes</h1>

            <p>
                Résous des problèmes de la vie quotidienne
                et développe ton raisonnement mathématique.
            </p>

        </div>

        <form class="filters">

            <input
                type="text"
                name="q"
                placeholder="Rechercher..."
                value="<?= h($q) ?>">

            <select name="level" class='animated-select'>

                <option value="">Tous les niveaux</option>

                <?php foreach ($levelsManage as $code => $label) { ?>

                    <option
                        value="<?= $code ?>"
                        <?= $level == $code ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>

                <?php } ?>

            </select>

            <select name="category" class='animated-select'>

                <option value="">Toutes catégories</option>

                <?php foreach ($cats as $cat) { ?>

                    <option
                        value="<?= h($cat) ?>"
                        <?= $category == $cat ? 'selected' : '' ?>>
                        <?= h($cat) ?>
                    </option>

                <?php } ?>

            </select>

            <select name="order" class='animated-select'>

                <option value="recent">Plus récents</option>

                <option value="old" <?= $order == 'old' ? 'selected' : '' ?>>
                    Plus anciens
                </option>

                <option value="title_asc" <?= $order == 'title_asc' ? 'selected' : '' ?>>
                    Titre A-Z
                </option>

                <option value="title_desc" <?= $order == 'title_desc' ? 'selected' : '' ?>>
                    Titre Z-A
                </option>

            </select>

            <button class='input-submit-black-bottom'>
                Filtrer
            </button>

        </form>

        <div class="grid">

            <?php foreach ($list as $row) { ?>

                <a
                    class="card"
                    href="<?= $GLOBALS['LocalHome'] ?>work/id=<?= $row['id'] ?>/">

                    <div>

                        <span class="badge">
                            <?= h($levelsManage[$row['level']] ?? $row['level']) ?>
                        </span>

                        <span class="badge">
                            <?= h($row['category']) ?>
                        </span>

                    </div>

                    <h2><?= h($row['title']) ?></h2>

                    <div class="desc">
                        Lire la situation, répondre aux questions et vérifier les réponses.
                    </div>

                    <div class="div-buttom-violet">
                        Commencer →
                    </div>

                </a>

            <?php } ?>

        </div>

    </div>

</body>

</html>