<?php
/**
 * Get Courts AJAX Endpoint
 * Futsal Booking System
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$futsal_id = isset($_GET['futsal_id']) ? (int)$_GET['futsal_id'] : 0;

if ($futsal_id <= 0) {
    echo json_encode(['courts' => [], 'open_time' => null, 'close_time' => null]);
    exit;
}

try {
    // Get futsal opening and closing times
    $stmt = $pdo->prepare("SELECT open_time, close_time FROM futsals WHERE futsal_id = ?");
    $stmt->execute([$futsal_id]);
    $futsal = $stmt->fetch();
    
    // Get courts for this futsal
    $stmt = $pdo->prepare("SELECT court_id, court_name, surface_type, price_per_hour, status 
                           FROM courts 
                           WHERE futsal_id = ? AND status = 'active'
                           ORDER BY court_name");
    $stmt->execute([$futsal_id]);
    $courts = $stmt->fetchAll();
    
    echo json_encode([
        'courts' => $courts,
        'open_time' => $futsal ? $futsal['open_time'] : null,
        'close_time' => $futsal ? $futsal['close_time'] : null
    ]);
} catch (PDOException $e) {
    echo json_encode(['courts' => [], 'open_time' => null, 'close_time' => null, 'error' => 'Failed to load courts']);
}
?>
