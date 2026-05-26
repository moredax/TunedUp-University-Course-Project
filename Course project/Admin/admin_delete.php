<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

$mysqli = new mysqli("localhost","root","","tuned_cars");

$type = $_GET['type'];
$id = $_GET['id'];

$tableMap = [
    'tool' => 'tools',
    'sticker' => 'stickers',
    'car' => 'cars',
    'color' => 'car_colors',
    'light' => 'car_lights',
    'user' => 'users',
    'review' => 'reviews',
    'tool_car' => 'car_tools'
];

$table = $tableMap[$type];

// For reviews, also delete the photo file if it exists
if ($type === 'review') {
    $review_res = $mysqli->query("SELECT photo FROM reviews WHERE id=$id");
    if ($review_res && $review_row = $review_res->fetch_assoc()) {
        if (!empty($review_row['photo']) && file_exists('../' . $review_row['photo'])) {
            @unlink('../' . $review_row['photo']);
        }
    }
}

$mysqli->query("DELETE FROM $table WHERE id=$id");

$redirect_section = ($type === 'tool_car') ? 'tool_car' : (($type === 'review') ? 'reviews' : $table);
header("Location: admin.php?section={$redirect_section}");
exit;
