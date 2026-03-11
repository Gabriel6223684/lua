<?php

namespace app\controller;

use app\database\builder\InsertQuery;
use app\database\builder\SelectQuery;
use app\database\builder\UpdateQuery;
use app\database\builder\DeleteQuery;

class Sale extends Base
{
    public function cadastro($request, $response)
    {
        $dadosTemplate = [
            'titulo' => 'Página inicial',
            'acao' => 'c'
        ];
        return $this->getTwig()
            ->render($response, $this->setView('sale'), $dadosTemplate)
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(200);
    }
    public function lista($request, $response)
    {
        $dadosTemplate = [
            'titulo' => 'Página inicial'
        ];
        return $this->getTwig()
            ->render($response, $this->setView('listsale'), $dadosTemplate)
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(200);
    }
    public function insert($request, $response)
    {
        #captura os dados do formulário
        $form = $request->getParsedBody();
        
        #seleciona o id do cliente CONSUMIDOR FINAL para inserir a venda
        $customer = SelectQuery::select('id')
            ->from('customer')
            ->order('id', 'asc')
            ->limit(1)
            ->fetch();
        #Verificar se o cliente não foi encontrado
        if (!$customer) {
            return $this->SendJson($response, [
                'status' => false,
                'msg' => 'Restrição: Nenhum cliente encontrado!',
                'id' => 0
            ], 403);
        }
        #seleciona o id do cliente CONSUMIDOR FINAL para inserir a venda
        $id_customer = $customer['id'];
        $FieldAndValue = [
            'id_cliente' => $id_customer,
            'total_bruto' => 0,
            'total_liquido' => 0,
            'desconto' => 0,
            'acrescimo' => 0,
            'observacao' => ''
        ];
        try {
            #Tenta inserir a venda no banco de dados e captura o resultado da inserção
            $IsInserted = InsertQuery::table('sale')->save($FieldAndValue);
            #Verificar se a inserção falhou
            if (!$IsInserted) {
                return $this->SendJson(
                    $response,
                    [
                        'status' => false,
                        'msg' => 'Restrição: Falha ao inserir a venda!',
                        'id' => 0
                    ],
                    403
                );
            }
            #Seleciona o id da venda inserida mais recente para retornar na resposta
            $sale = SelectQuery::select('id')
                ->from('sale')
                ->order('id', 'desc')
                ->limit(1)
                ->fetch();
            #Verificar se a venda não foi encontrada
            if (!$sale) {
                return $this->SendJson($response, [
                    'status' => false,
                    'msg' => 'Restrição: Nenhuma venda encontrada!',
                    'id' => 0
                ], 403);
            }
            $id_sale = $sale["id"];
            return $this->SendJson($response, [
                'status' => true,
                'msg' => 'Venda inserida com sucesso!',
                'id' => $id_sale
            ], 201);
        } catch (\Exception $e) {
            return $this->SendJson($response, [
                'status' => false,
                'msg' => 'Restrição: ' . $e->getMessage(),
                'id' => 0
            ], 500);
        }
    }
    public function update($request, $response)
    {
        $form = $request->getParsedBody();
        $id = $form['id'] ?? null;
        $id_cliente = $form['id_cliente'] ?? null;
        $observacao = $form['observacao'] ?? null;
        $quantidade = $form['quantidade'] ?? null;
        if (is_null($id)) {
            return $this->SendJson($response, ['status' => false, 'msg' => 'Para alterar a venda informa o código!'], 403);
        }

        try {
            $total_venda = SelectQuery::select("sum(total_liquido) as total_liquido,sum(total_bruto) as total_bruto")
                ->from('item_sale')
                ->where('id_venda', '=', $id)
                ->fetch();

            $total_bruto = $total_venda ? floatval($total_venda['total_bruto'] ?? 0) : 0;
            $total_liquido = $total_venda ? floatval($total_venda['total_liquido'] ?? 0) : 0;

            $FieldAndValues = [
                'total_bruto' => $total_bruto,
                'total_liquido' => $total_liquido
            ];
            #Alteramos o código do cliente
            if (!is_null($id_cliente)) {
                $FieldAndValues['id_cliente'] = $id_cliente;
            }
            #Alteramos a observação
            if (!is_null($observacao)) {
                $FieldAndValues['observacao'] = $observacao;
            }
            if (!is_null($quantidade)) {
                $FieldAndValues['quantidade'] = $quantidade;
            }
            $isUpdated = UpdateQuery::table('sale')->set($FieldAndValues)->where('id', '=', $id)->update();
            if (!$isUpdated) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'Restrição: ' . $isUpdated, 'id' => 0], 500);
            }
            return $this->SendJson($response, [
                'status' => true,
                'msg' => 'Atualização realizada com sucesso!',
                'id' => $id,
                'data' => $total_venda
            ]);
        } catch (\Exception $e) {
            return $this->SendJson($response, ['status' => false, 'msg' => 'Restrição: ' . $e->getMessage(), 'id' => 0], 500);
        }
    }
    public function alterar($request, $response, $args)
    {
        $id = $args['id'];
        try {
            $sale = SelectQuery::select()
                ->from('sale')
                ->where('id', '=', $id)
                ->fetch();
            if (!$sale) {
                return header('Location: /venda/lista');
                die;
            }
            $dadosTemplate = [
                'titulo' => 'Página inicial',
                'acao' => 'e',
                'id' => $id,
                'sale' => $sale
            ];
            return $this->getTwig()
                ->render($response, $this->setView('sale'), $dadosTemplate)
                ->withHeader('Content-Type', 'text/html')
                ->withStatus(200);
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
    }
    public function insertitem($request, $response)
    {
        #captura os dados do formulário
        $form = $request->getParsedBody();
        $id = $form['id'] ?? null;
        $id_produto = $form['pesquisa'];
        #Verificar se o id da venda esta vasio ou nulo
        if (empty($id) or is_null($id)) {
            return $this->SendJson($response, [
                'status' => false,
                'msg' => 'Restrição: O ID da venda é obrigatório!',
                'id' => 0
            ], 403);
        }
        #Verificar se o id do produto esta vasio ou nulo
        if (empty($id_produto) or is_null($id_produto)) {
            return $this->SendJson($response, [
                'status' => false,
                'msg' => 'Restrição: O ID do produto é obrigatório!',
                'id' => 0
            ], 403);
        }
        try {
            #Selecionamos o produto que esta sendo vendido
            $produto = SelectQuery::select()->from('product')->where('id', '=', $id_produto)->fetch();
            if (!$produto) {
                return $this->SendJson($response, [
                    'status' => false,
                    'msg' => 'Restrição: Nenhum produto localizado!',
                    'id' => 0
                ], 403);
            }
            $FieldAndValue = [
                'id_venda' => $id,
                'id_produto' => $id_produto,
                'quantidade' => 1,
                'total_bruto' => $produto['valor'],
                'total_liquido' => $produto['valor'],
                'desconto' => 0,
                'acrescimo' => 0,
                'nome' => $produto['nome'],
            ];
            $isInserted = InsertQuery::table('item_sale')->save($FieldAndValue);
            if (!$isInserted) {
                return $this->SendJson($response, [
                    'status' => false,
                    'msg' => 'Restrição: ' . $isInserted,
                    'id' => 0
                ], 500);
            }
            
            // Atualiza os totais da venda após inserir item
            $this->atualizarTotaisVenda($id);
            
            return $this->SendJson($response, [
                'status' => true,
                'msg' => 'Item inserido com sucesso!',
                'id' => 0
            ]);
        } catch (\Exception $e) {
            return $this->SendJson($response, [
                'status' => false,
                'msg' => 'Restrição: ' . $e->getMessage(),
                'id' => 0
            ], 500);
        }
    }
    public function listitemsale($request, $response)
    {
        $form = $request->getParsedBody();
        $id = $form['id'] ?? null;
        #Verificar se o id da venda esta vasio ou nulo
        if (empty($id) or is_null($id)) {
            return $this->SendJson($response, [
                'status' => false,
                'msg' => 'Restrição: O ID da venda é obrigatório!',
                'id' => 0
            ], 403);
        }

        $total_venda = SelectQuery::select("sum(total_liquido) as total_liquido,sum(total_bruto) as total_bruto")
            ->from('item_sale')
            ->where('id_venda', '=', $id)
            ->fetch();

        $items = SelectQuery::select('id,id_produto,nome,quantidade,total_liquido,total_bruto')
            ->from('item_sale')
            ->where('id_venda', '=', $id)
            ->fetchAll();

        // Garantir que os totais não sejam nulos
        $total_liquido = $total_venda ? floatval($total_venda['total_liquido'] ?? 0) : 0;
        $total_bruto = $total_venda ? floatval($total_venda['total_bruto'] ?? 0) : 0;

        $data = [
            'status' => true,
            'id' => $id,
            'msg' => 'Dados listados com sucesso!',
            'sale' => [
                'total_liquido' => $total_liquido,
                'total_bruto' => $total_bruto
            ],
            'data' => $items
        ];
        return $this->SendJson($response, $data);
    }

    public function listsale($request, $response)
    {
        try {
            #Captura todas a variaveis de forma mais segura VARIAVEIS POST.
            $form = $request->getParsedBody();
            
            # DataTables required parameter
            $draw = isset($form['draw']) ? intval($form['draw']) : 1;
            
            #Qual a coluna da tabela deve ser ordenada.
            $order = $form['order'][0]['column'] ?? 0;
            #Tipo de ordenação
            $orderType = $form['order'][0]['dir'] ?? 'desc';
            #Em qual registro se inicia o retorno dos registros, OFFSET
            $start = $form['start'] ?? 0;
            #Limite de registro a serem retornados do banco de dados LIMIT
            $length = $form['length'] ?? 10;
            
            $fields = [
                0 => 'id',
                1 => 'id_cliente',
                2 => 'total_bruto',
                3 => 'desconto',
                4 => 'total_liquido',
                5 => 'observacao',
                6 => 'data_cadastro',
            ];
            #Capturamos o nome do campo a ser ordenado.
            $orderField = $fields[$order] ?? 'id';
            #O termo pesquisado
            $term = $form['search']['value'] ?? '';
            
            # Contar total de registros (sem limite) para paginação correta
            $countQuery = SelectQuery::select('id')->from('sale');
            if (!is_null($term) && ($term !== '')) {
                $countQuery->where('id', 'ilike', "%{$term}%", 'or')
                    ->where('id_cliente', 'ilike', "%{$term}%", 'or')
                    ->where('observacao', 'ilike', "%{$term}%", 'or')
                    ->where('total_bruto', 'ilike', "%{$term}%", 'or')
                    ->where('total_liquido', 'ilike', "%{$term}%");
            }
            $totalRecords = $countQuery->count();
            
            # Query com paginação
            $query = SelectQuery::select('id, id_cliente, total_bruto, desconto, total_liquido, observacao, data_cadastro')
                ->from('sale');
                
            if (!is_null($term) && ($term !== '')) {
                $query->where('id', 'ilike', "%{$term}%", 'or')
                    ->where('id_cliente', 'ilike', "%{$term}%", 'or')
                    ->where('observacao', 'ilike', "%{$term}%", 'or')
                    ->where('total_bruto', 'ilike', "%{$term}%", 'or')
                    ->where('total_liquido', 'ilike', "%{$term}%");
            }
            
            $sales = $query
                ->order($orderField, $orderType)
                ->limit($length, $start)
                ->fetchAll();
                
            # Buscar nomes de clientes
            $salesData = [];
            foreach ($sales as $key => $value) {
                # Buscar nome do cliente
                $customer = SelectQuery::select('nome_fantasia')
                    ->from('customer')
                    ->where('id', '=', $value['id_cliente'])
                    ->fetch();
                
                $nomeCliente = $customer ? $customer['nome_fantasia'] : 'Consumidor Final';
                $dataFormatada = date('d/m/Y H:i', strtotime($value['data_cadastro']));
                
                $salesData[$key] = [
                    $value['id'],
                    $nomeCliente,
                    'R$ ' . number_format($value['total_bruto'], 2, ',', '.'),
                    'R$ ' . number_format($value['desconto'], 2, ',', '.'),
                    'R$ ' . number_format($value['total_liquido'], 2, ',', '.'),
                    $value['observacao'] ?? '-',
                    $dataFormatada,
                    "<div class='d-flex gap-2'>
                        <a href='/venda/alterar/{$value['id']}' class='btn btn-warning btn-sm px-2 shadow-sm' style='white-space: nowrap; font-weight: 500;'>
                            <i class='bi bi-pencil-square'></i> Alterar
                        </a>
                        <button type='button' onclick='Delete({$value['id']});' class='btn btn-danger btn-sm px-2 shadow-sm' style='white-space: nowrap; font-weight: 500;'>
                            <i class='bi bi-trash-fill'></i> Excluir
                        </button>
                    </div>"
                ];
            }
            
            # DataTables expected format
            $data = [
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $salesData
            ];
            
            $payload = json_encode($data);
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
                
        } catch (\Exception $e) {
            $data = [
                'draw' => isset($form['draw']) ? intval($form['draw']) : 1,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Erro ao listar vendas: ' . $e->getMessage()
            ];
            $payload = json_encode($data);
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        }
    }

    public function delete($request, $response)
    {
        try {
            $form = $request->getParsedBody();
            $id = $form['id'] ?? null;
            
            if (empty($id) || is_null($id)) {
                return $this->SendJson($response, [
                    'status' => false,
                    'msg' => 'Restrição: O ID da venda é obrigatório!',
                    'id' => 0
                ], 403);
            }
            
            # Primeiro excluir os itens da venda
            DeleteQuery::table('item_sale')
                ->where('id_venda', '=', $id)
                ->delete();
            
            # Depois excluir a venda
            $IsDelete = DeleteQuery::table('sale')
                ->where('id', '=', $id)
                ->delete();

            if (!$IsDelete) {
                return $this->SendJson($response, [
                    'status' => false,
                    'msg' => 'Erro ao excluir venda!',
                    'id' => $id
                ], 403);
            }
            
            return $this->SendJson($response, [
                'status' => true,
                'msg' => 'Venda removida com sucesso!',
                'id' => $id
            ]);
            
        } catch (\Throwable $th) {
            return $this->SendJson($response, [
                'status' => false,
                'msg' => 'Erro: ' . $th->getMessage(),
                'id' => 0
            ], 500);
        }
    }

    public function deleteitem($request, $response)
    {
        try {
            $form = $request->getParsedBody();
            $id = $form['id'] ?? null;
            
            if (empty($id) || is_null($id)) {
                return $this->SendJson($response, [
                    'status' => false,
                    'msg' => 'Restrição: O ID do item é obrigatório!',
                    'id' => 0
                ], 403);
            }
            
            // Buscar o item para obter o id_venda antes de excluir
            $item = SelectQuery::select('id_venda')->from('item_sale')->where('id', '=', $id)->fetch();
            
            $IsDelete = DeleteQuery::table('item_sale')
                ->where('id', '=', $id)
                ->delete();

            if (!$IsDelete) {
                return $this->SendJson($response, [
                    'status' => false,
                    'msg' => 'Erro ao excluir item!',
                    'id' => $id
                ], 403);
            }
            
            // Atualizar totais da venda após excluir item
            if ($item && isset($item['id_venda'])) {
                $this->atualizarTotaisVenda($item['id_venda']);
            }
            
            return $this->SendJson($response, [
                'status' => true,
                'msg' => 'Item removido com sucesso!',
                'id' => $id
            ]);
            
        } catch (\Throwable $th) {
            return $this->SendJson($response, [
                'status' => false,
                'msg' => 'Erro: ' . $th->getMessage(),
                'id' => 0
            ], 500);
        }
    }

    public function updateItem($request, $response)
    {
        try {
            $form = $request->getParsedBody();
            $id = $form['id'] ?? null;
            $quantidade = $form['quantidade'] ?? null;
            
            if (empty($id) || is_null($id)) {
                return $this->SendJson($response, [
                    'status' => false,
                    'msg' => 'Restrição: O ID do item é obrigatório!',
                    'id' => 0
                ], 403);
            }
            
            $quantidade = intval($quantidade);
            if (is_null($quantidade) || $quantidade <= 0) {
                return $this->SendJson($response, [
                    'status' => false,
                    'msg' => 'Restrição: A quantidade deve ser maior que zero!',
                    'id' => 0
                ], 403);
            }
            
            // Buscar o item atual para calcular os valores
            $item = SelectQuery::select()->from('item_sale')->where('id', '=', $id)->fetch();
            if (!$item) {
                return $this->SendJson($response, [
                    'status' => false,
                    'msg' => 'Restrição: Item não encontrado!',
                    'id' => 0
                ], 403);
            }
            
            $quantidadeItem = intval($item['quantidade'] ?? 1);
            $valorUnitario = floatval($item['total_liquido'] ?? 0) / $quantidadeItem;
            
            // Calcular novos totais
            $total_bruto = $valorUnitario * $quantidade;
            $total_liquido = $valorUnitario * $quantidade;
            
            $FieldAndValues = [
                'quantidade' => $quantidade,
                'total_bruto' => $total_bruto,
                'total_liquido' => $total_liquido
            ];
            
            $isUpdated = UpdateQuery::table('item_sale')->set($FieldAndValues)->where('id', '=', $id)->update();
            
            if (!$isUpdated) {
                return $this->SendJson($response, [
                    'status' => false,
                    'msg' => 'Restrição: Erro ao atualizar item!',
                    'id' => 0
                ], 403);
            }
            
            // Atualiza os totais da venda
            $this->atualizarTotaisVenda($item['id_venda']);
            
            return $this->SendJson($response, [
                'status' => true,
                'msg' => 'Item atualizado com sucesso!',
                'id' => $id
            ]);
            
        } catch (\Throwable $th) {
            return $this->SendJson($response, [
                'status' => false,
                'msg' => 'Erro: ' . $th->getMessage(),
                'id' => 0
            ], 500);
        }
    }
    
    // Método auxiliar para atualizar totais da venda
    private function atualizarTotaisVenda($id_venda)
    {
        $total_venda = SelectQuery::select("sum(total_liquido) as total_liquido,sum(total_bruto) as total_bruto")
            ->from('item_sale')
            ->where('id_venda', '=', $id_venda)
            ->fetch();

        $total_bruto = $total_venda ? floatval($total_venda['total_bruto'] ?? 0) : 0;
        $total_liquido = $total_venda ? floatval($total_venda['total_liquido'] ?? 0) : 0;

        UpdateQuery::table('sale')
            ->set([
                'total_bruto' => $total_bruto,
                'total_liquido' => $total_liquido
            ])
            ->where('id', '=', $id_venda)
            ->update();
    }

    public function print($request, $response)
    {
        try {
            $sales = SelectQuery::select('id, id_cliente, total_bruto, desconto, total_liquido, observacao, data_cadastro')
                ->from('sale')
                ->order('id', 'DESC')
                ->fetchAll();

            # Buscar nomes de clientes
            $salesData = [];
            foreach ($sales as $sale) {
                $customer = SelectQuery::select('nome_fantasia')
                    ->from('customer')
                    ->where('id', '=', $sale['id_cliente'])
                    ->fetch();
                
                $salesData[] = [
                    'id' => $sale['id'],
                    'cliente' => $customer ? $customer['nome_fantasia'] : 'Consumidor Final',
                    'total_bruto' => $sale['total_bruto'],
                    'desconto' => $sale['desconto'],
                    'total_liquido' => $sale['total_liquido'],
                    'observacao' => $sale['observacao'] ?? '-',
                    'data' => date('d/m/Y H:i', strtotime($sale['data_cadastro']))
                ];
            }

            $dadosTemplate = [
                'titulo' => 'Relatório de Vendas',
                'vendas' => $salesData,
                'total' => count($salesData)
            ];

            return $this->getTwig()
                ->render($response, $this->setView('reports/reportsale'), $dadosTemplate)
                ->withHeader('Content-Type', 'text/html')
                ->withStatus(200);

        } catch (\Exception $e) {
            $response->getBody()->write("Erro ao gerar relatório: " . $e->getMessage());
            return $response->withStatus(500);
        }
    }

    public function finalizar($request, $response)
    {
        try {
            $form = $request->getParsedBody();
            $id = $form['id'] ?? null;
            $paymentData = isset($form['paymentData']) ? json_decode($form['paymentData'], true) : null;
            
            if (empty($id) || is_null($id)) {
                return $this->SendJson($response, [
                    'status' => false,
                    'msg' => 'Restrição: O ID da venda é obrigatório!',
                    'id' => 0
                ], 403);
            }
            
            if (!$paymentData) {
                return $this->SendJson($response, [
                    'status' => false,
                    'msg' => 'Restrição: Dados do pagamento são obrigatórios!',
                    'id' => 0
                ], 403);
            }
            
            // Validar forma de pagamento
            $formaPagamento = $paymentData['formaPagamento'] ?? '';
            if (empty($formaPagamento)) {
                return $this->SendJson($response, [
                    'status' => false,
                    'msg' => 'Restrição: Forma de pagamento é obrigatória!',
                    'id' => 0
                ], 403);
            }
            
            // Validar valor pago conforme forma de pagamento
            $valorTotal = floatval($paymentData['valorTotal'] ?? 0);
            $valorPago = floatval($paymentData['valorPago'] ?? 0);
            
            if ($formaPagamento === 'DINHEIRO' && $valorPago < $valorTotal) {
                return $this->SendJson($response, [
                    'status' => false,
                    'msg' => 'Restrição: Valor pago é menor que o total da venda!',
                    'id' => 0
                ], 403);
            }
            
            // Buscar a venda para verificar se existe
            $sale = SelectQuery::select()->from('sale')->where('id', '=', $id)->fetch();
            if (!$sale) {
                return $this->SendJson($response, [
                    'status' => false,
                    'msg' => 'Restrição: Venda não encontrada!',
                    'id' => 0
                ], 404);
            }
            
            // Atualizar a venda com os dados do pagamento
            $FieldAndValues = [
                'forma_pagamento' => $formaPagamento,
                'status' => 'FINALIZADA'
            ];
            
            // Adicionar observação do pagamento
            $obsPagamento = '';
            switch ($formaPagamento) {
                case 'PIX':
                    $obsPagamento = 'PIX - Chave: ' . ($paymentData['pixKey'] ?? 'N/A');
                    break;
                case 'CARTAO_CREDITO':
                    $obsPagamento = 'Cartão Crédito - ' . ($paymentData['parcelas'] ?? 1) . 'x';
                    break;
                case 'CARTAO_DEBITO':
                    $obsPagamento = 'Cartão Débito';
                    break;
                case 'DINHEIRO':
                    $troco = $valorPago - $valorTotal;
                    $obsPagamento = 'Dinheiro - Pago: R$ ' . number_format($valorPago, 2, ',', '.') . ' - Troco: R$ ' . number_format($troco, 2, ',', '.');
                    break;
                case 'BOLETO':
                    $obsPagamento = 'Boleto';
                    break;
            }
            
            $FieldAndValues['observacao'] = ($sale['observacao'] ?? '') . ' | Pagamento: ' . $obsPagamento;
            
            $isUpdated = UpdateQuery::table('sale')->set($FieldAndValues)->where('id', '=', $id)->update();
            
            if (!$isUpdated) {
                return $this->SendJson($response, [
                    'status' => false,
                    'msg' => 'Restrição: Erro ao finalizar venda!',
                    'id' => 0
                ], 500);
            }
            
            // Registrar saída de estoque para cada item da venda
            $this->registrarSaidaEstoque($id);
            
            return $this->SendJson($response, [
                'status' => true,
                'msg' => 'Venda finalizada com sucesso!',
                'id' => $id,
                'pagamento' => $paymentData
            ]);
            
        } catch (\Exception $e) {
            return $this->SendJson($response, [
                'status' => false,
                'msg' => 'Erro ao finalizar venda: ' . $e->getMessage(),
                'id' => 0
            ], 500);
        }
    }

    /**
     * Registra a saída de estoque para cada item da venda
     */
    private function registrarSaidaEstoque($id_venda)
    {
        try {
            // Buscar todos os itens da venda
            $itens = SelectQuery::select('id_produto', 'quantidade')
                ->from('item_sale')
                ->where('id_venda', '=', $id_venda)
                ->fetchAll();

            foreach ($itens as $item) {
                $id_produto = $item['id_produto'];
                $quantidade = intval($item['quantidade']);

                if ($id_produto && $quantidade > 0) {
                    // Registrar movimento de saída no estoque
                    InsertQuery::table('stock_movement')->save([
                        'id_produto' => $id_produto,
                        'quantidade_entrada' => 0,
                        'quantidade_saida' => $quantidade,
                        'observacao' => 'Venda #' . $id_venda,
                        'tipo' => 'SAIDA',
                        'origem_movimento' => 'VENDA'
                    ]);
                }
            }

            return true;
        } catch (\Exception $e) {
            error_log('Erro ao registrar saída de estoque: ' . $e->getMessage());
            return false;
        }
    }
}

