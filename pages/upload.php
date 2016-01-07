<?php
include_once("inc/db.php");
header("Content-Type: application/json");

function makeerr($msg) {
    return json_encode(array("error" => true, "message" => $msg));
}

function dieerr($m) {
    global $db;
    mysqli_close($db);
    die(makeerr($m));
}

function generateRandomString($length=10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

if (!isset($_POST["username"]) || !isset($_POST["apikey"])) {
    dieerr("Username and API key are needed!");
}

$username = mysqli_real_escape_string($db, $_POST["username"]);
$apikey = mysqli_real_escape_string($db, $_POST["apikey"]);

$q = "SELECT * FROM users WHERE username='${username}'";
$res = mysqli_query($db, $q);
if (mysqli_num_rows($res) === 0) {
    dieerr("There is no user by that name!");
}

$arr = mysqli_fetch_assoc($res);
if ($arr["apikey"] !== $apikey) {
    dieerr("API key provided is invalid for user!");
}

if (!isset($_FILES["image"])) {
    dieerr("No image file given!");
}

$filename = $_FILES["image"]["name"];
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$hash = sha1_file($_FILES["image"]["tmp_name"]);
$slug = null;
$good = false;
while (!$good) {
    $good = true;
    $slug = generateRandomString(7);
    $sres = mysqli_query($db, "SELECT * FROM images WHERE slug='${slug}'");
    if (mysqli_num_rows($sres) > 0) {
        $good = false;
    }
}

if (!in_array($ext, array("gif", "png", "jpg", "jpeg"))) {
    die("Invalid file type uploaded!");
}

move_uploaded_file($_FILES["image"]["tmp_name"], "images/${slug}.${ext}");

$escaped_filename = mysqli_real_escape_string($db, $filename);
$q = "INSERT INTO images (original_name, hash, slug) VALUES ('${escaped_filename}', '${hash}', '${slug}')";
mysqli_query($db, $q);

echo json_encode(array("error" => false, "hash" => $hash, "slug" => $slug, "extension" => $ext));
mysqli_close($db);

?>