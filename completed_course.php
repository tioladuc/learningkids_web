<?php

header('Content-Type: application/json');

require_once( __DIR__ . '/core/dbcore.php');  // Your PDO connection
$pdo = connectBD();

try {

    $child_id = $_POST['child_id'] ?? '';
    $course_code = $_POST['course_code'] ?? '';
    $course_item_id = $_POST['course_item_id'] ?? '';

    if (
        empty($child_id) ||
        empty($course_code) ||
        empty($course_item_id)
    ) {

        echo json_encode([
            "success" => false,
            "message" => "Missing required information."
        ]);
        exit;
    }

    // Prevent duplicate completion
    $check = $pdo->prepare("
        SELECT COUNT(*)
        FROM learn4kids_complete_courses_items
        WHERE child_id = ?
          AND course_code = ?
          AND course_item_id = ?
    ");

    $check->execute([
        $child_id,
        $course_code,
        $course_item_id
    ]);

    if ($check->fetchColumn() > 0) {
        $check = $pdo->prepare("
            DELETE
            FROM learn4kids_complete_courses_items
            WHERE child_id = ?
            AND course_code = ?
            AND course_item_id = ?
        ");

        $check->execute([
            $child_id,
            $course_code,
            $course_item_id
        ]);
        echo json_encode([
            "success" => true,
            "message" => "This course item has been marked as Not Completed."
        ]);
        exit;

    }

    $stmt = $pdo->prepare("
        INSERT INTO learn4kids_complete_courses_items
        (
            child_id,
            course_code,
            course_item_id,
            access_date
        )
        VALUES
        (
            ?, ?, ?, NOW()
        )
    ");

    $stmt->execute([
        $child_id,
        $course_code,
        $course_item_id
    ]);

    echo json_encode([
        "success" => true,
        "message" => "This course item has been marked as Completed."

    ]);

} catch (PDOException $e) {

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);

}

?>