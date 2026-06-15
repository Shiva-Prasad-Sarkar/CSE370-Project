<?php
$db_host = 'sql210.infinityfree.com';
$db_user = 'if0_42184599';
$db_pass = 'XG7lAu3mB7g4jY';
$db_name = 'if0_42184599_ecogrow';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    http_response_code(500);
    die('<p style="color:red;font-family:sans-serif;padding:2rem">Database connection failed: ' . htmlspecialchars($conn->connect_error) . '</p>');
}
$conn->set_charset('utf8mb4');
