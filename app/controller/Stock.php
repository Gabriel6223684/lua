<?php

namespace app\controller;

use app\database\builder\InsertQuery;
use app\database\builder\DeleteQuery;
use app\database\builder\SelectQuery;
use app\database\builder\UpdateQuery;

class Stock extends Base
{

    public function lista($request, $response)
    {
        $dadosTemplate = [
            'titulo' => 'Movimentações de Estoque'
        ];
        return $this->getTwig()
            ->render($response, $this->setView('liststock'), $dadosTemplate)
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(200);
    }

    /**
     * Lista todas as movimentações de estoque com paginação e filtros
     */
    public function liststock($request, $response)
    {
        try {
            #Captura todas a variaveis de forma mais segura VARIAVEIS POST.
            $form = $request->getParsedBody();
            
            #Qual a coluna da tabela deve ser ordenada.
            $order = $form['order'][0]['column'] ?? 0;
            #Tipo de ordenação
            $orderType = $form['order'][0]['dir'] ?? 'desc';
            #Em qual registro se inicia o retorno dos registros, OFFSET
            $start = $form['start'] ?? 0;
            #Limite de registro a serem retornados do banco de dados LIMIT
            $length = $form['length'] ?? 10;
            
            #O termo pesquisado
            $term = $form['search']['value'] ?? '';
            
            #Filtros adicionais
            $idProduto = $form['id_produto'] ?? null;
            $tipoMovimento = $form['tipo_movimento'] ?? null;
            $dataInicial = $form['data_inicial'] ?? null;
            $dataFinal = $form['data_final'] ?? null;

            $fields = [
                0 => 'data_cadastro',
                1 => 'nome_produto',
                2 => 'tipo_movimento',
                3 => 'quantidade_entrada',
                4 => 'quantidade_saida',
                5 => 'estoque_anterior',
                6 => 'estoque_atual',
                7 => 'origem_movimento',
                8 => 'observacao'
            ];
            
            #Capturamos o nome do campo a ser ordenado.
            $orderField = $fields[$order] ?? 'data_cadastro';

            // Primeiro, conta o total de registros (sem filtros)
            $totalQuery = SelectQuery::select('id')->from('view_stock_movement');
            $recordsTotal = $totalQuery->count();

            // Query com filtros para dados
            $query = SelectQuery::select()->from('view_stock_movement');
            
            // Filtro de busca global
            if (!is_null($term) && ($term !== '')) {
                $query
                    ->where('nome_produto', 'ilike', "%{$term}%")
                    ->where('codigo_barra', 'ilike', "%{$term}%", 'or')
                    ->where('observacao', 'ilike', "%{$term}%", 'or')
                    ->where('origem_movimento', 'ilike', "%{$term}%", 'or');
            }
            
            // Filtro por produto
            if (!is_null($idProduto) && $idProduto !== '') {
                $query->where('id_produto', '=', $idProduto);
            }
            
            // Filtro por tipo de movimento
            if (!is_null($tipoMovimento) && $tipoMovimento !== '') {
                $query->where('tipo_movimento', '=', $tipoMovimento);
            }
            
            // Filtro por data inicial
            if (!is_null($dataInicial) && $dataInicial !== '') {
                $query->where('data_cadastro', '>=', $dataInicial . ' 00:00:00');
            }
            
            // Filtro por data final
            if (!is_null($dataFinal) && $dataFinal !== '') {
                $query->where('data_cadastro', '<=', $dataFinal . ' 23:59:59');
            }

            // Conta registros filtrados
            $filteredQuery = clone $query;
            $recordsFiltered = $filteredQuery->count();

            // Busca os dados paginados
            $movements = $query
                ->order($orderField, $orderType)
                ->limit($length, $start)
                ->fetchAll();

            $movimentoData = [];
            foreach ($movements as $key => $value) {
                $tipo = $value['tipo_movimento'] ?? '';
                $corTipo = $tipo === 'ENTRADA' ? 'text-success' : ($tipo === 'SAIDA' ? 'text-danger' : 'text-warning');
                $iconeTipo = $tipo === 'ENTRADA' ? 'bi-arrow-down-circle' : ($tipo === 'SAIDA' ? 'bi-arrow-up-circle' : 'bi-arrow-repeat');
                
                $origem = $value['origem_movimento'] ?? 'AJUSTE';
                $corOrigem = match($origem) {
                    'COMPRA' => 'bg-primary',
                    'VENDA' => 'bg-danger',
                    'AJUSTE_MANUAL' => 'bg-warning text-dark',
                    default => 'bg-secondary'
                };
                
                $entrada = $value['quantidade_entrada'] ? floatval($value['quantidade_entrada']) : '-';
                $saida = $value['quantidade_saida'] ? floatval($value['quantidade_saida']) : '-';
                
                $estoqueAnterior = $value['estoque_anterior'] ? intval($value['estoque_anterior']) : 0;
                $estoqueAtual = $value['estoque_atual'] ? intval($value['estoque_atual']) : 0;
                
                $dataFormatada = date('d/m/Y H:i', strtotime($value['data_cadastro']));

                $movimentoData[$key] = [
                    $dataFormatada,
                    $value['nome_produto'],
                    "<span class='{$corTipo} fw-bold'><i class='bi {$iconeTipo} me-1'></i>{$tipo}</span>",
                    $entrada,
                    $saida,
                    $estoqueAnterior,
                    $estoqueAtual,
                    "<span class='badge {$corOrigem}'>{$origem}</span>",
                    $value['observacao'] ?? '-'
                ];
            }

            $data = [
                'status' => true,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $movimentoData
            ];

            $payload = json_encode($data);
            $response->getBody()->write($payload);

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
                
        } catch (\Exception $e) {
            $data = [
                'status' => false,
                'msg' => 'Erro ao listar movimentações: ' . $e->getMessage()
            ];
            $payload = json_encode($data);
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Lista produtos para select2
     */
    public function listproductsselect($request, $response)
    {
        try {
            $form = $request->getParsedBody();
            $term = $form['term'] ?? null;
            
            $query = SelectQuery::select('id', 'nome', 'codigo_barra')
                ->from('product')
                ->where('excluido', '=', 'false');
                
            if (!is_null($term) && $term !== '') {
                $query->where('nome', 'ILIKE', "%{$term}%", 'or')
                    ->where('codigo_barra', 'ILIKE', "%{$term}%", 'or');
            }
            
            $produtos = $query->order('nome', 'ASC')->limit(50)->fetchAll();
            
            $data = [];
            foreach ($produtos as $key => $item) {
                $data['results'][$key] = [
                    'id' => $item['id'],
                    'text' => $item['nome'] . ' - Cód: ' . $item['codigo_barra']
                ];
            }
            
            return $this->SendJson($response, $data);
            
        } catch (\Exception $e) {
            return $this->SendJson($response, ['status' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Registra entrada de estoque
     */
    public function entrada($request, $response)
    {
        try {
            $form = $request->getParsedBody();
            $id_produto = $form['id_produto'] ?? null;
            $quantidade = $form['quantidade'] ?? null;
            $observacao = $form['observacao'] ?? 'Entrada de estoque';

            if (empty($id_produto)) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'Selecione um produto!'], 400);
            }

            if (empty($quantidade) || floatval($quantidade) <= 0) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'Informe uma quantidade válida!'], 400);
            }

            // Buscar produto
            $produto = SelectQuery::select('id', 'nome')
                ->from('product')
                ->where('id', '=', $id_produto)
                ->fetch();

            if (!$produto) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'Produto não encontrado!'], 404);
            }

            // Registrar movimentação
            $FieldAndValues = [
                'id_produto' => $id_produto,
                'quantidade_entrada' => floatval($quantidade),
                'quantidade_saida' => 0,
                'observacao' => $observacao,
                'tipo' => 'ENTRADA',
                'origem_movimento' => 'AJUSTE_MANUAL'
            ];

            $IsSave = InsertQuery::table('stock_movement')->save($FieldAndValues);

            if (!$IsSave) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'Erro ao registrar entrada!'], 500);
            }

            return $this->SendJson($response, [
                'status' => true, 
                'msg' => 'Entrada registrada com sucesso!',
                'id_produto' => $id_produto
            ]);

        } catch (\Exception $e) {
            return $this->SendJson($response, ['status' => false, 'msg' => 'Erro: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Registra saída de estoque
     */
    public function saida($request, $response)
    {
        try {
            $form = $request->getParsedBody();
            $id_produto = $form['id_produto'] ?? null;
            $quantidade = $form['quantidade'] ?? null;
            $observacao = $form['observacao'] ?? 'Saída de estoque';

            if (empty($id_produto)) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'Selecione um produto!'], 400);
            }

            if (empty($quantidade) || floatval($quantidade) <= 0) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'Informe uma quantidade válida!'], 400);
            }

            // Buscar produto e verificar estoque
            $produto = SelectQuery::select('id', 'nome')
                ->from('view_product')
                ->where('id', '=', $id_produto)
                ->fetch();

            if (!$produto) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'Produto não encontrado!'], 404);
            }

            $estoqueAtual = floatval($produto['estoque'] ?? 0);
            if ($estoqueAtual < floatval($quantidade)) {
                return $this->SendJson($response, [
                    'status' => false, 
                    'msg' => "Estoque insuficiente! Estoque atual: {$estoqueAtual}"
                ], 400);
            }

            // Registrar movimentação
            $FieldAndValues = [
                'id_produto' => $id_produto,
                'quantidade_entrada' => 0,
                'quantidade_saida' => floatval($quantidade),
                'observacao' => $observacao,
                'tipo' => 'SAIDA',
                'origem_movimento' => 'AJUSTE_MANUAL'
            ];

            $IsSave = InsertQuery::table('stock_movement')->save($FieldAndValues);

            if (!$IsSave) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'Erro ao registrar saída!'], 500);
            }

            return $this->SendJson($response, [
                'status' => true, 
                'msg' => 'Saída registrada com sucesso!',
                'id_produto' => $id_produto
            ]);

        } catch (\Exception $e) {
            return $this->SendJson($response, ['status' => false, 'msg' => 'Erro: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Retorna o estoque atual de um produto
     */
    public function getstock($request, $response)
    {
        try {
            $form = $request->getParsedBody();
            $id_produto = $form['id_produto'] ?? null;

            if (empty($id_produto)) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'ID do produto não informado!'], 400);
            }

            $produto = SelectQuery::select('id', 'nome', 'estoque')
                ->from('view_product')
                ->where('id', '=', $id_produto)
                ->fetch();

            if (!$produto) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'Produto não encontrado!'], 404);
            }

            return $this->SendJson($response, [
                'status' => true,
                'estoque' => intval($produto['estoque'] ?? 0),
                'nome' => $produto['nome']
            ]);

        } catch (\Exception $e) {
            return $this->SendJson($response, ['status' => false, 'msg' => 'Erro: ' . $e->getMessage()], 500);
        }
    }
}

