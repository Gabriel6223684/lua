<?php

namespace app\controller;

use app\database\builder\SelectQuery;

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
            3 => 'descricao_curta',
            2 => 'codigo_barra',
            4 => 'valor',
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
            $produtoData[$key] = [
                $value['id'],
                $value['nome'],
                $value['descricao_curta'],
                $value['codigo_barra'],
                $value['valor'],
                "<div class='d-flex gap-2'>
                    <button type='button' class='btn btn-primary btn-sm px-2 shadow-sm' style='white-space: nowrap; font-weight: 500;' data-bs-toggle='modal' data-bs-target='#modalstock'>
                        <i class='bi bi-plus-circle'></i> Ajustar
                    </button>

                <div class='modal fade' id='modalstock' tabindex='-1' aria-labelledby='exampleModalLabel' aria-hidden='true'>
                    <div class='modal-dialog'>
                        <div class='modal-content'>
                            <div class='modal-header'>
                                <h1 class='modal-title fs-5' id='exampleModalLabel'>Ajuste Estoque</h1>
                                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                        </div>
                            <div class='modal-body'>
                                <div class='form-floating mb-3'>
                                    <input type='text' class='form-control' id='floatingInput' placeholder='Quantidade' autofocus>
                                    <label for='floatingInput'>Nova Quantidade</label>
                            </div>
                            <div class='form-floating mb-3'>
                                <input type='email' class='form-control' id='floatingInput' placeholder='name@example.com' disabled>
                                <label for='floatingInput'>Quantidade Atual</label>
                            </div>
                            <div class='form-floating mb-3'>
                                <button id='ajustestoque' type='button' class='btn btn-warning'>Alterar</button>
                            </div>
                        </div>
                    </div>
                </div>       
            </div>

                <button type='button' onclick='Delete({$value['id']});' class='btn btn-danger btn-sm px-2 shadow-sm' style='white-space: nowrap; font-weight: 500;'>
                <i class='bi bi-trash-fill'></i> Excluir
                </button>
                </div>"
                ];
                }
                
                
                
            //<div class='d-flex gap-2'>
            // <a href='/produto/alterar/{$value['id']}' class='btn btn-warning btn-sm px-2 shadow-sm' style='white-space: nowrap; font-weight: 500;'>
            //   <i class='bi bi-pencil-square'></i> Alterar
            // </a>



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
}