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

$table = "learn4kids_expose_primaire";

$levels = Settings::getLevelsInFrenchAutre();

$message = "";

/* DELETE */
if (isset($_GET["delete"])) {
    $stmt = $pdo->prepare("DELETE FROM $table WHERE id=?");
    $stmt->execute([(int)$_GET["delete"]]);
    header("Location: " . $GLOBALS['LocalHome'] . "manage/?msg=Deleted");
    exit;
}

/* SAVE */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = [
        "title" => trim($_POST["title"] ?? ""),
        "level" => trim($_POST["level"] ?? ""),
        "description" => $_POST["description"] ?? "",
        "solution" => $_POST["solution"] ?? "",
        "active" => isset($_POST["active"]) ? 1 : 0
    ];

    if (!empty($_POST["id"])) {
        $data["id"] = (int)$_POST["id"];
        $sql = "UPDATE $table SET
            title=:title,
            level=:level,
            description=:description,
            solution=:solution,
            active=:active
            WHERE id=:id";
        $pdo->prepare($sql)->execute($data);
        header("Location: " . $GLOBALS['LocalHome'] . "manage/?msg=Updated");
        exit;
    } else {
        $sql = "INSERT INTO $table
        (title,level,description,solution,active)
        VALUES
        (:title,:level,:description,:solution,:active)";
        $pdo->prepare($sql)->execute($data);
        header("Location: " . $GLOBALS['LocalHome'] . "manage/?msg=Created");
        exit;
    }
}

$editData = null;
if (isset($_GET["edit"])) {
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id=?");
    $stmt->execute([(int)$_GET["edit"]]);
    $editData = $stmt->fetch(PDO::FETCH_ASSOC);
}

$viewData = null;
if (isset($_GET["view"])) {
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id=?");
    $stmt->execute([(int)$_GET["view"]]);
    $viewData = $stmt->fetch(PDO::FETCH_ASSOC);
}

$where = [];
$params = [];

if (!empty($_GET["title"])) {
    $where[] = "title LIKE ?";
    $params[] = "%" . $_GET["title"] . "%";
}

if (!empty($_GET["level"])) {
    $where[] = "level=?";
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
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Gestion des Exposés</title>
    <?php echo Menu::menuStaticScriptMenu(); ?>
    <?php echo Menu::menuStaticCSS(); ?>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            margin: 0;
            padding: 20px
        }

        .container {
            max-width: 1400px;
            margin: auto
        }

        .card {
            background: #fff;
            padding: 20px;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .08);
            margin-bottom: 25px
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            margin-bottom: 12px
        }

        textarea {
            min-height: 180px
        }

        button,
        .btn {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 10px 14px;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer
        }

        .grid2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px
        }

        table {
            width: 100%;
            border-collapse: collapse
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            text-align: left
        }

        .view {
            background: #10b981
        }

        .edit {
            background: #f59e0b
        }

        .delete {
            background: #ef4444
        }

        .html-box {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 10px;
            background: #fafafa
        }

        @media(max-width:900px) {
            .grid2 {
                grid-template-columns: 1fr
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <?php
            echo Menu::menuStaticMenu("");
        ?>
        <h1>Gestion des Exposés du Primaire</h1>

        <?php if (isset($_GET["msg"])): ?>
            <div class="card"><?= h($_GET["msg"]) ?></div>
        <?php endif; ?>

        <?php if (!isset($_GET["add"]) && !$editData && !$viewData): ?>
            <div class="card">
                <form>
                    <div class="grid2">
                        <div>
                            <label>Titre</label>
                            <input name="title" value="<?= h($_GET["title"] ?? "") ?>">
                        </div>
                        <div>
                            <label>Niveau</label>
                            <select name="level">
                                <option value="">Tous</option>
                                <?php foreach ($levels as $k => $v): ?>
                                    <option value="<?= $k ?>" <?= ($k == ($_GET["level"] ?? "") ? 'selected' : '') ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button>Rechercher</button>
                    <a class="btn" href="manage.php?add=1">Ajouter</a>
                </form>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET["add"]) || $editData): ?>
            <div class="card">
                <h2><?= $editData ? "Modifier" : "Ajouter" ?></h2>
                <form method="post">
                    <input type="hidden" name="id" value="<?= h($editData["id"] ?? "") ?>">

                    <label>Titre</label>
                    <input name="title" value="<?= h($editData["title"] ?? "") ?>" required>

                    <label>Niveau</label>
                    <select name="level" required>
                        <?php foreach ($levels as $k => $v): ?>
                            <option value="<?= $k ?>" <?= ($k == ($editData["level"] ?? "") ? 'selected' : '') ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label>Description (HTML)</label>
                    <textarea name="description"><?= h($editData["description"] ?? "") ?></textarea>

                    <label>Solution (HTML)</label>
                    <textarea name="solution"><?= h($editData["solution"] ?? "") ?></textarea>

                    <label><input type="checkbox" name="active" value="1" <?= !empty($editData["active"]) ? "checked" : "" ?>> Actif</label>
                    <br><br>
                    <button>Enregistrer</button>
                    <a class="btn" href="<?= $GLOBALS['LocalHome'] ?>manage/">Retour</a>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($viewData): ?>
            <div class="card">
                <h2><?= h($viewData["title"]) ?></h2>
                <p><strong>Niveau:</strong> <?= h($levels[$viewData["level"]] ?? $viewData["level"]) ?></p>

                <h3>Description</h3>
                <div class="html-box"><?= $viewData["description"] ?></div>

                <h3>Proposition de solution</h3>
                <div class="html-box"><?= $viewData["solution"] ?></div>

                <br>
                <a class="btn" href="<?= $GLOBALS['LocalHome'] ?>manage/">Retour</a>
            </div>
        <?php endif; ?>

        <?php if (!isset($_GET["add"]) && !$editData && !$viewData): ?>
            <div class="card">
                <h2>Liste des exposés</h2>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Niveau</th>
                        <th>Actif</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>

                    <?php foreach ($list as $row): ?>
                        <tr>
                            <td><?= $row["id"] ?></td>
                            <td><?= h($row["title"]) ?></td>
                            <td><?= h($levels[$row["level"]] ?? $row["level"]) ?></td>
                            <td><?= $row["active"] ? "Oui" : "Non" ?></td>
                            <td><?= h($row["created_on"]) ?></td>
                            <td>
                                <a class="btn view" href="<?= $GLOBALS['LocalHome'] ?>manage/view=<?= $row["id"] ?>/">Voir</a>
                                <a class="btn edit" href="<?= $GLOBALS['LocalHome'] ?>manage/edit=<?= $row["id"] ?>/">Modifier</a>
                                <a class="btn delete" onclick="return confirm('Supprimer ?')" href="<?= $GLOBALS['LocalHome'] ?>manage/delete=<?= $row["id"] ?>/">Supprimer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                </table>
            </div>
        <?php endif; ?>

    </div>
</body>

</html>