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

$table = "learn4kids_comprehension";

/* DELETE */
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM $table WHERE id=?")->execute([(int)$_GET['delete']]);
    header("Location: ". $GLOBALS['LocalHome'] ."/manage/?msg=Deleted");
    exit;
}

/* SAVE */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'title' => trim($_POST['title']),
        'level' => $_POST['level'],
        'category' => $_POST['category'],
        'text_comprehension' => $_POST['text_comprehension'],
        'questions_comprehension' => $_POST['questions_comprehension'],
        'reponses_comprehension' => $_POST['reponses_comprehension'],
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];

    if (!empty($_POST['id'])) {
        $data['id'] = (int)$_POST['id'];
        $sql = "UPDATE $table SET title=:title,level=:level,category=:category,
        text_comprehension=:text_comprehension,
        questions_comprehension=:questions_comprehension,
        reponses_comprehension=:reponses_comprehension,
        is_active=:is_active WHERE id=:id";
    } else {
        $sql = "INSERT INTO $table(title,level,category,text_comprehension,questions_comprehension,reponses_comprehension,is_active)
        VALUES(:title,:level,:category,:text_comprehension,:questions_comprehension,:reponses_comprehension,:is_active)";
    }
    $pdo->prepare($sql)->execute($data);
    header("Location: ". $GLOBALS['LocalHome'] ."/manage/?msg=Saved");
    exit;
}

$editData = null;
$viewData = null;

if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM $table WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $editData = $s->fetch(PDO::FETCH_ASSOC);
}

if (isset($_GET['view'])) {
    $s = $pdo->prepare("SELECT * FROM $table WHERE id=?");
    $s->execute([(int)$_GET['view']]);
    $viewData = $s->fetch(PDO::FETCH_ASSOC);
}

$list = $pdo->query("SELECT * FROM $table ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$levels = Settings::getLevelsInFrench();
$categories = ['Lecture', 'Compréhension', 'Français'];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Gestion Compréhensions</title>
    <?php echo Menu::menuStaticScriptMenu(); ?>
    <?php echo Menu::menuStaticCSS(); ?>
    <style>
        body {
            margin: 0;
            font-family: Segoe UI;
            background: #f4f6f9
        }

        .container {
            max-width: 1400px;
            margin: auto;
            padding: 20px
        }

        .card {
            background: #fff;
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .08)
        }

        h1 {
            color: #1e3a8a
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-sizing: border-box
        }

        textarea {
            min-height: 220px;
            font-family: Consolas
        }

        .btn {
            padding: 10px 15px;
            border-radius: 8px;
            color: #fff;
            text-decoration: none;
            border: none;
            cursor: pointer
        }

        .p {
            background: #2563eb
        }

        .s {
            background: #10b981
        }

        .w {
            background: #f59e0b
        }

        .d {
            background: #ef4444
        }

        .g {
            background: #64748b
        }

        table {
            width: 100%;
            border-collapse: collapse
        }

        th,
        td {
            padding: 10px;
            border-bottom: 1px solid #eee
        }

        th {
            background: #f8fafc
        }

        pre {
            white-space: pre-wrap;
            background: #fafafa;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #ddd
        }

        @media(max-width:900px) {
            .grid {
                grid-template-columns: 1fr
            }

            table {
                display: block;
                overflow: auto
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <?php 
            echo Menu::menuStaticMenu("");
        ?>
        <h1>📖 Gestion des Compréhensions de Lecture</h1>

        <?php if (!$editData && !$viewData && !isset($_GET['add'])): ?>
            <div class="card">
                <a href="<?= $GLOBALS['LocalHome'] ?>manage/add=1/" class="btn s">➕ Ajouter une compréhension</a>
            </div>

            <div class="card">
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Niveau</th>
                        <th>Catégorie</th>
                        <th>Actif</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($list as $r): ?>
                        <tr>
                            <td><?= $r['id'] ?></td>
                            <td><?= h($r['title']) ?></td>
                            <td><?= h($levels[$r['level']]) ?></td>
                            <td><?= h($r['category']) ?></td>
                            <td><?= $r['is_active'] ? 'Oui' : 'Non' ?></td>
                            <td>
                                <a class="btn s" href="?view=<?= $r['id'] ?>">Voir</a>
                                <a class="btn w" href="?edit=<?= $r['id'] ?>">Modifier</a>
                                <a class="btn d" onclick="return confirm('Supprimer ?')" href="?delete=<?= $r['id'] ?>">Supprimer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['add']) || $editData): ?>
            <div class="card">
                <form method="post">
                    <input type="hidden" name="id" value="<?= h($editData['id'] ?? '') ?>">
                    <label>Titre</label>
                    <input name="title" required value="<?= h($editData['title'] ?? '') ?>">

                    <div class="grid">
                        <div>
                            <label>Niveau</label>
                            <select name="level"><?php foreach ($levels as $k => $v): ?><option value="<?= $k ?>" <?= $k == ($editData['level'] ?? '') ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
                        </div>
                        <div>
                            <label>Catégorie</label>
                            <select name="category"><?php foreach ($categories as $v): ?><option <?= $v == ($editData['category'] ?? '') ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
                        </div>
                    </div>

                    <label>Texte de compréhension (XML)</label>
                    <textarea name="text_comprehension"><?= h($editData['text_comprehension'] ?? '') ?></textarea>

                    <label>Questions (XML)</label>
                    <textarea name="questions_comprehension"><?= h($editData['questions_comprehension'] ?? '') ?></textarea>

                    <label>Réponses (XML)</label>
                    <textarea name="reponses_comprehension"><?= h($editData['reponses_comprehension'] ?? '') ?></textarea>

                    <p><label><input type="checkbox" name="is_active" value="1" <?= !empty($editData['is_active']) ? 'checked' : '' ?>> Actif</label></p>

                    <button class="btn p">💾 Enregistrer</button>
                    <a href="manage.php" class="btn g">Retour</a>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($viewData): ?>
            <div class="card">
                <h2><?= h($viewData['title']) ?></h2>
                <p><b>Niveau :</b> <?= h($levels[$viewData['level']]) ?></p>
                <p><b>Catégorie :</b> <?= h($viewData['category']) ?></p>
                <h3>Texte</h3>
                <pre><?= h($viewData['text_comprehension']) ?></pre>
                <h3>Questions</h3>
                <pre><?= h($viewData['questions_comprehension']) ?></pre>
                <h3>Réponses</h3>
                <pre><?= h($viewData['reponses_comprehension']) ?></pre>
                <a href="manage.php" class="btn g">Retour</a>
            </div>
        <?php endif; ?>

    </div>
</body>

</html>