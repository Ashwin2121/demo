<?php
// functions.php
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

// functions.php
function getStatusBadge($status) {
    $status = strtolower($status);
    $classes = [
        'pending' => 'status-pending',
        'approved' => 'status-approved',
        'rejected' => 'status-rejected'
    ];
    
    return isset($classes[$status]) ? $classes[$status] : '';
}