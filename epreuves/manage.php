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
$currentTable = "learn4kids_epreuve";

/*error_reporting(E_ALL);
ini_set('display_errors', 1);*/

function h($str)
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

$fields = [
    'code',
    'classe',
    'titre',
    'dictee',
    'dictee_fait',
    'comprehension',
    'comprehension_fait',
    'anglais_youtube_lien',
    'anglais_lecture',
    'anglais_exercice',
    'anglais_fait',
    'probleme_situation',
    'probleme_situation_fait',
    'redaction',
    'redaction_fait'
];

$message = "";

/* =========================
   DELETE
========================= */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $stmt = $pdo->prepare("DELETE FROM $currentTable WHERE id=?");
    $stmt->execute([$id]);

    header("Location: " . $GLOBALS['LocalHome'] . "manage/mnu=manage/");
    exit;
}

/* =========================
   INSERT / UPDATE
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = [];

    foreach ($fields as $field) {
        if (str_ends_with($field, '_fait')) {
            $data[$field] = isset($_POST[$field]) ? 1 : 0;
        } else {
            $data[$field] = $_POST[$field] ?? '';
        }
    }

    if (!empty($_POST['id'])) {
        // UPDATE
        $id = (int)$_POST['id'];

        $sql = "UPDATE $currentTable SET
            code=:code,
            classe=:classe,
            titre=:titre,
            dictee=:dictee,
            dictee_fait=:dictee_fait,
            comprehension=:comprehension,
            comprehension_fait=:comprehension_fait,
            anglais_youtube_lien=:anglais_youtube_lien,
            anglais_lecture=:anglais_lecture,
            anglais_exercice=:anglais_exercice,
            anglais_fait=:anglais_fait,
            probleme_situation=:probleme_situation,
            probleme_situation_fait=:probleme_situation_fait,
            redaction=:redaction,
            redaction_fait=:redaction_fait
            WHERE id=:id";

        $stmt = $pdo->prepare($sql);
        $data['id'] = $id;
        $stmt->execute($data);

        $message = "Updated successfully";
    } else {
        // INSERT
        $sql = "INSERT INTO $currentTable(
            code, classe, titre,
            dictee, dictee_fait,
            comprehension, comprehension_fait,
            anglais_youtube_lien, anglais_lecture, anglais_exercice, anglais_fait,
            probleme_situation, probleme_situation_fait,
            redaction, redaction_fait,
            created_on
        ) VALUES (
            :code, :classe, :titre,
            :dictee, :dictee_fait,
            :comprehension, :comprehension_fait,
            :anglais_youtube_lien, :anglais_lecture, :anglais_exercice, :anglais_fait,
            :probleme_situation, :probleme_situation_fait,
            :redaction, :redaction_fait,
            NOW()
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        $message = "Created successfully";
    }
}

/* =========================
   FETCH ONE
========================= */
$editData = null;

if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM $currentTable WHERE id=?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* =========================
   VIEW
========================= */
$viewData = null;

