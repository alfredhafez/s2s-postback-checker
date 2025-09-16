<?php
require_once 'includes/config.php';
require_once 'lib/ClickModel.php';

$clickModel = new ClickModel($pdo);

// Get parameters from URL
$offerId = (int)($_GET['offer'] ?? 0);
$sub1 = $_GET['sub1'] ?? '';
$sub2 = $_GET['sub2'] ?? '';
$sub3 = $_GET['sub3'] ?? '';
$sub4 = $_GET['sub4'] ?? '';
$sub5 = $_GET['sub5'] ?? '';

if (!$offerId) {
    die('Invalid offer ID');
}

// Check if sub1 contains the literal placeholder {transaction_id} or is empty
if (empty($sub1) || $sub1 === '{transaction_id}') {
    // Generate a UUID for testing when placeholder is literal or empty
    $sub1 = 'test_' . uniqid() . '_' . bin2hex(random_bytes(4));
    error_log("Generated test transaction ID for click.php: {$sub1}");
}

// Collect all URL parameters for meta
$meta = $_GET;
$meta['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
$meta['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
$meta['referer'] = $_SERVER['HTTP_REFERER'] ?? '';

// Create click record
$clickId = $clickModel->create($offerId, $sub1, $sub2, $sub3, $sub4, $sub5, $meta, 'click');

if ($clickId) {
    // Redirect to offer page with the created click id
    $redirectUrl = "offer.php?id={$offerId}&click={$clickId}";
    header("Location: {$redirectUrl}");
    exit;
} else {
    die('Failed to create click record');
}
?>