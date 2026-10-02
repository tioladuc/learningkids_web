<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
 }
require_once( __DIR__ . '/../core/setting.php');
require_once( __DIR__ . '/../core/dbcore.php');
require_once( __DIR__ . '/../core/session.php');
require_once( __DIR__ . '/../core/menu.php');

SessionHandle::checkingPrivilegeChildAdmin();
$pdo = connectBD();

function h($s)
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

$table = "learn4kids_dictation";

/* LEVELS */
$levels = Settings::getLevelsInFrenchAutre();

$message = "";

/* DELETE */
if (isset($_GET["delete"])) {
    $stmt = $pdo->prepare("DELETE FROM $table WHERE id=?");
    $stmt->execute([(int)$_GET["delete"]]);
    header("Location: ". $GLOBALS['LocalHome'] ."manage/?msg=Deleted");
    exit;
}

/* SAVE (INSERT / UPDATE) */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $data = [
        "title" => trim($_POST["title"] ?? ""),
        "level" => trim($_POST["level"] ?? ""),
        "youtube_video_url" => trim($_POST["youtube_video_url"] ?? ""),
        "youtube_video_transcript" => $_POST["youtube_video_transcript"] ?? "",
        "dictation" => $_POST["dictation"] ?? "",
        "rules_tips" => $_POST["rules_tips"] ?? "",
        "exercices" => $_POST["exercices"] ?? "",
        "responses" => $_POST["responses"] ?? "",
        "active" => isset($_POST["active"]) ? 1 : 0
    ];

    if (!empty($_POST["id"])) {

        $data["id"] = (int)$_POST["id"];

        $sql = "UPDATE $table SET
            title=:title,
            level=:level,
            youtube_video_url=:youtube_video_url,
            youtube_video_transcript=:youtube_video_transcript,
            dictation=:dictation,
            rules_tips=:rules_tips,
            exercices=:exercices,
            responses=:responses,
            active=:active
            WHERE id=:id";

        $pdo->prepare($sql)->execute($data);

        header("Location: ". $GLOBALS['LocalHome'] ."manage/?msg=Updated");
        exit;
    } else {

        $sql = "INSERT INTO $table
        (title, level, youtube_video_url, youtube_video_transcript, dictation, rules_tips, exercices, responses, active)
        VALUES
        (:title, :level, :youtube_video_url, :youtube_video_transcript, :dictation, :rules_tips, :exercices, :responses, :active)";

        $pdo->prepare($sql)->execute($data);

        header("Location: ". $GLOBALS['LocalHome'] ."manage/?msg=Created");
        exit;
    }
}

