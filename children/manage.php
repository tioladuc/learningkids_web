<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
 }
require_once __DIR__ . '/../core/dbcore.php';
require_once(__DIR__ . "/../core/session.php");
require_once(__DIR__ . "/../core/menu.php");
require_once(__DIR__ . "/basket.php");

//$_SESSION['current_id'] = $_SESSION['parent_id'];
SessionHandle::checkingPrivilegeParent();
SessionHandle::workasChildStop();


$pdo = connectBD();
//$_SESSION['parent_id'] = 'parent_6a2f0a7629fea';
//$_SESSION['codeparent'] = 'roro';
//echo "sssssssssssssssss=== ". $_SESSION['user_type'] ." ===sssssssssssssssssss";exit;


$parentId = $_SESSION['current_id'] ?? '';


if ( isset($_POST['action']) && $_POST['action'] === 'add_child' ) {
    addChild($parentId, $pdo);
}
if ( isset($_POST['action']) && $_POST['action'] === 'update_child') {
    updateChild($parentId, $pdo);
}
if ( isset($_POST['action']) && $_POST['action'] === 'delete_child') {
    deleteChild($parentId, $pdo);
}
if ( isset($_POST['action']) && $_POST['action'] === 'workas_child') {
    SessionHandle::workasChild($_POST['child_id'], $_POST['child_name']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT *
    FROM learn4kids_children
    WHERE parent_id = ? AND is_active = 1
    ORDER BY name
");

$stmt->execute([$parentId]);

$children = $stmt->fetchAll(PDO::FETCH_ASSOC);


$stmt = $pdo->prepare("
    SELECT *
    FROM learn4kids_level
    ORDER BY code
");

$stmt->execute([]);
$levelsT = $stmt->fetchAll(PDO::FETCH_ASSOC);
$levels = array();
for ($i=0; $i < count($levelsT) ; $i++) { 
    $levels[ $levelsT[$i]['code'] ] = $levelsT[$i]['libelle'];
}

function addChild($parentId, $pdo) {
    $name = trim($_POST['name']);
    $login = trim($_POST['login']);
    $codeparent = trim($_POST['codeparent']);
    $password = trim($_POST['password']);
    $level = trim($_POST['level']);

    if (
        empty($name)
        || empty($login)
        || empty($password)
    ) {

        $msg = "Tous les champs sont obligatoires.";
    } else {

        $stmt = $pdo->prepare("
            SELECT COUNT(*) nb
            FROM learn4kids_children
            WHERE login = ? AND codeparent = ?
        ");

        $stmt->execute([$login, $codeparent]);

        $exists = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($exists['nb'] > 0) {

            $msg = "Ce login existe déjà.";
        } else {

            $id = 'child_' . uniqid();
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO learn4kids_children
                    (
                        id,
                        parent_id,
                        name,
                        login,
                        password,
                        passwordraw,
                        level,
                        codeparent,
                        codesecret,
                        parent_responsible,
                        is_active
                    )
                    VALUES
                    (
                        ?, ?, ?, ?, ?, ?, ?, '$codeparent', '', 0, 1
                    )
                ");

                $stmt->execute([
                    $id,
                    $parentId,
                    $name,
                    $login,
                    password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    ),
                    $password,
                    $level
                ]);

                
                header("Location: ". $GLOBALS['LocalHome'] ."manage/?msg=Enfant ajouté");
                exit;

            } catch (PDOException $e) { // Catch only the database exception
                
            }
            

        }
    }
}
function updateChild($parentId, $pdo) {
    try {
        $stmt = $pdo->prepare("
            UPDATE learn4kids_children
            SET
                name = ?,
                login = ?,
                password = ?,
                passwordraw = ?,
                level = ?
            WHERE id = ?
            AND parent_id = ?
        ");

        $stmt->execute([
            $_POST['name'],
            $_POST['login'],
            password_hash(
                        $_POST['password'],
                        PASSWORD_DEFAULT
                    ),
            $_POST['password'],
            
            $_POST['level'],
            $_POST['child_id'],
            $parentId
        ]);

        header("Location: ". $GLOBALS['LocalHome'] ."manage/?msg=Enfant modifié");
        exit;

    } catch (PDOException $e) { // Catch only the database exception
        
    }
    
}
function deleteChild($parentId, $pdo) {
    //echo $parentId . " ***********"; print_r($_POST); exit();
    try {
        $queryDel = "
            DELETE FROM learn4kids_children
            WHERE id = '". $_POST['child_id'] ."'
            AND parent_id = '". $parentId ."'
        ";
        
        $stmt = $pdo->prepare("
            UPDATE learn4kids_children
            SET is_active = 0, login = concat(login, ' Delete ', DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'))
            WHERE id = '". $_POST['child_id'] ."'
            AND parent_id = '". $parentId ."'
        ");

        $stmt->execute([
            $_POST['child_id'],
            $parentId
        ]);
        //echo $parentId . " *******try****"; print_r($_POST); exit();
        header("Location: ". $GLOBALS['LocalHome'] ."manage/?msg=Enfant supprimé");
        exit;

    } catch (PDOException $e) { // Catch only the database exception
        echo $parentId . " *****Catch******"; print_r($_POST); exit();
    }
    
}


?>


<!DOCTYPE html>
<html lang="fr">

<head>
    <link rel="icon" type="image/x-icon" href="<?= Settings::getHomePage() ?>favicon/favicon.ico">
    <link rel="icon" type="image/png" href="<?= Settings::getHomePage() ?>favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" href="<?= Settings::getHomePage() ?>favicon/favicon-16x16.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des enfants</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <?php echo Menu::menuStaticCSS(); ?>
    <style>
        :root {
            --primary: #4f46e5;
            --secondary: #7c3aed;
            --success: #10b981;
            --danger: #ef4444;
            --dark: #1f2937;
            --gray: #6b7280;
            --light: #f8fafc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f5f7fb;
            padding: 30px;
            min-height: 100vh;
        }

        .container {
            max-width: 1300px;
            margin: auto;
        }

        .header {
            background: linear-gradient(135deg,
                    var(--success),
                    var(--gray));
            color: white;
            padding: 30px;
            border-radius: 25px;
            margin-bottom: 30px;
            box-shadow: 0 15px 30px rgba(79, 70, 229, .20);
        }

        .header h1 {
            margin-bottom: 10px;
        }

        .header p {
            opacity: .95;
        }

        .message {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 12px;
            background: #ecfdf5;
            color: #166534;
            border-left: 5px solid var(--success);
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .05);
            margin-bottom: 30px;
        }

        .card h2 {
            margin-bottom: 20px;
            color: var(--dark);
        }

        .row {
            display: grid;
            grid-template-columns: repeat(auto-fit,minmax(220px,1fr));
            gap: 15px;
        }

        .input-group {
            margin-bottom: 15px;
        }

        .input-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 500;
        }

        .input-group input,select {
            width: 100%;
            padding: 14px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 14px;
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79,70,229,.12);
        }

        .btn {
            border: none;
            cursor: pointer;
            padding: 12px 18px;
            border-radius: 10px;
            color: white;
            font-weight: 600;
        }

        .btn-primary {
            background: linear-gradient(
                135deg,
                var(--primary),
                var(--secondary)
            );
        }

        .btn-danger {
            background: var(--danger);
        }

        .btn-success {
            background: var(--success);
        }

        .btn-othersecondary {
            background: var(--secondary);
        }

        .btn-secondary {
            background: var(--gray);
        }

        .children-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit,minmax(380px,1fr));
            gap: 20px;
        }

        .child-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0,0,0,.05);
        }

        .child-name {
            font-size: 22px;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .child-info {
            margin-bottom: 10px;
        }

        .child-info strong {
            color: var(--dark);
        }

        details {
            margin-top: 20px;
        }

        summary {
            cursor: pointer;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .edit-form {
            margin-top: 15px;
        }

        .actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .future-actions {
            margin-top: 15px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .future-actions button {
            opacity: .6;
        }

        @media(max-width:768px) {
            body {
                padding: 15px;
            }

            .children-grid {
                grid-template-columns: 1fr;
            }
        }

    </style>
    <?php echo Menu::menuStaticScriptMenu(); ?>
</head>

<body>

<div class="container">
    <?php 
                $mnuStatic = "";
                echo Menu::menuStaticMenu($mnuStatic);                
    ?>
    <div class="header">

        <h1>👨‍👩‍👧 Gestion des Enfants</h1>

        <p>
            Gérez les comptes de vos enfants, leurs accès,
            leurs mots de passe et leurs futures activités. 
            
        </p>
        
        <div class="row">
            <div class="col-4" style="justify-content: center;display: grid; place-items: center;"><?php echo Basket::displayIconBasket() ?></div>
            <div class="col-4" style="justify-content: center;display: grid; place-items: center;">
                <a href="<?= $GLOBALS['LocalHome'] ?>basket/?automate=true" style="color:white; <?= Settings::buttonDimensionStyle() ?>" class="btn div-buttom-violet">Panier Automatique</a></div>
            <div class="col-4" style="justify-content: center;display: grid; place-items: center;"><?php echo SessionHandle::displayTrialMessage(); ?> </div>            
        </div>
    </div>

    <?php if(isset($_GET['msg'])) { ?>

        <div class="message">
            <?= htmlspecialchars($_GET['msg']) ?>
        </div>

    <?php } ?>

    <div class="card">

        <h2>➕ Ajouter un enfant</h2>

        <form method="POST">

            <input
                type="hidden"
                name="action"
                value="add_child">

            <div class="row">

                <div class="input-group">
                    <label>Nom de l'enfant</label>
                    <input
                        type="text"
                        name="name"
                        required>
                </div>

                <div class="input-group">
                    <label>Login</label>
                    <input
                        type="text"
                        name="login"
                        required>
                </div>

                <div class="input-group">
                    <label>Mot de passe</label>
                    <input
                        type="text"
                        name="password"
                        required>
                </div>

                <div class="input-group">
                    <label>Niveau</label>
                    <select name='level'>
                        <?php
                            foreach($levels as $k => $v) {
                                echo "<option value='$k'>$v</option>";
                            }
                        ?>
                    </select>
                    
                </div>

                <div class="input-group">
                    <label>Code Parent</label>
                    <input
                        type="text" disabled
                        value="<?= htmlspecialchars($_SESSION['codeparent'] ?? '') ?>">
                    <input
                        type="hidden"
                        name="codeparent"
                        value="<?= $_SESSION['codeparent'] ?>"
                        required>
                </div>

            </div>

            <button
                type="submit"
                class="btn btn-primary">
                Ajouter l'enfant
            </button>

        </form>

    </div>

    <div class="children-grid">

        <?php foreach($children as $child){ ?>

            <div class="child-card">

                <div class="child-name">
                    <?= htmlspecialchars($child['name']) ?>
                </div>

                <div class="child-info">
                    <strong>Login :</strong>
                    <?= htmlspecialchars($child['login']) ?>
                </div>

                <div class="child-info">
                    <strong>Mot de passe :</strong>
                    <?= htmlspecialchars($child['passwordraw']) ?>
                </div>

                <div class="child-info">
                    <strong>Niveau :</strong>
                    <?= htmlspecialchars($levels[ $child['level'] ]) ?>
                </div>

                <div class="child-info">
                    <strong>Code Parent :</strong>
                    <?= htmlspecialchars($child['codeparent']) ?>
                </div>

                <details>

                    <summary>
                        ✏️ Modifier les informations
                    </summary>

                    <form
                        method="POST"
                        class="edit-form">

                        <input
                            type="hidden"
                            name="action"
                            value="update_child">

                        <input
                            type="hidden"
                            name="child_id"
                            value="<?= $child['id'] ?>">

                        <div class="input-group">
                            <label for="">Nom de l'enfant</label>
                            <input
                                type="text"
                                name="name"
                                value="<?= htmlspecialchars($child['name']) ?>">
                        </div>

                        <div class="input-group">
                            <label for="">Login/Nom Utilisateur</label>
                            <input
                                type="text"
                                name="login"
                                value="<?= htmlspecialchars($child['login']) ?>">
                        </div>

                        <div class="input-group">
                            <label for="">Mot de passe</label>
                            <input
                                type="text"
                                name="password"
                                value="<?= htmlspecialchars($child['passwordraw']) ?>">
                        </div>

                        <div class="input-group">
                            <label for="">Niveau</label>
                            <select name='level'>
                                <?php
                                    foreach($levels as $k => $v) {
                                        echo "<option value='$k' ". ($child['level']==$k ? "SELECTED": "") ." >$v</option>";
                                    }
                                ?>
                            </select>
                            <!--input
                                type="text"
                                name="level"
                                value="<?= htmlspecialchars($child['level']) ?>"-->
                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary">
                            Enregistrer
                        </button>

                    </form>

                </details>

                <div class="actions">

                    <form
                        method="POST"
                        onsubmit="return confirm('Voulez-vous vraiment supprimer cet enfant ?');">

                        <input
                            type="hidden"
                            name="action"
                            value="delete_child">

                        <input
                            type="hidden"
                            name="child_id"
                            value="<?= $child['id'] ?>">

                        <button
                            type="submit"
                            class="btn btn-danger">
                            Supprimer
                        </button>

                    </form>

                </div>

                <div class="actions ">

                    <a href="<?= $GLOBALS['LocalHome'] ?>childrenstats/childid=<?= $child['id'] ?>/ "><button
                        class="btn btn-othersecondary"
                        >
                        📊 Statistiques
                    </button></a>

                    <a href="<?= $GLOBALS['LocalHome'] ?>courses/childid=<?= $child['id'] ?>/ "><button
                        class="btn btn-othersecondary"
                        >
                        📚 Cours
                    </button></a>

                    <a href="<?= $GLOBALS['LocalHome'] ?>payments/childid=<?= $child['id'] ?>/"><button
                        class="btn btn-othersecondary"
                        >
                        💳 Paiements
                    </button></a>

                    
                    <form
                        method="POST"
                        onsubmit="return confirm('Voulez-vous vraiment travailler comme l\'enfant <?php echo $child['name']  ?> ?');">

                        <input
                            type="hidden"
                            name="action"
                            value="workas_child">

                        <input
                            type="hidden"
                            name="child_id"
                            value="<?= $child['id'] ?>">

                        <input
                            type="hidden"
                            name="child_name"
                            value="<?= $child['name'] ?>">

                        
                        <button type="submit"
                        class="btn btn-success" >
                        🔑 Connexion enfant
                    </button>

                    </form>


                </div>

            </div>

        <?php } ?>

    </div>

</div>

</body>
</html>