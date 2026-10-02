<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
 }
require_once("core/setting.php");
require_once("core/dbcore.php");
require_once("core/session.php");
require_once("core/menu.php");

SessionHandle::checkingPrivilegeChildAdmin();
$pdo = connectBD();
$table = "learn4kids_dictee";
$levels =Settings::getLevelsInFrenchAutre();
function h($s)
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
if (isset($_GET["delete"])) {
    $pdo->prepare("DELETE FROM $table WHERE id=?")->execute([(int)$_GET["delete"]]);
    header("Location: " . $GLOBALS['LocalHome'] . "manage/?msg=Deleted");
    exit;
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['search'])) {
    $d = ["title" => trim($_POST["title"] ?? ""), "level" => $_POST["level"] ?? "", "dictee" => $_POST["dictee"] ?? "", "astuces" => $_POST["astuces"] ?? "", "active" => isset($_POST["active"]) ? 1 : 0];
    if (!empty($_POST["id"])) {
        $d["id"] = (int)$_POST["id"];
        $pdo->prepare("UPDATE $table SET title=:title,level=:level,dictee=:dictee,astuces=:astuces,active=:active WHERE id=:id")->execute($d);
    } else {
        $pdo->prepare("INSERT INTO $table(title,level,dictee,astuces,active) VALUES(:title,:level,:dictee,:astuces,:active)")->execute($d);
    }
    header("Location: " . $GLOBALS['LocalHome'] . "manage/?msg=Saved");
    exit;
}
$edit = null;
$view = null;
if (isset($_GET["edit"])) {
    $s = $pdo->prepare("SELECT * FROM $table WHERE id=?");
    $s->execute([(int)$_GET["edit"]]);
    $edit = $s->fetch(PDO::FETCH_ASSOC);
}
if (isset($_GET["view"])) {
    $s = $pdo->prepare("SELECT * FROM $table WHERE id=?");
    $s->execute([(int)$_GET["view"]]);
    $view = $s->fetch(PDO::FETCH_ASSOC);
}
$sql = "SELECT * FROM $table WHERE level LIKE '%". ($_POST['searchLevel'] ?? '') ."%' AND title LIKE '%". ($_POST['searchText'] ?? '') ."%' ORDER BY id DESC";
$list = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Gestion Dictées</title><?= Menu::menuStaticScriptMenu() ?>
    <?php echo Menu::menuStaticCSS(); ?>
    <style>
        body {
            font-family: Arial;
            background: #f4f7fb
        }

        .c {
            max-width: 1300px;
            margin: auto
        }

        .card {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 10px;
            margin: 6px 0
        }

        textarea {
            min-height: 180px
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
            border-collapse: collapse
        }

        td,
        th {
            border-bottom: 1px solid #ddd;
            padding: 8px
        }
    </style>
</head>

<body>
    <div class="c"><?= Menu::menuStaticMenu("") ?><h1>Gestion des Dictées</h1>
        <?php if (isset($_GET["msg"])) echo "<div class='card' style='color:red; font-weight:bold;'>" . h($_GET["msg"]) . "</div>"; ?>
        <?php if (isset($_GET["add"]) || $edit) { ?><div class="card">
                <form method="post">
                    <input type="hidden" name="id" value="<?= h($edit["id"] ?? "") ?>">
                    <label>Titre</label><input name="title" required value="<?= h($edit["title"] ?? "") ?>">
                    <label>Niveau</label><select name="level"><?php foreach ($levels as $k => $v) { ?><option value="<?= $k ?>" <?= ($edit["level"] ?? "") == $k ? "selected" : "" ?>><?= $v ?></option><?php } ?></select>
                    <label>Dictée (XML)</label><textarea name="dictee"><?= h($edit["dictee"] ?? "") ?></textarea>
                    <label>Astuces (XML)</label><textarea name="astuces"><?= h($edit["astuces"] ?? "") ?></textarea>
                    <label><input type="checkbox" name="active" value="1" <?= !isset($edit["active"]) || !empty($edit["active"]) ? "checked" : "" ?>> Actif</label><br><br>
                    <button>Enregistrer</button> <a class="btn" href="<?= $GLOBALS['LocalHome'] ?>manage/">Retour</a>
                </form>
            </div>
        <?php } elseif ($view) { ?><div class="card">
                <h2><?= h($view["title"]) ?></h2>
                <p><b>Niveau:</b> <?= h($levels[$view["level"]] ?? $view["level"]) ?></p>
                <h3>Dictée</h3>
                <pre><?= h($view["dictee"]) ?></pre>
                <h3>Astuces</h3>
                <pre><?= h($view["astuces"]) ?></pre><a class="btn" href="<?= $GLOBALS['LocalHome'] ?>manage/">Retour</a>
            </div>
        <?php } else { ?><div class="card"><a class="btn" href="<?= $GLOBALS['LocalHome'] ?>manage/add=1/">Ajouter</a></div>
            <div class="card">
                <form method="post" action="<?= $GLOBALS['LocalHome'] ?>manage/">
                    <table>
                        <tr>
                            <td><input type="text" class="text" name="searchText" placeholder="Rechercher ..." value="<?= h($_POST['searchText'] ?? '') ?>"></td>
                            <td><select name="searchLevel">
                                    <option value="">Tous les niveaux</option>
                                    <?php foreach ($levels as $k => $v) { ?><option value="<?= $k ?>" <?= (($_POST['searchLevel'] ?? '') == $k) ? 'selected' : '' ?>><?= $v ?></option><?php } ?></select></td>
                            <td><button type="submit" name="search">Rechercher</button></td>
                        </tr>
                    </table>
                </form>
                <table>
                    <tr>
                        <th align="left">ID</th>
                        <th align="left">Titre</th>
                        <th align="left">Niveau</th>
                        <th align="left">Actif</th>
                        <th align="left">Date</th>
                        <th align="left">Actions</th>
                    </tr><?php foreach ($list as $r) { ?><tr>
                            <td><?= $r["id"] ?></td>
                            <td><?= h($r["title"]) ?></td>
                            <td><?= h($levels[$r["level"]] ?? $r["level"]) ?></td>
                            <td><?= $r["active"] ? "Oui" : "Non" ?></td>
                            <td><?= h($r["created_on"]) ?></td>
                            <td>
                                <a class="btn view" href="<?= $GLOBALS['LocalHome'] ?>manage/view=<?= $r["id"] ?>/">Voir</a> 
                                <a class="btn edit" href="<?= $GLOBALS['LocalHome'] ?>manage/edit=<?= $r["id"] ?>/">Modifier</a> 
                                <a class="btn delete" onclick="return confirm('Supprimer ?')" href="<?= $GLOBALS['LocalHome'] ?>manage/delete=<?= $r["id"] ?>/">Supprimer</a>
                            </td>
                        </tr><?php } ?>
                </table>
            </div><?php } ?>
    </div>
</body>

</html>