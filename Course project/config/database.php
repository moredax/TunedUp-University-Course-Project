<?php
/**
 * Database Configuration File
 * 
 * This file contains database connection settings.
 * Update these values with your server's database credentials.
 */

// Prevent direct access to this file
if (!defined('DB_HOST')) {
    // If accessed directly, define a flag to allow access
    define('DIRECT_ACCESS', true);
}

// Database configuration
define('DB_HOST', 'localhost');        // Database host (usually 'localhost' or your server's IP)
define('DB_USER', 'eigiva_tunedup');             // Database username
define('DB_PASS', '3c224pkvVu8AEj8F');                 // Database password
define('DB_NAME', 'eigiva_tunedup');       // Database name

// Optional: Database charset
define('DB_CHARSET', 'utf8mb4');

/**
 * Get database connection
 * 
 * @return mysqli Database connection object
 */
function getDBConnection() {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }
    
    // Set charset
    $mysqli->set_charset(DB_CHARSET);
    
    return $mysqli;
}

