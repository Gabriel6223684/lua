<?php
require __DIR__ . '/../vendor/autoload.php';

use app\database\builder\SelectQuery;

header('Content-Type: application/json');

try {
    // Teste simples
    $query = SelectQuery::select('id')->from('view_product');
    $total = $query->count();
    
    echo json_encode([
        'status' => true,
        'total' => $total,
        'message' => 'Teste OK'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'error' => $e->getMessage()
    ]);
}

