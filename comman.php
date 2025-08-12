<?php

// In db.php (or functions.php)
function generateRequestId() {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    
    for ($i = 0; $i < 4; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    
    $timestamp = time();
    return 'RQ-' . $randomString . '-' . $timestamp;
}

?>