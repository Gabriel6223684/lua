<?php

namespace app\controller;

use app\database\builder\InsertQuery;
use app\database\builder\DeleteQuery;
use app\database\builder\SelectQuery;
use app\database\builder\UpdateQuery;

class Produto extends Base
{

    public function lista($request, $response)
    {
        $dadosTemplate = [
            'titulo' => 'Lista de Produtos'
        ];
        return $this->getTwig()
            ->render($response, $this->setView('listproduto'), $dadosTemplate)
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
                ->render($response, $this->setView('produto'), $dadosTemplate)
                ->withHeader('Content-Type', 'text/html')
                ->withStatus(200);
        } catch (\Exception $e) {
            var_dump($e);
        }
    }
    public function insert($request, $response)
    {
        try {
            $form = $request->getParsedBody();
            // Converte vírgula para ponto no valor (formato brasileiro para americano)
            $valor = str_replace(',', '.', $form['valor']);
            $FieldAndValues = [
                'nome' => $form['nome'],
                'codigo_barra' => $form['codigo_barra'],
                'descricao_curta' => $form['descricao_curta'],
                'valor' => $valor
            ];
            $IsSave = InsertQuery::table('product')->save($FieldAndValues);
            if (!$IsSave) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'Restrição: ' . $IsSave, 'id' => 0], 403);
            }
            $produto = SelectQuery::select('id')->from('product')->order('id', 'desc')->fetch();
            return $this->SendJson($response, ['status' => true, 'msg' => 'Salvo com sucesso', 'id' => $produto['id']], 201);
        } catch (\Exception $e) {
            return $this->SendJson($response, ['status' => false, 'msg' => 'Restrição: ' . $e->getMessage(), 'id' => 0], 500);
        }
    }
    public function listproductdata($request, $response)
    {
        $form = $request->getParsedBody();
        $term = $form['term'] ?? null;
        $query = SelectQuery::select('id, codigo_barra, nome')->from('product');
        if ($term != null) {
            $query->where('codigo_barra', 'ILIKE', "%{$term}%", 'or')
                ->where('nome', 'ILIKE', "%{$term}%");
        }
        $data = [];
        $results = $query->fetchAll();
        foreach ($results as $key => $item) {
            $data['results'][$key] = [
                'id' => $item['id'],
                'text' => $item['nome'] . ' - Cód. barra: ' . $item['codigo_barra']
            ];
        }
        #$data['pagination'] = ['more' => true];
        return $this->SendJson($response, $data);
    }
    
    /**
     * Lista todos os produtos para pesquisa no modal (sem paginação)
     * Usado no modal de pesquisa de produto (F4) na tela de vendas
     */
    public function listproductall($request, $response)
    {
        try {
            $form = $request->getParsedBody();
            $term = $form['search'] ?? '';
            
            // Busca produtos na view que tem estoque
            $query = SelectQuery::select('id, codigo_barra, nome, descricao_curta, valor, estoque')
                ->from('view_product')
                ->order('nome', 'ASC');
            
            // Se houver termo de busca, filtra
            if (!empty($term)) {
                $query->where('id', 'ilike', "%{$term}%", 'or')
                    ->where('nome', 'ilike', "%{$term}%", 'or')
                    ->where('codigo_barra', 'ilike', "%{$term}%", 'or')
                    ->where('descricao_curta', 'ilike', "%{$term}%");
            }
            
            $produtos = $query->fetchAll();
            
            $data = [
                'status' => true,
                'data' => $produtos,
                'total' => count($produtos)
            ];
            
            return $this->SendJson($response, $data);
            
        } catch (\Exception $e) {
            return $this->SendJson($response, [
                'status' => false,
                'msg' => 'Erro ao listar produtos: ' . $e->getMessage()
            ], 500);
        }
    }
    public function listproduto($request, $response)
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
            3 => 'descricao_curta',
            2 => 'codigo_barra',
            4 => 'estoque',
            5 => 'valor',
        ];
        #Capturamos o nome do campo a ser odernado.
        $orderField = $fields[$order];
        #O termo pesquisado
        $term = $form['search']['value'];
        $query = SelectQuery::select()->from('view_product');
        if (!is_null($term) && ($term !== '')) {
            $query
                ->where('id', 'ilike', "%{$term}%")
                ->where('nome', 'ilike', "%{$term}%", 'or')
                ->where('descricao_curta', 'ilike', "%{$term}%", 'or')
                ->where('codigo_barra', 'ilike', "%{$term}%", 'or')
                ->where('valor', 'ilike', "%{$term}%", 'or');
        }
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
                "<div class='d-flex gap-1'>
    <button type='button' onclick='AdjustStock({$value['id']}, {$estoque});' class='btn btn-primary btn-sm px-2 shadow-sm' style='white-space: nowrap; font-weight: 500;'>
        <i class='bi bi-box-seam'></i> Estoque
    </button>
    <a href='/produto/alterar/{$value['id']}' class='btn btn-warning btn-sm px-2 shadow-sm' style='white-space: nowrap; font-weight: 500;'>
        <i class='bi bi-pencil-square'></i> Alterar
    </a>
    <button type='button' onclick='Delete({$value['id']});' class='btn btn-danger btn-sm px-2 shadow-sm' style='white-space: nowrap; font-weight: 500;'>
        <i class='bi bi-trash-fill'></i> Excluir
    </button>
