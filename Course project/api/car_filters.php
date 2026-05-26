<?php
header('Content-Type: application/json');

// Database connection
require_once __DIR__ . '/../config/database.php';
$mysqli = getDBConnection();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'models':
        $brand = isset($_GET['brand']) ? $mysqli->real_escape_string($_GET['brand']) : '';
        if ($brand) {
            $query = "SELECT DISTINCT model FROM cars WHERE LOWER(brand) = LOWER('$brand') AND model IS NOT NULL AND model != '' ORDER BY model";
            $result = $mysqli->query($query);
            $models = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $models[] = $row['model'];
                }
            }
            echo json_encode(['success' => true, 'models' => $models]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Brand is required']);
        }
        break;
        
    case 'series':
        $brand = isset($_GET['brand']) ? $mysqli->real_escape_string($_GET['brand']) : '';
        $model = isset($_GET['model']) ? $mysqli->real_escape_string($_GET['model']) : '';
        if ($brand && $model) {
            $query = "SELECT DISTINCT series FROM cars WHERE LOWER(brand) = LOWER('$brand') AND LOWER(model) = LOWER('$model') AND series IS NOT NULL AND series != '' ORDER BY series";
            $result = $mysqli->query($query);
            $series = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $series[] = $row['series'];
                }
            }
            echo json_encode(['success' => true, 'series' => $series]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Brand and model are required']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

$mysqli->close();
?>

