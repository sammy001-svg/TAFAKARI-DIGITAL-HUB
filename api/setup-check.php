<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$required = ['User','Post','Comment','ContactMessage'];
$result   = ['dbConnected'=>false,'tablesReady'=>false,'missingTables'=>[],'setupRequired'=>true,'userCount'=>0];

try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $result['dbConnected'] = true;
    $existing = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $missing  = array_values(array_diff($required, $existing));
    $result['missingTables'] = $missing;
    $result['tablesReady']   = empty($missing);
    if ($result['tablesReady']) {
        $result['userCount']     = (int)$pdo->query('SELECT COUNT(*) FROM User')->fetchColumn();
        $result['setupRequired'] = $result['userCount'] === 0;
    }
} catch (PDOException $e) {
    $result['error'] = $e->getMessage();
    json_response($result, 503);
}

json_response($result);
