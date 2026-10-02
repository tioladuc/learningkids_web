<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../core/dbcore.php';
require_once __DIR__ . '/../core/session.php';

SessionHandle::workasParent();
exit;
?>