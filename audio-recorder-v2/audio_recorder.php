<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}


if (!isset($_FILES['audio'])) {
    http_response_code(400);
    exit('No audio file received');
}


$file = $_FILES['audio'];


if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    exit('Upload error: ' .
        $file['error']);
}


$code =
    $_POST['code'] ?? '';

$course =
    $_POST['course'] ?? '';

$login =
    $_POST['login'] ?? '';


/*
 * Make sure upload directory exists.
 */
$uploadDirectory =
    __DIR__ . '/audio/';


if (!is_dir($uploadDirectory)) {

    mkdir(
        $uploadDirectory,
        0755,
        true
    );
}


/*
 * Generate unique filename.
 */
$filename = $login . '_' . $course . '_' . $code . '.mp3';
    /*uniqid(
        'recording_',
        true
    ) .
    '.mp3';*/

$destinationFolder = $uploadDirectory . "$code/";
if (!is_dir($destinationFolder)) {

    mkdir(
        $destinationFolder,
        0755,
        true
    );
}
$destination =
    $uploadDirectory . "$code/" .
    $filename;


/*
 * Move uploaded MP3.
 */
if (!move_uploaded_file(
    $file['tmp_name'],
    $destination
)) {

    http_response_code(500);

    exit('Could not save audio file');
}


/*
 * Successful response.
 */
echo json_encode([
    'success' => true,
    'filename' => $filename,
    'code' => $code,
    'course' => $course,
    'login' => $login
]);
