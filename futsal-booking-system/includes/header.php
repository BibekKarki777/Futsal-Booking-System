<?php
/**
 * Header Include File
 * Futsal Booking System
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure BASE_URL is defined
if (!defined('BASE_URL')) {
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
    $basePath = '';
    if (strpos($scriptPath, '/admin') !== false) {
        $basePath = str_replace('/admin', '', $scriptPath);
    } elseif (strpos($scriptPath, '/user') !== false) {
        $basePath = str_replace('/user', '', $scriptPath);
    } elseif (strpos($scriptPath, '/auth') !== false) {
        $basePath = str_replace('/auth', '', $scriptPath);
    } else {
        $basePath = $scriptPath;
    }
    define('BASE_URL', rtrim($basePath, '/'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Futsal Booking System - Book your favorite futsal courts online">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Futsal Booking System</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Flatpickr Date Picker CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <!-- Flatpickr Date Picker JS (loaded early for page scripts) -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    
    <?php if (isset($additionalCSS)) echo $additionalCSS; ?>
</head>
<body>
