<?php
require __DIR__ . '/../vendor/autoload.php';

use app\database\builder\SelectQuery;

header('Content-Type: application/json');

try {
    // Simula os parâmetros do DataTables
    $form = [
        'order' => [['column' => 0, 'dir' => 'asc']],
        'start' => 0,
        'length' => 10,
        'search' => ['value' => '']
    ];

    $order = $form['order'][0]['column'];
    $orderType = $form['order'][0]['dir'];
    $start = $form['start'];
    $length = $form['length'];
    $term = $form['search']['value'];

    $fields = [
        0 => 'id',
        1 => 'nome',
        3 => 'descricao_curta',
        2 => 'codigo_barra',
        4 => 'estoque',
        5 => 'valor',
    ];

    $orderField = $fields[$order];

    // Conta total
    $totalQuery = SelectQuery::select('id')->from('view_product');
    $recordsTotal = $totalQuery->count();

    // Query com filtros
    $query = SelectQuery::select()->from('view_product');
    if (!is_null($term) && ($term !== '')) {
        $query
            ->where('id', 'ilike', "%{$term}%")
            ->where('nome', 'ilike', "%{$term}%", 'or')
            ->where('descricao_curta', 'ilike', "%{$term}%", 'or')
            ->where('codigo_barra', 'ilike', "%{$term}%", 'or')
            ->where('valor', 'ilike', "%{$term}%", 'or');
    }

    // Conta filtrados
    $filteredQuery = clone $query;
    $recordsFiltered = $filteredQuery->count();

    // Busca dados
    $product = $query
        ->order($orderField, $orderType)
        ->limit($length, $start)
        ->fetchAll();

    $produtoData = [];
    foreach ($product as $key => $value) {
        $estoque = isset($value['estoque']) ? intval($value['estoque']) : 0;
        $produtoData[$key] = [
            $value['id'],
            $value['nome'],
            $value['descricao_curta'],
            $value['codigo_barra'],
            $estoque,
            $value['valor'],
            "ação"
        ];
    }

    $data = [
        'status' => true,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $produtoData
    ];

    echo json_encode($data);
} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
