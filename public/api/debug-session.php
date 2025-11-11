<?php
require_once '../../src/config/constants.php';
require_once '../../src/config/session.php';

header('Content-Type: application/json');

echo json_encode([
    'logged_in' => isLoggedIn(),
    'role' => getSessionRole(),
    'user_id' => getSessionUserId(),
    'branch_id' => getSessionBranchId(),
    'session_data' => $_SESSION
]);
?>