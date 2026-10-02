<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
 }
require_once("core/recorder.php");
require_once("core/setting.php");
require_once("core/dbcore.php");
require_once("core/session.php");
require_once("core/menu.php");

SessionHandle::checkingPrivilegeChild();
$codeCourse = $GLOBALS['CourseCode'];
$codeLesson = $_GET['codeLesson'] ?? '';
SessionHandle::start($codeCourse, $codeLesson);

$levelsManage = Settings::getLevelsInEnglish();

$pdo = connectBD();
$recorder = new Recorder();

function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

function beautifyHtml($html)
{
    $html = str_replace("<ul>", "<ul class='beautiful-list'>", $html);
    $html = str_replace("<ol>", "<ol class='beautiful-list'>", $html);
    $html = str_replace("<p>", "<p class='beautiful-p'>", $html);
    return $html;
}

$table = "learn4kids_dictation";

/* DETAIL */
if (isset($_GET['id'])) {

    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id=? AND active=1");
    $stmt->execute([(int)$_GET['id']]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        die("Material not found");
    }

    $url = $item['youtube_video_url'] ?? '';
    parse_str(parse_url($url, PHP_URL_QUERY), $query);
    $videoId = $query['v'] ?? '';

    $sections = [
        'subject' => [
            'title' => 'Youtube Video',
            'content' => '<div class="subject-box"><h2>' . h($item['title']) . '</h2>
                          <iframe width=\"420\" height=\"315\"                            
                            src="https://www.youtube.com/embed/'. $videoId .'"
                            frameborder="0"
                            allowfullscreen>
                          </iframe>
                          <p>'. beautifyHtml($item['youtube_video_transcript']) .'</p></div>'
        ],
        'writeDictation' => [
            'title' => 'Write down Dictation',
            'content' => '<div class="subject-box">' . "<div style='margin-top:20px'>" . $recorder->displayRecorderTag($codeCourse ?? 'Dictation', 'dictation_' . $item['id'], $_SESSION['current_id']) . 
                        "<button id='dictee-content' class='blinking-button'>Play the audio to make your dictation. If not yet recorded, goto the next tab to record the audio</button></div>"
        ],
        'record' => [
            'title' => 'Record Dictation',
            'content' => '<div class="subject-box">' . "<div style='margin-top:20px'>" . $recorder->displayRecorderTag($codeCourse ?? 'Dictation', 'dictation_' . $item['id'], $_SESSION['current_id']) . 
                        "<p id='dictee-content'>". beautifyHtml($item['dictation']) ."</p></div>"
        ],
        'rulestips' => [
            'title' => 'Rules & Tips',
            'content' => beautifyHtml($item['rules_tips']) 
        ],


        'exercices' => [
            'title' => 'Exercices',
            'content' => beautifyHtml($item['exercices'])
        ],
        'responses' => [
            'title' => 'Reponses',
            'content' => beautifyHtml($item['responses']) 
        ]
    ];

    $current = $_GET['section'] ?? 'subject';
    if (!isset($sections[$current])) $current = 'subject';
?>
    <!DOCTYPE html>
    <html lang="en">

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
            <?= Menu::menuStaticMenu("<a href='" . $GLOBALS['LocalHome'] . "work/" . "'>← Retour</a>"); ?>

            <div class="card">
                <h1><?= h($item['title']) ?></h1>
                <div class="badge"><?= h($levelsManage[$item['level']]) ?></div>

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

$level = $_GET['level'] ?? '';
$q = trim($_GET['q'] ?? '');
$order = $_GET['order'] ?? 'recent';

$sql = "SELECT * FROM $table WHERE active=1";
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
    <title>English Learning</title>
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

        .start {
            margin-top: 20px;
            display: inline-block;
            background: #4f46e5;
            color: white;
            padding: 12px 18px;
            border-radius: 12px
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
</head>

<body>
    <div class="container">
        <?php
        echo Menu::menuStaticMenu("");
        ?>
        <div class="hero">
           <h1>🎤 English Learning</h1>
           <p>Learn English Writing, Speaking, Listening and Reading, the fun way </p>
        </div>

        <form class="filters">
            <input name="q" placeholder="Search for a topic..." value="<?= h($q) ?>">

            <select name="level" class='animated-select'>
                <option value="">All Levels</option>
                <?php foreach ($levels as $l) { ?>
                    <option value="<?= $l ?>" <?= ($level == $l ? 'selected' : '') ?>><?= $levelsManage[$l] ?></option>
                <?php } ?>
            </select>

            <select name="order" class='animated-select'>
                <option value="recent">Most Recent</option>
                <option value="old" <?= ($order == 'old' ? 'selected' : '') ?>>Oldest First</option>
                <option value="title_asc" <?= ($order == 'title_asc' ? 'selected' : '') ?>>Title A-Z</option>
                <option value="title_desc" <?= ($order == 'title_desc' ? 'selected' : '') ?>>Title Z-A</option>
            </select>

            <button class='input-submit-black-bottom'><strong>Filtrer</strong></button>
        </form>

        <div class="grid">
            <?php foreach ($list as $row) { ?>
                <a class="card" href="<?= $GLOBALS['LocalHome'] ?>work/id=<?= $row['id'] ?>/<?php echo (!isset($_GET['login']) || $_GET['login'] === '') ? "" :  (("/login=" . $_GET['login']) ."/") ?>">
                    <div class="badge"><?= h($levelsManage[$row['level']]) ?></div>
                    <h2><?= h($row['title']) ?></h2>
                    <div class="desc">Go and learn this course to improve your skills.</div>
                    <div class="date"><?= date('d/m/Y H:i:s', strtotime($row['created_on'])) ?></div>
                    <div class="div-buttom-violet">Start →</div>
                </a>
            <?php } ?>
        </div>

    </div>
</body>

</html>