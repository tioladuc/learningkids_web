<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
 }
$pdo = null;
if(isset($_SESSION['lastConnectionDurationInMinute'])) {
    $_SESSION['lastConnectionDurationInMinute'] = (new DateTimeImmutable())->format('Y-m-d H:i:s');
}
/* =========================
   CONNEXION PDO
========================= */
$GLOBALS['db'] = null;


function isLocal(): bool {
    return ($_SERVER['HTTP_HOST'] == "api.institutblaina.cm");
}

function connectBD()
{
    try {
        /* =========================
            CONFIGURATION BDD
        ========================= */
        /*$dbHost = isLocal() ? 'localhost' : 'db5019935106.hosting-data.io';
        $dbName = (isLocal()) ? 'learn4kids' : 'dbs15392165';
        $dbUser = (isLocal()) ? 'root' : 'dbu217123';
        $dbPass = (isLocal()) ? '' : 'L3@rn4k!d52026!'; // à modifier si besoin
        */
        $dbHost = isLocal() ? 'localhost' : 'db5020894361.hosting-data.io';
        $dbName = (isLocal()) ? 'learn4kids' : 'dbs15883990';
        $dbUser = (isLocal()) ? 'root' : 'dbu4094539';
        $dbPass = (isLocal()) ? '' : 'l3@rn!ngk!d5VIFC@n@d@'; // à modifier si besoin
        
        if ($GLOBALS['db'] == null) {
            $pdo = new PDO(
                "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
                $dbUser,
                $dbPass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
            $GLOBALS['db'] = $pdo;
        }

        return $GLOBALS['db'];

    } catch (Throwable $e) {
        http_response_code(500);
        die("Erreur de connexion à la base de données : " . htmlspecialchars($e->getMessage()));
    }
}
