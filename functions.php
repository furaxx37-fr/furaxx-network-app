<?php
/**
 * Validation functions for FuraXx Network
 */

/**
 * Validate anonymous ID format
 * @param string $anonymous_id
 * @return bool
 */
function isValidAnonymousId($anonymous_id) {
    // Check if anonymous_id is not empty and matches expected format
    return !empty($anonymous_id) && 
           is_string($anonymous_id) && 
           strlen($anonymous_id) >= 5 && 
           strlen($anonymous_id) <= 50 &&
           preg_match('/^[a-zA-Z0-9_-]+$/', $anonymous_id);
}

/**
 * Validate session code format
 * @param string $session_code
 * @return bool
 */
function isValidSessionCode($session_code) {
    // Check if session_code matches the fx + 8 digits format
    return !empty($session_code) && 
           is_string($session_code) && 
           preg_match('/^fx\d{8}$/', $session_code);
}
?>