</div>"
            ];
        }
        $data = [
            'status' => true,
            'recordsTotal' => count($product),
            'recordsFiltered' => count($product),
            'data' => $produtoData
        ];
        $payload = json_encode($data);

        $response->getBody()->write($payload);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
    public function alterar($request, $response, $args)
    {
        try {
            $id = $args['id'];
            $produto = SelectQuery::select()->from('product')->where('id', '=', $id)->fetch();
            $dadosTemplate = [
                'acao' => 'e',
                'id' => $id,
                'titulo' => 'Cadastro e edição',
                'produto' => $produto
            ];
            return $this->getTwig()
                ->render($response, $this->setView('produto'), $dadosTemplate)
                ->withHeader('Content-Type', 'text/html')
                ->withStatus(200);
        } catch (\Exception $e) {
            var_dump($e);
        }
    }
    public function delete($request, $response)
    {
        try {
            $id = $_POST['id'];
            $IsDelete = UpdateQuery::table('product')
                ->set(['excluido' => true])
                ->where('id', '=', $id)
                ->update();
            if (!$IsDelete) {
                echo json_encode(['status' => false, 'msg' => $IsDelete, 'id' => $id]);
                die;
            }
            echo json_encode(['status' => true, 'msg' => 'Removido com sucesso!', 'id' => $id]);
            die;
        } catch (\Throwable $th) {
            echo "Erro: " . $th->getMessage();
            die;
        }
    }
    public function update($request, $response)
    {
        try {
            $form = $request->getParsedBody();
            $id = $form['id'];
            if (is_null($id) || empty($id)) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'Por favor informe o ID', 'id' => 0], 500);
            }
            // Converte vírgula para ponto no valor (formato brasileiro para americano)
            $valor = str_replace(',', '.', $form['valor']);
            $FieldAndValues = [
                'nome' => $form['nome'],
                'descricao_curta' => $form['descricao_curta'],
                'valor' => $valor
            ];
            $IsUpdate = UpdateQuery::table('product')->set($FieldAndValues)->where('id', '=', $id)->update();
            if (!$IsUpdate) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'Restrição: ' . $IsUpdate, 'id' => 0], 403);
            }
            return $this->SendJson($response, ['status' => true, 'msg' => 'Atualizado com sucesso!', 'id' => $id]);
        } catch (\Exception $e) {
            return $this->SendJson($response, ['status' => false, 'msg' => 'Restrição: ' . $e->getMessage(), 'id' => 0], 500);
        }
    }
    public function print($request, $response)
    {
        try {
            // Busca todos os produtos usando a view
            $produtos = SelectQuery::select('id, codigo_barra, nome, descricao_curta, valor, estoque')
                ->from('view_product')
                ->order('nome', 'ASC')
                ->order('codigo_barra', 'ASC')
                ->order('valor', 'ASC')
                ->fetchAll();

            $dadosTemplate = [
                'titulo'   => 'Relatório de Produtos',
                'produtos' => $produtos,
                'total'    => count($produtos)
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
    
    public function adjuststock($request, $response)
    {
        try {
            $form = $request->getParsedBody();
            $id = $form['id'];
            $novaQuantidade = $form['quantidade'];
            
            if (is_null($id) || empty($id)) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'Por favor informe o ID do produto', 'id' => 0], 500);
            }
            
            if (is_null($novaQuantidade) || $novaQuantidade === '' || $novaQuantidade < 0) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'Por favor informe uma quantidade válida', 'id' => 0], 500);
            }
            
            // Buscar o estoque atual
            $produto = SelectQuery::select('id')->from('view_product')->where('id', '=', $id)->fetch();
            if (!$produto) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'Produto não encontrado', 'id' => 0], 404);
            }
            
            // Buscar quantidade atual no estoque
            $estoqueAtual = SelectQuery::select('id_produto')
                ->from('stock_movement')
                ->where('id_produto', '=', $id)
                ->fetchAll();
            
            $quantidadeAtual = 0;
            foreach ($estoqueAtual as $mov) {
                $quantidadeAtual += floatval($mov['quantidade_entrada'] ?? 0) - floatval($mov['quantidade_saida'] ?? 0);
            }
            
            // Calcular a diferença
            $diferenca = intval($novaQuantidade) - $quantidadeAtual;
            
            // Registrar a movimentação de estoque
            $FieldAndValues = [
                'id_produto' => $id,
                'quantidade_entrada' => $diferenca > 0 ? $diferenca : 0,
                'quantidade_saida' => $diferenca < 0 ? abs($diferenca) : 0,
                'observacao' => 'Ajuste de estoque',
                'tipo' => $diferenca >= 0 ? 'ENTRADA' : 'SAIDA',
                'origem_movimento' => 'AJUSTE_MANUAL'
            ];
            
            $IsSave = InsertQuery::table('stock_movement')->save($FieldAndValues);
            
            if (!$IsSave) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'Erro ao ajustar estoque: ' . $IsSave, 'id' => 0], 403);
            }
            
            return $this->SendJson($response, ['status' => true, 'msg' => 'Estoque ajustado com sucesso!', 'id' => $id]);
            
        } catch (\Exception $e) {
            return $this->SendJson($response, ['status' => false, 'msg' => 'Erro ao ajustar estoque: ' . $e->getMessage(), 'id' => 0], 500);
        }
    }
}
