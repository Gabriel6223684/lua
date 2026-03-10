<?php

namespace app\controller;

use app\database\builder\InsertQuery;
use app\database\builder\SelectQuery;
use app\database\builder\UpdateQuery;

class AdjustmentStock extends Base
{

    public function lista($request, $response)
    {
        $dadosTemplate = [
            'titulo' => 'Lista de Produtos'
        ];
        return $this->getTwig()
            ->render($response, $this->setView('listadjustmentstock'), $dadosTemplate)
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(200);
    }
    public function cadastro($request, $response)
    {
        try {
            $dadosTemplate = [
                'acao' => 'c',
                'titulo' => 'Cadastro'
            ];
            return $this->getTwig()
                ->render($response, $this->setView('adjustmentstock'), $dadosTemplate)
                ->withHeader('Content-Type', 'text/html')
                ->withStatus(200);
        } catch (\Exception $e) {
            var_dump($e);
        }
    }
    public function listajusteestoque($request, $response)
    {
        #Captura todas a variaveis de forma mais segura VARIAVEIS POST.
        $form = $request->getParsedBody();
        #Qual a coluna da tabela deve ser ordenada.
        $order = $form['order'][0]['column'];
        #Tipo de ordenação
        $orderType = $form['order'][0]['dir'];
        #Em qual registro se inicia o retorno dos registros, OFFSET
        $start = $form['start'];
        #Limite de registro a serem retornados do banco de dados LIMIT
        $length = $form['length'];
        $fields = [
            0 => 'id',
            1 => 'nome',
            2 => 'descricao_curta',
            3 => 'codigo_barra',
            4 => 'valor',
        ];
        #Capturamos o nome do campo a ser odernado.
        $orderField = $fields[$order];
        #O termo pesquisado
        $term = $form['search']['value'];
        
        // Primeiro, conta o total de registros (sem filtros)
        $totalQuery = SelectQuery::select('id')->from('view_product');
        $recordsTotal = $totalQuery->count();
        
        // Query com filtros para dados
        $query = SelectQuery::select()->from('view_product');
        if (!is_null($term) && ($term !== '')) {
            $query
                ->where('id', 'ilike', "%{$term}%")
                ->where('nome', 'ilike', "%{$term}%", 'or')
                ->where('descricao_curta', 'ilike', "%{$term}%", 'or')
                ->where('codigo_barra', 'ilike', "%{$term}%", 'or')
                ->where('valor', 'ilike', "%{$term}%", 'or');        
        }
        
        // Conta registros filtrados
        $filteredQuery = clone $query;
        $recordsFiltered = $filteredQuery->count();
        
        // Busca os dados paginados
        $product = $query
            ->order($orderField, $orderType)
            ->limit($length, $start)
            ->fetchAll();
            
        $produtoData = [];
        foreach ($product as $key => $value) {
            $produtoData[$key] = [
                $value['id'],
                $value['nome'],
                $value['descricao_curta'],
                $value['codigo_barra'],
                $value['valor'],
                "<div class='d-flex gap-2'>
                    <button type='button' class='btn btn-primary btn-sm px-2 shadow-sm' style='white-space: nowrap; font-weight: 500;' data-bs-toggle='modal' data-bs-target='#modalstock{$value['id']}'>
                        <i class='bi bi-plus-circle'></i> Ajustar
                    </button>
                    <button type='button' onclick='Delete({$value['id']});' class='btn btn-danger btn-sm px-2 shadow-sm' style='white-space: nowrap; font-weight: 500;'>
                        <i class='bi bi-trash-fill'></i> Excluir
                    </button>
                </div>
                <div class='modal fade' id='modalstock{$value['id']}' tabindex='-1' aria-labelledby='exampleModalLabel' aria-hidden='true'>
                    <div class='modal-dialog'>
                        <div class='modal-content'>
                            <div class='modal-header'>
                                <h1 class='modal-title fs-5' id='exampleModalLabel'>Ajuste Estoque - {$value['nome']}</h1>
                                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                            </div>
                            <div class='modal-body'>
                                <div class='form-floating mb-3'>
                                    <input type='text' class='form-control' id='quantidade_ajuste_{$value['id']}' placeholder='Quantidade' autofocus>
                                    <label for='quantidade_ajuste_{$value['id']}'>Nova Quantidade</label>
                                </div>
                                <div class='form-floating mb-3'>
                                    <input type='text' class='form-control' id='quantidade_atual_{$value['id']}' placeholder='Quantidade Atual' value='{$value['estoque']}' disabled>
                                    <label for='quantidade_atual_{$value['id']}'>Quantidade Atual</label>
                                </div>
                                <div class='form-floating mb-3'>
                                    <button onclick='ajustarEstoque({$value['id']});' type='button' class='btn btn-warning'>Alterar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>"
            ];
        }

        $data = [
            'status' => true,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $produtoData
        ];
        
        $payload = json_encode($data);
        $response->getBody()->write($payload);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function ajustarestoque($request, $response)
    {
        try {
            $form = $request->getParsedBody();
            $id_produto = $form['id'] ?? null;
            $nova_quantidade = $form['quantidade'] ?? null;

            if (empty($id_produto) || is_null($id_produto)) {
                return $this->SendJson($response, [
                    'status' => false,
                    'msg' => 'Restrição: O ID do produto é obrigatório!',
                    'id' => 0
                ], 403);
            }

            if (empty($nova_quantidade) || is_null($nova_quantidade)) {
                return $this->SendJson($response, [
                    'status' => false,
                    'msg' => 'Restrição: A nova quantidade é obrigatória!',
                    'id' => 0
                ], 403);
            }

            // Buscar a quantidade atual do produto
            $produto = SelectQuery::select('estoque')
                ->from('product')
                ->where('id', '=', $id_produto)
                ->fetch();

            if (!$produto) {
                return $this->SendJson($response, [
                    'status' => false,
                    'msg' => 'Restrição: Produto não encontrado!',
                    'id' => 0
                ], 403);
            }

            $quantidade_anterior = $produto['estoque'] ?? 0;
            $diferenca = intval($nova_quantidade) - intval($quantidade_anterior);

            // Inserir movimento de estoque
            InsertQuery::table('stock_movement')->save([
                'id_product' => $id_produto,
                'quantity' => $diferenca,
                'type' => 'AJUSTE',
                'observation' => 'Ajuste manual de estoque'
            ]);

            // Atualizar quantidade do produto
            UpdateQuery::table('product')
                ->set(['estoque' => intval($nova_quantidade)])
                ->where('id', '=', $id_produto)
                ->update();

            return $this->SendJson($response, [
                'status' => true,
                'msg' => 'Estoque atualizado com sucesso!',
                'id' => $id_produto
            ], 200);

        } catch (\Exception $e) {
            return $this->SendJson($response, [
                'status' => false,
                'msg' => 'Erro: ' . $e->getMessage(),
                'id' => 0
            ], 500);
        }
    }

    public function print($request, $response)
    {
        try {
            $products = SelectQuery::select()
                ->from('view_product')
                ->order('id', 'DESC')
                ->fetchAll();

            $dadosTemplate = [
                'titulo' => 'Relatório de Produtos com Estoque',
                'produtos' => $products,
                'total' => count($products)
            ];

            return $this->getTwig()
                ->render($response, $this->setView('reports/reportproduto'), $dadosTemplate)
                ->withHeader('Content-Type', 'text/html')
                ->withStatus(200);

        } catch (\Exception $e) {
            $response->getBody()->write("Erro ao gerar relatório: " . $e->getMessage());
            return $response->withStatus(500);
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
                    'msg' => 'Restrição: O ID é obrigatório!',
                    'id' => 0
                ], 403);
            }

            // Verificar se o produto existe
            $produto = SelectQuery::select('id')
                ->from('product')
                ->where('id', '=', $id)
                ->fetch();

            if (!$produto) {
                return $this->SendJson($response, [
                    'status' => false,
                    'msg' => 'Restrição: Produto não encontrado!',
                    'id' => 0
                ], 403);
            }

            // Excluir movimentos de estoque relacionados
            UpdateQuery::table('stock_movement')
                ->set(['id_product' => null])
                ->where('id_product', '=', $id)
                ->update();

            // Excluir o produto
            UpdateQuery::table('product')
                ->set(['status' => 'I'])
                ->where('id', '=', $id)
                ->update();

            return $this->SendJson($response, [
                'status' => true,
                'msg' => 'Produto excluído com sucesso!',
                'id' => $id
            ], 200);

        } catch (\Exception $e) {
            return $this->SendJson($response, [
                'status' => false,
                'msg' => 'Erro: ' . $e->getMessage(),
                'id' => 0
            ], 500);
        }
    }
}
