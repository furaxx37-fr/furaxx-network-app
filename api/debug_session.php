<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Debug Info:\n";
echo "Current working directory: " . getcwd() . "\n";
echo "File exists check: " . (file_exists('../config.php') ? 'YES' : 'NO') . "\n";

try {
    require_once '../config.php';
    echo "Config loaded successfully\n";
    echo "Function exists: " . (function_exists('isValidAnonymousId') ? 'YES' : 'NO') . "\n";
} catch (Exception $e) {
    echo "Error loading config: " . $e->getMessage() . "\n";
}
?>