/* EDIT */
$editData = null;
if (isset($_GET["edit"])) {
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id=?");
    $stmt->execute([(int)$_GET["edit"]]);
    $editData = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* VIEW */
$viewData = null;
if (isset($_GET["view"])) {
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id=?");
    $stmt->execute([(int)$_GET["view"]]);
    $viewData = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* SEARCH */
$where = [];
$params = [];

if (!empty($_GET["title"])) {
    $where[] = "title LIKE ?";
    $params[] = "%" . $_GET["title"] . "%";
}

if (!empty($_GET["level"])) {
    $where[] = "level = ?";
    $params[] = $_GET["level"];
}

$sql = "SELECT * FROM $table";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dictation Management</title>

    <?php echo Menu::menuStaticScriptMenu(); ?>
    <?php echo Menu::menuStaticCSS(); ?>
    <style>
        body {
            font-family: Arial;
            background: #f4f7fb;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: auto;
        }

        .card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        textarea {
            min-height: 140px;
        }

        button,
        .btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 10px 14px;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            display: inline-block;
        }

        .btn.edit {
            background: #f59e0b;
        }

        .btn.view {
            background: #10b981;
        }

        .btn.delete {
            background: #ef4444;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        @media(max-width:900px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }

        .box {
            background: #fafafa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            white-space: pre-wrap;
        }
    </style>
</head>

<body>

    <div class="container">

        <?php echo Menu::menuStaticMenu(""); ?>

        <h1>Dictation Management</h1>

        <?php if (isset($_GET["msg"])): ?>
            <div class="card" style="color:red; font-weight:bold;"><?= h($_GET["msg"]) ?></div>
        <?php endif; ?>

        <!-- SEARCH -->
        <?php if (!isset($_GET["add"]) && !$editData && !$viewData): ?>
            <div class="card">
                <form method="get">
                    <div class="grid">
                        <div>
                            <label>Title</label>
                            <input name="title" value="<?= h($_GET["title"] ?? "") ?>">
                        </div>
                        <div>
                            <label>Level</label>
                            <select name="level">
                                <option value="">All</option>
                                <?php foreach ($levels as $k => $v): ?>
                                    <option value="<?= $k ?>" <?= ($k == ($_GET["level"] ?? "")) ? "selected" : "" ?>>
                                        <?= $v ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button>Search</button>
                    <a class="btn" href="<?= $GLOBALS['LocalHome'] ?>manage/add=1/">Add New</a>
                </form>
            </div>
        <?php endif; ?>

        <!-- ADD / EDIT -->
        <?php if (isset($_GET["add"]) || $editData): ?>
            <div class="card">
                <h2><?= $editData ? "Edit Dictation" : "Add Dictation" ?></h2>

                <form method="post">
                    <input type="hidden" name="id" value="<?= h($editData["id"] ?? "") ?>">

                    <label>Title</label>
                    <input name="title" required value="<?= h($editData["title"] ?? "") ?>">

                    <label>Level</label>
                    <select name="level">
                        <?php foreach ($levels as $k => $v): ?>
                            <option value="<?= $k ?>" <?= ($k == ($editData["level"] ?? "")) ? "selected" : "" ?>>
                                <?= $v ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>YouTube URL</label>
                    <input name="youtube_video_url" value="<?= h($editData["youtube_video_url"] ?? "") ?>">

                    <label>Transcript</label>
                    <textarea name="youtube_video_transcript"><?= h($editData["youtube_video_transcript"] ?? "") ?></textarea>

                    <label>Dictation</label>
                    <textarea name="dictation"><?= h($editData["dictation"] ?? "") ?></textarea>

                    <label>Rules / Tips</label>
                    <textarea name="rules_tips"><?= h($editData["rules_tips"] ?? "") ?></textarea>

                    <label>Exercises</label>
                    <textarea name="exercices"><?= h($editData["exercices"] ?? "") ?></textarea>

                    <label>Responses</label>
                    <textarea name="responses"><?= h($editData["responses"] ?? "") ?></textarea>

                    <label>
                        <input type="checkbox" name="active" value="1" <?= !empty($editData["active"]) ? "checked" : "" ?>>
                        Active
                    </label>

                    <br><br>
                    <button>Save</button>
                    <a class="btn" href="<?= $GLOBALS['LocalHome'] ?>manage/">Back</a>
                </form>
            </div>
        <?php endif; ?>

        <!-- VIEW -->
        <?php if ($viewData): ?>
            <div class="card">
                <h2><?= h($viewData["title"]) ?></h2>

                <p><strong>Level:</strong> <?= h($viewData["level"]) ?></p>
                <p><strong>YouTube:</strong> <?= h($viewData["youtube_video_url"]) ?></p>

                <h3>Transcript</h3>
                <div class="box"><?= h($viewData["youtube_video_transcript"]) ?></div>

                <h3>Dictation</h3>
                <div class="box"><?= h($viewData["dictation"]) ?></div>

                <h3>Rules / Tips</h3>
                <div class="box"><?= h($viewData["rules_tips"]) ?></div>

                <h3>Exercises</h3>
                <div class="box"><?= h($viewData["exercices"]) ?></div>

                <h3>Responses</h3>
                <div class="box"><?= h($viewData["responses"]) ?></div>

                <br>
                <a class="btn" href="<?= $GLOBALS['LocalHome'] ?>manage/">Back</a>
            </div>
        <?php endif; ?>

        <!-- LIST -->
        <?php if (!isset($_GET["add"]) && !$editData && !$viewData): ?>
            <div class="card">
                <h2>List</h2>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Level</th>
                        <th>Active</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>

                    <?php foreach ($list as $r): ?>
                        <tr>
                            <td><?= $r["id"] ?></td>
                            <td><?= h($r["title"]) ?></td>
                            <td><?= h($levels[$r["level"]]) ?></td>
                            <td><?= $r["active"] ? "Yes" : "No" ?></td>
                            <td><?= h($r["created_on"]) ?></td>
                            <td>
                                <a class="btn view" href="<?= $GLOBALS['LocalHome'] ?>manage/view=<?= $r["id"] ?>/">View</a>
                                <a class="btn edit" href="<?= $GLOBALS['LocalHome'] ?>manage/edit=<?= $r["id"] ?>/">Edit</a>
                                <a class="btn delete" onclick="return confirm('Delete?')" href="<?= $GLOBALS['LocalHome'] ?>manage/delete=<?= $r["id"] ?>/">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>

    </div>

</body>

</html>