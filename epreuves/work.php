<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
 }
require_once( __DIR__ . '/../core/recorder.php');
require_once( __DIR__ . '/../core/setting.php');
require_once( __DIR__ . '/../core/dbcore.php');
require_once( __DIR__ . '/../core/session.php');
require_once( __DIR__ . '/../core/menu.php');

//$_GET['login'] = isset($_GET['login']) ? $_GET['login'] : Settings::$loginUnknown ;
//$_GET['codeCourse'] = isset($_GET['codeCourse']) ? $_GET['codeCourse'] : Settings::$codeCourseEpreuve ;

SessionHandle::checkingPrivilegeChild();
$codeCourse = $GLOBALS['CourseCode'];
$codeLesson = $_GET['codeLesson'] ?? '';
SessionHandle::start($codeCourse, $codeLesson);

$recorder = new Recorder();
$pdo = connectBD();
$currentTable = "learn4kids_epreuve";

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
function addclassToList($html)
{
    $html = str_replace("<ol>", "<ol class='animated-list'>", $html);
    $html = str_replace("<ul>", "<ol class='animated-list'>", $html);
    return $html;
}
function addclassToParagraph($html)
{
    $html = str_replace("<p>", "<p class='beautiful-p'>", $html);
    return $html;
}
function compilationModification($html)
{
    $html = addclassToList($html);
    $html = addclassToParagraph($html);
    return $html;
}

/* ===========================
   FILTERS
=========================== */
$selectedClasse = $_GET['classe'] ?? '';
$order = $_GET['order'] ?? 'created_on';

$allowedOrder = ['titre', 'classe', 'created_on'];
if (!in_array($order, $allowedOrder)) {
    $order = 'created_on';
}

/* ===========================
   GET CLASSES
=========================== */
$stmt = $pdo->query("
    SELECT DISTINCT classe
    FROM $currentTable
    WHERE classe IS NOT NULL AND classe <> ''
    ORDER BY classe
");
$classes = $stmt->fetchAll(PDO::FETCH_COLUMN);

/* ===========================
   VIEW SINGLE
=========================== */
$viewId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($viewId > 0) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM $currentTable
        WHERE id=?
    ");
    $stmt->execute([$viewId]);
    $epreuve = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$epreuve) {
        die("Epreuve not found");
    }

    $sections = [
        'dictee' => [
            'title' => 'Dictée',
            'content' => compilationModification($epreuve['dictee']) . $recorder->displayRecorderTag($codeCourse, $_GET['codeLesson'] . "dictation_", $_SESSION['current_id'])
        ],
        'comprehension' => [
            'title' => 'Compréhension',
            'content' => compilationModification($epreuve['comprehension'])
        ],
        'anglais' => [
            'title' => 'Anglais',
            'content' => (!empty($epreuve['anglais_youtube_lien'])
                ? '<p>'. $recorder->displayYoutubeVideo($epreuve['anglais_youtube_lien']) .'</p>'
                : '')
                . (compilationModification($epreuve['anglais_lecture']) ?? '')
                . ((compilationModification($epreuve['anglais_lecture'])==null || compilationModification($epreuve['anglais_lecture'])=="") ? "": $recorder->displayRecorderTag($codeCourse, $_GET['codeLesson'] . "english_", $_SESSION['current_id']))
                . (compilationModification($epreuve['anglais_exercice']) ?? '')
        ],
        'probleme_situation' => [
            'title' => 'Problème Situation',
            'content' => compilationModification($epreuve['probleme_situation'])
        ],
        'redaction' => [
            'title' => 'Rédaction',
            'content' => compilationModification($epreuve['redaction'])
        ]
    ];

    $keys = array_keys($sections);

    $currentSection = $_GET['section'] ?? $keys[0];
    if (!isset($sections[$currentSection])) {
        $currentSection = $keys[0];
    }

    $currentIndex = array_search($currentSection, $keys);

    $prev = $keys[max(0, $currentIndex - 1)];
    $next = $keys[min(count($keys) - 1, $currentIndex + 1)];

