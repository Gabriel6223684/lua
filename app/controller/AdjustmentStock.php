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
        $form = $request->getParsedBody() ?? [];

        $draw = intval($form['draw'] ?? 1);
        $start = intval($form['start'] ?? 0);
        $length = intval($form['length'] ?? 10);

        $order = $form['order'][0]['column'] ?? 0;
        $orderType = $form['order'][0]['dir'] ?? 'asc';

        $fields = [
            0 => 'id',
            1 => 'nome',
            2 => 'descricao_curta',
            3 => 'codigo_barra',
            4 => 'valor',
        ];

        $orderField = $fields[$order] ?? 'id';

        $term = $form['search']['value'] ?? '';

        $recordsTotal = SelectQuery::select('id')
            ->from('view_product')
            ->count();

        $query = SelectQuery::select()
            ->from('view_product');

        if (!empty($term)) {
            $query
                ->where('id', 'ilike', "%{$term}%")
                ->where('nome', 'ilike', "%{$term}%", 'or')
                ->where('descricao_curta', 'ilike', "%{$term}%", 'or')
                ->where('codigo_barra', 'ilike', "%{$term}%", 'or')
                ->where('valor', 'ilike', "%{$term}%", 'or');
        }

        $filteredQuery = clone $query;
        $recordsFiltered = $filteredQuery->count();

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
                "<button onclick='ajustarEstoque({$value['id']});' class='btn btn-primary'>Ajustar</button>"
            ];
        }

        $data = [
            "draw" => $draw,
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $produtoData
        ];

        $response->getBody()->write(json_encode($data));

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
