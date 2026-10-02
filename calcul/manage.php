<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
 }
require_once(__DIR__ . "/../core/setting.php");
require_once(__DIR__ . "/../core/dbcore.php");
require_once(__DIR__ . "/../core/session.php");
require_once(__DIR__ . "/../core/menu.php");

SessionHandle::checkingPrivilegeChildAdmin();
$pdo = connectBD();

function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

$table = "learn4kids_calcul";

$message = "";

/* ==========================
   DELETE
========================== */

if (isset($_GET['delete'])) {

    $stmt = $pdo->prepare("DELETE FROM $table WHERE id=?");
    $stmt->execute([(int)$_GET['delete']]);

    header("Location: ". $GLOBALS['LocalHome'] ."/manage/?msg=Deleted");
    exit;
}

/* ==========================
   SAVE
========================== */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $data = [
        'title'      => trim($_POST['title']),
        'level'      => trim($_POST['level']),
        'category'   => trim($_POST['category']),
        'calculs'    => $_POST['calculs'],
        'reponses'   => $_POST['reponses'],
        'is_active'  => isset($_POST['is_active']) ? 1 : 0
    ];

    if (!empty($_POST['id'])) {

        $data['id'] = (int)$_POST['id'];

        $sql = "
        UPDATE $table SET
            title=:title,
            level=:level,
            category=:category,
            calculs=:calculs,
            reponses=:reponses,
            is_active=:is_active
        WHERE id=:id";

        $pdo->prepare($sql)->execute($data);

        header("Location: ". $GLOBALS['LocalHome'] ."/manage/?msg=Updated");
        exit;
    } else {

        $sql = "
        INSERT INTO $table
        (
            title,
            level,
            category,
            calculs,
            reponses,
            is_active
        )
        VALUES
        (
            :title,
            :level,
            :category,
            :calculs,
            :reponses,
            :is_active
        )";

        $pdo->prepare($sql)->execute($data);

        header("Location: ". $GLOBALS['LocalHome'] ."/manage/?msg=Created");
        exit;
    }
}

/* ==========================
   EDIT
========================== */

$editData = null;

if (isset($_GET['edit'])) {

    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);

    $editData = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ==========================
   VIEW
========================== */

$viewData = null;

if (isset($_GET['view'])) {

    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id=?");
    $stmt->execute([(int)$_GET['view']]);

    $viewData = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ==========================
   FILTER
========================== */

$where = [];
$params = [];

if (!empty($_GET['title'])) {
    $where[] = "title LIKE ?";
    $params[] = "%" . $_GET['title'] . "%";
}

if (!empty($_GET['level'])) {
    $where[] = "level = ?";
    $params[] = $_GET['level'];
}

if (!empty($_GET['category'])) {
    $where[] = "category = ?";
    $params[] = $_GET['category'];
}

$sql = "SELECT * FROM $table";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$levels = Settings::getLevelsInFrench();

$categories = [
    'Addition',
    'Soustraction',
    'Multiplication',
    'Division',
    'Fractions',
    'Problèmes'
];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>Gestion Exercices Calcul</title>
    <?php echo Menu::menuStaticScriptMenu(); ?>

    <?php echo Menu::menuStaticCSS(); ?>
    <style>
        body {
            margin: 0;
            background: #f4f7fb;
            font-family: Segoe UI, Tahoma, sans-serif;
        }

        .container {
            max-width: 1400px;
            margin: auto;
            padding: 20px;
        }

        h1 {
            margin-top: 0;
            color: #1e293b;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 25px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, .08);
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
        }

        input,
        select,
        textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 10px;
        }

        textarea {
            min-height: 260px;
            font-family: Consolas, monospace;
        }

        .btn {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            color: white;
            border: none;
            cursor: pointer;
        }

        .primary {
            background: #2563eb;
        }

        .success {
            background: #10b981;
        }

        .warning {
            background: #f59e0b;
        }

        .danger {
            background: #ef4444;
        }

        .secondary {
            background: #64748b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8fafc;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .xml-box {
            background: #fafafa;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            overflow: auto;
            white-space: pre-wrap;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 30px;
            color: white;
            font-size: 12px;
        }

        .active {
            background: black;
            color: #fff;
        }

        .inactive {
            background: #ef4444;
        }

        @media(max-width:900px) {

            .grid {
                grid-template-columns: 1fr;
            }

            table {
                display: block;
                overflow: auto;
            }
        }
    </style>

</head>