?>
    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= h($epreuve['titre']) ?></title>

        <?php $recorder->javascript(); ?>
        <?php echo Menu::menuStaticScriptMenu(); ?>
        <?php echo Menu::menuStaticCSS(); ?>
        <style>
            <?php echo $recorder->css(); ?>
            

            body {
                margin: 0;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                background: linear-gradient(135deg, #4f46e5, #7c3aed);
                color: #1e293b;
            }
            
            .container {
                max-width: 1100px;
                margin: auto;
                padding: 20px;
            }

            .topbar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 10px;
                flex-wrap: wrap;
                margin-bottom: 20px;
            }

            .back-btn {
                text-decoration: none;
                background: white;
                color: #334155;
                padding: 12px 18px;
                border-radius: 14px;
                box-shadow: 0 6px 18px rgba(0, 0, 0, .08);
                font-weight: 600;
            }

            .card {
                background: white;
                border-radius: 24px;
                padding: 28px;
                box-shadow: 0 10px 35px rgba(0, 0, 0, .08);
            }

            h1 {
                margin-top: 0;
                font-size: 2rem;
            }

            .meta {
                display: flex;
                gap: 12px;
                flex-wrap: wrap;
                margin-bottom: 20px;
            }

            .badge {
                background: #eef2ff;
                color: #4338ca;
                padding: 8px 14px;
                border-radius: 999px;
                font-size: .9rem;
                font-weight: 600;
            }

            .section-nav {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
                margin: 20px 0;
            }

            .section-link {
                text-decoration: none;
                padding: 10px 16px;
                border-radius: 12px;
                background: #f1f5f9;
                color: #334155;
                font-weight: 600;
            }

            .section-link.active {
                background: black;
                color: white;
            }

            .content {
                line-height: 1.8;
                font-size: 1.05rem;
            }

            .content article {
                max-width: 100%;
            }

            .content img {
                max-width: 100%;
                border-radius: 12px;
            }

            .content iframe {
                max-width: 100%;
            }

            .bottom-nav {
                display: flex;
                justify-content: space-between;
                margin-top: 30px;
                gap: 10px;
            }

            .arrow {
                flex: 1;
                text-align: center;
                text-decoration: none;
                padding: 14px;
                border-radius: 14px;
                font-weight: 700;
                background: #4f46e5;
                color: white;
            }

            @media(max-width:700px) {
                .card {
                    padding: 18px;
                }

                h1 {
                    font-size: 1.4rem;
                }

                .arrow {
                    font-size: .95rem;
                }
            }

            /* 1. Base List Styling */
            .animated-list {
                list-style: none;
                /* Hide default numbers */
                counter-reset: list-counter;
                /* Initialize counter */
                padding: 0;
                /*max-width: 400px;*/
            }

            /* 2. List Item Animation */
            .animated-list li {
                counter-increment: list-counter;
                /* Increment counter for each item */
                margin-bottom: 15px;
                padding: 15px 20px;
                background: #f9f9f9;
                border-radius: 8px;
                display: flex;
                align-items: center;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);

                /* Animation properties */
                opacity: 0;
                transform: translateY(20px);
                animation: fadeInUp 0.5s ease forwards;
            }

            /* 3. Custom Numbering with Pseudo-elements */
            .animated-list li::before {
                content: counter(list-counter);
                /* Show current number */
                margin-right: 15px;
                background: #6366f1;
                color: white;
                font-weight: bold;
                width: 30px;
                height: 30px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.9em;
            }

            .animated-list li:hover {
                background-color: #0077b5;
                /* LinkedIn color example */
                transform: translateY(-3px);
            }

            /* 4. Staggered Delay Effect */
            /* Each item starts slightly after the previous one */
            .animated-list li:nth-child(1) {
                animation-delay: 0.4s;
            }

            .animated-list li:nth-child(2) {
                animation-delay: 0.8s;
            }

            .animated-list li:nth-child(3) {
                animation-delay: 1.2s;
            }

            .animated-list li:nth-child(4) {
                animation-delay: 1.6s;
            }

            .animated-list li:nth-child(5) {
                animation-delay: 2.0s;
            }

            .animated-list li:nth-child(6) {
                animation-delay: 2.4s;
            }

            .animated-list li:nth-child(7) {
                animation-delay: 2.8s;
            }

            .animated-list li:nth-child(8) {
                animation-delay: 3.2s;
            }

            /* 5. Keyframes for Entrance */
            @keyframes fadeInUp {
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            /***********************************************************/
            .shimmer-p {
                font-weight: 600;
                background: linear-gradient(to right, #4d4d4d 0, white 10%, #4d4d4d 20%);
                background-position: 0;
                background-clip: text;
                -webkit-background-clip: text;
                /* Clips background to text shape */
                -webkit-text-fill-color: transparent;
                animation: shimmer 3s infinite linear;
                background-size: 200% auto;
            }

            @keyframes shimmer {
                to {
                    background-position: 200% center;
                }
            }

            /******************************************************** */
            .beautiful-p {
                font-family: 'Georgia', serif;
                font-size: 1.2rem;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                opacity: 0;
                /* Starts invisible */
                animation: fadeSlideUp 1s ease-out forwards;
            }

            @keyframes fadeSlideUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        </style>

    </head>

    <body>

        <div class="container">

            <?php 
                $mnuStatic = "<div class=\"topbar\"><a class=\"back-btn\" href=\"" . $GLOBALS['LocalHome'] . "work/codeLesson=" . $epreuve['code'] . "/\">← Retour liste</a></div>";
                echo Menu::menuStaticMenu($mnuStatic);
            ?>

            <div class="card">

                <h1><?= h($epreuve['titre']) ?></h1>

                <div class="meta">
                    <div class="badge"><?= h($epreuve['code']) ?></div>
                    <div class="badge"><?= h($epreuve['classe']) ?></div>
                    <div class="badge"><?= h($epreuve['created_on']) ?></div>
                </div>

                <div class="section-nav">
                    <?php foreach ($sections as $key => $section): ?>
                        <a class="section-link <?= $key === $currentSection ? 'active' : '' ?>" 
                           href='<?= $GLOBALS['LocalHome'] ?>work/id=<?= $viewId ?>/section=<?= $key ?>/codeCourse=<?= $codeCourse ?>/codeLesson=<?= $epreuve['code'] ?>/' >
                            <?= h($section['title']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="content">
                    <?= $sections[$currentSection]['content'] ?>
                </div>

                <div class="bottom-nav">
                    <a class="arrow" href="<?= $GLOBALS['LocalHome'] ?>work/id=<?= $viewId ?>/section=<?= $prev ?>/codeCourse=<?= $codeCourse ?>/codeLesson=<?= $epreuve['code'] ?>/">← Précédent</a>
                    <a class="arrow" href="<?= $GLOBALS['LocalHome'] ?>work/id=<?= $viewId ?>/section=<?= $next ?>/codeCourse=<?= $codeCourse ?>/codeLesson=<?= $epreuve['code'] ?>/">Suivant →</a>
                </div>

            </div>
        </div>

    </body>

    </html>
<?php
    exit;
}

/* ===========================
   LIST
=========================== */
$sql = "SELECT id, code, titre, classe, created_on FROM $currentTable";
$params = [];

if ($selectedClasse !== '') {
    $sql .= " WHERE classe=?";
    $params[] = $selectedClasse;
}

$sql .= " ORDER BY $order";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Epreuves</title>
    <?php echo Menu::menuStaticCSS(); ?>
    <style>
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #1e293b;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            padding: 20px;
        }

        h1 {
            text-align: center;
            font-size: 2.4rem;
            margin-bottom: 25px;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .07);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        select {
            padding: 14px;
            border-radius: 14px;
            border: 1px solid #cbd5e1;
            min-width: 200px;
            font-size: 1rem;
        }

        button {
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 14px;
            padding: 14px 22px;
            font-weight: 700;
            cursor: pointer;
        }

        .list {
            display: grid;
            gap: 16px;
        }

        .row {
            display: grid;
            grid-template-columns: 120px 1fr 180px 180px;
            gap: 15px;
            background: white;
            padding: 20px;
            border-radius: 18px;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 8px 22px rgba(0, 0, 0, .06);
            transition: .2s;
            align-items: center;
        }

        .row:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 30px rgba(0, 0, 0, .10);
        }

        .code {
            font-weight: 800;
            color: #4338ca;
        }

        .title {
            font-weight: 700;
            font-size: 1.05rem;
        }

        .small {
            color: #64748b;
        }

        @media(max-width:850px) {
            .row {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <?php echo Menu::menuStaticScriptMenu(); ?>
</head>

<body>

    <div class="container">

        <h1>📚 Liste des Épreuves</h1>
        <?php 
                echo Menu::menuStaticMenu("");
        ?>
        <form class="filters" method="get">
            <select name="classe" class='animated-select'>
                <option value="">Toutes les classes</option>
                <?php foreach ($classes as $classe): ?>
                    <option value="<?= h($classe) ?>"
                        <?= $selectedClasse === $classe ? 'selected' : '' ?>>
                        <?= h($classe) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="order" class='animated-select'>
                <option value="titre" <?= $order === 'titre' ? 'selected' : '' ?>>Titre</option>
                <option value="classe" <?= $order === 'classe' ? 'selected' : '' ?>>Classe</option>
                <option value="created_on" <?= $order === 'created_on' ? 'selected' : '' ?>>Date</option>
            </select>

            <button type="submit" class='input-submit-black-bottom'><strong>Filter</strong></button>
        </form>

        <div class="list">
            <?php foreach ($rows as $row): ?>
            <div class="grid">
                <a class="row card" href="<?= $GLOBALS['LocalHome'] ?>work/id=<?= $row['id'] ?>/codeLesson=<?= $row['code'] ?>/">
                    <div class="code"><?= h($row['code']) ?></div>
                    <div class="title"><?= h($row['titre']) ?></div>
                    <div class="small"><?= h($row['classe']) ?></div>
                    <div class="small"><?= h($row['created_on']) ?></div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

    </div>

</body>

</html>