if (isset($_GET['view'])) {
    $id = (int)$_GET['view'];
    $stmt = $pdo->prepare("SELECT * FROM $currentTable WHERE id=?");
    $stmt->execute([$id]);
    $viewData = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* =========================
   LIST
========================= */
$where = [];
$params = [];

if (!empty($_GET['classe'])) {
    $where[] = "classe LIKE ?";
    $params[] = "%" . $_GET['classe'] . "%";
}

$orderAllowed = ['id', 'code', 'classe', 'titre', 'created_on'];
$order = $_GET['order'] ?? 'id';

if (!in_array($order, $orderAllowed)) {
    $order = 'id';
}

$sql = "SELECT * FROM $currentTable";

if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY $order DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$list = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Gestion Epreuves</title>
    <?php echo Menu::menuStaticScriptMenu(); ?>
    <?php echo Menu::menuStaticCSS(); ?>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: auto;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }

        h1,
        h2 {
            margin-top: 0;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            margin-bottom: 12px;
            box-sizing: border-box;
        }

        textarea {
            min-height: 120px;
        }

        button {
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 18px;
            border-radius: 8px;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        a.btn {
            text-decoration: none;
            padding: 6px 10px;
            border-radius: 6px;
            color: white;
            font-size: 13px;
        }

        .view {
            background: #10b981;
        }

        .edit {
            background: #f59e0b;
        }

        .delete {
            background: #ef4444;
        }

        .grid2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .message {
            background: #dcfce7;
            color: #166534;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .html-box {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            background: #fafafa;
        }
    </style>

    <script>
        class AudioRecorder extends HTMLElement {

            constructor() {
                super();
            }

            connectedCallback() {

                this.innerHTML = `
						<button id="startBtn">Start Recording</button>
						<button id="stopBtn" disabled>Stop Recording</button>
						<audio controls></audio>
					`;

                const startBtn = this.querySelector("#startBtn");
                const stopBtn = this.querySelector("#stopBtn");
                const audio = this.querySelector("audio");

                let recorder;
                let chunks = [];

                startBtn.addEventListener("click", async () => {

                    const stream =
                        await navigator.mediaDevices.getUserMedia({
                            audio: true
                        });

                    recorder = new MediaRecorder(stream);

                    chunks = [];

                    recorder.ondataavailable = e => {
                        chunks.push(e.data);
                    };

                    recorder.onstop = async () => {

                        const blob = new Blob(
                            chunks, {
                                type: 'audio/webm'
                            }
                        );

                        audio.src =
                            URL.createObjectURL(blob);

                        const fd = new FormData();

                        fd.append(
                            'audio',
                            blob,
                            'recording.webm'
                        );

                        fd.append(
                            'code',
                            this.getAttribute('code')
                        );

                        fd.append(
                            'course',
                            this.getAttribute('course')
                        );

                        fd.append(
                            'login',
                            this.getAttribute('login')
                        );

                        const response =
                            await fetch(
                                'save_audio.php', {
                                    method: 'POST',
                                    body: fd
                                }
                            );

                        console.log(
                            await response.text()
                        );
                    };

                    recorder.start();

                    startBtn.disabled = true;
                    stopBtn.disabled = false;
                });

                stopBtn.addEventListener("click", () => {

                    recorder.stop();

                    startBtn.disabled = false;
                    stopBtn.disabled = true;
                });
            }
        }

        customElements.define(
            'audio-recorder',
            AudioRecorder
        );
    </script>
</head>

<body>
    <div class="container">
        <?php
            echo Menu::menuStaticMenu("");
        ?>
        <h1>Gestion des Épreuves</h1>

        <?php if ($message): ?>
            <div class="message"><?= h($message) ?></div>
        <?php endif; ?>

        <!-- FILTER -->
        <?php if (!isset($_GET['delete']) && !isset($_GET['edit']) && !isset($_GET['view']) && !isset($_GET['add'])): ?>
            <div class="card">
                <form method="get" action="<?= $GLOBALS['LocalHome'] ?>manage/mnu=manage/">
                    <div class="grid2">
                        <div>
                            <label>Classe</label>
                            <input name="classe" value="<?= h($_GET['classe'] ?? '') ?>">
                        </div>
                        <div>
                            <label>Order by</label>
                            <select name="order">
                                <?php foreach (['id', 'code', 'classe', 'titre', 'created_on'] as $o): ?>
                                    <option value="<?= $o ?>" <?= ($order == $o ? 'selected' : '') ?>><?= $o ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="mnu" value="manage">
                        </div>
                    </div>
                    <div class="grid2">
                        <div><button>Search</button></div>
                        <div><a href="<?= $GLOBALS['LocalHome'] ?>manage/mnu=manage/add=add/">
                                <div class="view" style="border-radius:10px; width:100px; height:20px; padding:10px;">Ajouter</div>
                            </a></div>
                    </div>

                </form>
            </div>
        <?php endif; ?>

        <!-- FORM -->
        <?php if (isset($_GET['edit']) || isset($_GET['add'])): ?>
            <div class="card">
                <h2><?= $editData ? 'Update Epreuve' : 'Add Epreuve' ?></h2>

                <form method="post" action="<?= $GLOBALS['LocalHome'] ?>manage/mnu=manage/">
                    <input type="hidden" name="id" value="<?= h($editData['id'] ?? '') ?>">

                    <?php foreach ($fields as $field): ?>

                        <?php if (str_ends_with($field, '_fait')): ?>
                            <div class="checkbox">
                                <input type="checkbox" name="<?= $field ?>" value="1"
                                    <?= !empty($editData[$field]) ? 'checked' : '' ?>>
                                <label><?= $field ?></label>
                            </div>
                        <?php else: ?>

                            <label><?= $field ?></label>

                            <?php if (in_array($field, [
                                'dictee',
                                'comprehension',
                                'anglais_lecture',
                                'anglais_exercice',
                                'probleme_situation',
                                'redaction'
                            ])): ?>
                                <textarea name="<?= $field ?>"><?= h($editData[$field] ?? '') ?></textarea>
                            <?php else: ?>
                                <input name="<?= $field ?>" value="<?= h($editData[$field] ?? '') ?>">
                            <?php endif; ?>

                        <?php endif; ?>

                    <?php endforeach; ?>


                    <div class="grid2">
                        <div><button type="submit">Save</button></div>
                        <div><a href="<?= $GLOBALS['LocalHome'] ?>manage/mnu=manage/">
                                <div class="view" style="border-radius:10px; width:100px; height:20px; padding:10px;">Retour Liste</div>
                            </a></div>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- VIEW -->
        <?php if ($viewData): ?>
            <div class="card">
                <h2>View Epreuve: <?= h($viewData['titre']) ?></h2>

                <?php foreach ($viewData as $k => $v): ?>
                    <h3><?= h($k) ?></h3>

                    <?php if (in_array($k, [
                        'dictee',
                        'comprehension',
                        'anglais_lecture',
                        'anglais_exercice',
                        'probleme_situation',
                        'redaction'
                    ])): ?>
                        <div class="html-box"><?= $v ?></div>
                    <?php else: ?>
                        <p><?= h($v) ?></p>
                    <?php endif; ?>
                <?php endforeach; ?>

                <div class="grid2">
                    <div></div>
                    <div><a href="<?= $GLOBALS['LocalHome'] ?>manage/mnu=manage/">
                            <div class="view" style="border-radius:10px; width:100px; height:20px; padding:10px;">Retour Liste</div>
                        </a></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- LIST -->
        <?php if (!isset($_GET['delete']) && !isset($_GET['edit']) && !isset($_GET['view']) && !isset($_GET['add'])): ?>
            <div class="card">
                <h2>Liste des Épreuves</h2>

                <table>
                    <tr>
                        <th>ID</th>
                        <th>Code</th>
                        <th>Classe</th>
                        <th>Titre</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>

                    <?php foreach ($list as $row): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= h($row['code']) ?></td>
                            <td><?= h($row['classe']) ?></td>
                            <td><?= h($row['titre']) ?></td>
                            <td><?= h($row['created_on']) ?></td>
                            <td>
                                <a class="btn view" href="<?= $GLOBALS['LocalHome'] ?>manage/mnu=manage/view=<?= $row['id'] ?>/">View</a>
                                <a class="btn edit" href="<?= $GLOBALS['LocalHome'] ?>manage/mnu=manage/edit=<?= $row['id'] ?>/">Edit</a>
                                <a class="btn delete"
                                    onclick="return confirm('Delete this epreuve?')"
                                    href="<?= $GLOBALS['LocalHome'] ?>manage/mnu=manage/delete=<?= $row['id'] ?>/">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                </table>
            </div>
        <?php endif; ?>

    </div>
</body>

</html>