<body>

    <div class="container">
        <?php
            echo Menu::menuStaticMenu("");
        ?>
        <h1>📚 Gestion des Exercices de Calcul</h1>

        <?php if (isset($_GET['msg'])): ?>
            <div class="card" style="color:red; font-weight:bold;">
                <?= h($_GET['msg']) ?>
            </div>
        <?php endif; ?>

        <?php if (!$editData && !$viewData && !isset($_GET['add'])): ?>

            <div class="card">

                <form>

                    <div class="grid">

                        <div>
                            <label>Titre</label>
                            <input type="text" name="title"
                                value="<?= h($_GET['title'] ?? '') ?>">
                        </div>

                        <div>
                            <label>Niveau</label>
                            <select name="level">
                                <option value="">Tous</option>

                                <?php foreach ($levels as $k=>$v): ?>
                                    <option value="<?= $k ?>"
                                        <?= ($k == ($_GET['level'] ?? '')) ? 'selected' : '' ?>>
                                        <?= $v ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div>
                            <label>Catégorie</label>
                            <select name="category">
                                <option value="">Toutes</option>

                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c ?>"
                                        <?= ($c == ($_GET['category'] ?? '')) ? 'selected' : '' ?>>
                                        <?= $c ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                    </div>

                    <br>

                    <button class="btn primary">🔍 Rechercher</button>

                    <a class="btn success" href="<?= $GLOBALS['LocalHome'] ?>manage/add=1/">
                        ➕ Ajouter
                    </a>

                </form>

            </div>

            <div class="card">

                <h2>Liste des Exercices</h2>

                <table>

                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Niveau</th>
                        <th>Catégorie</th>
                        <th>Actif</th>
                        <!--th>Date</th-->
                        <th>Actions</th>
                    </tr>

                    <?php foreach ($list as $row): ?>

                        <tr>

                            <td><?= $row['id'] ?></td>

                            <td><?= h($row['title']) ?></td>

                            <td><?= h($levels[$row['level']]) ?></td>

                            <td><?= h($row['category']) ?></td>

                            <td>

                                <?php if ($row['is_active']): ?>
                                    <span class="badge active">Oui</span>
                                <?php else: ?>
                                    <span class="badge inactive">Non</span>
                                <?php endif; ?>

                            </td>

                            <!--td><?= h($row['created_on']) ?></td-->

                            <td>

                                <a class="btn success"
                                    href="<?= $GLOBALS['LocalHome'] ?>manage/view=<?= $row['id'] ?>/">
                                    Voir
                                </a>

                                <a class="btn warning"
                                    href="<?= $GLOBALS['LocalHome'] ?>manage/edit=<?= $row['id'] ?>/">
                                    Modifier
                                </a>

                                <a class="btn danger"
                                    onclick="return confirm('Supprimer cet exercice ?')"
                                    href="<?= $GLOBALS['LocalHome'] ?>manage/delete=<?= $row['id'] ?>/">
                                    Supprimer
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </table>

            </div>

        <?php endif; ?>

        <?php if (isset($_GET['add']) || $editData): ?>

            <div class="card">

                <h2>
                    <?= $editData ? "✏️ Modifier" : "➕ Ajouter" ?>
                </h2>

                <form method="post">

                    <input type="hidden" name="id"
                        value="<?= h($editData['id'] ?? '') ?>">

                    <label>Titre</label>
                    <input type="text" name="title"
                        required
                        value="<?= h($editData['title'] ?? '') ?>">

                    <label>Niveau</label>
                    <select name="level" required>

                        <?php foreach ($levels as $k => $v): ?>
                            <option value="<?= $k ?>"
                                <?= ($k == ($editData['level'] ?? '')) ? 'selected' : '' ?>>
                                <?= $v ?>
                            </option>
                        <?php endforeach; ?>

                    </select>

                    <label>Catégorie</label>
                    <select name="category">

                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c ?>"
                                <?= ($c == ($editData['category'] ?? '')) ? 'selected' : '' ?>>
                                <?= $c ?>
                            </option>
                        <?php endforeach; ?>

                    </select>

                    <label>Calculs (XML)</label>
                    <textarea name="calculs"><?= h($editData['calculs'] ?? '') ?></textarea>

                    <label>Réponses (XML)</label>
                    <textarea name="reponses"><?= h($editData['reponses'] ?? '') ?></textarea>

                    <p>
                        <label>
                            <input type="checkbox"
                                name="is_active"
                                value="1"
                                <?= !empty($editData['is_active']) ? 'checked' : '' ?>>
                            Actif
                        </label>
                    </p>

                    <button class="btn primary">
                        💾 Enregistrer
                    </button>

                    <a href="<?= $GLOBALS['LocalHome'] ?>manage/"
                        class="btn secondary">
                        Retour
                    </a>

                </form>

            </div>

        <?php endif; ?>

        <?php if ($viewData): ?>

            <div class="card">

                <h2><?= h($viewData['title']) ?></h2>

                <p><b>Niveau :</b> <?= h($levels[$viewData['level']]) ?></p>

                <p><b>Catégorie :</b> <?= h($viewData['category']) ?></p>

                <h3>Calculs</h3>

                <div class="xml-box">
                    <?= nl2br(h($viewData['calculs'])) ?>
                </div>

                <br>

                <h3>Réponses</h3>

                <div class="xml-box">
                    <?= nl2br(h($viewData['reponses'])) ?>
                </div>

                <br>

                <a href="<?= $GLOBALS['LocalHome'] ?>manage/"
                    class="btn secondary">
                    Retour
                </a>

            </div>

        <?php endif; ?>

    </div>

</body>

</html>