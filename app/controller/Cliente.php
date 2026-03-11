<?php

namespace app\controller;

use app\database\builder\InsertQuery;
use app\database\builder\DeleteQuery;
use app\database\builder\SelectQuery;
use app\database\builder\UpdateQuery;

class Cliente extends Base
{
    public function lista($request, $response)
    {
        try {
            $dadosTemplate = [
                'titulo' => 'Página inicial'
            ];
            return $this->getTwig()
                ->render($response, $this->setView('listcliente'), $dadosTemplate)
                ->withHeader('Content-Type', 'text/html')
                ->withStatus(200);
        } catch (\Exception $e) {
        }
    }
    public function cadastro($request, $response)
    {
        try {
            $dadosTemplate = [
                'acao' => 'c',
                'titulo' => 'Cadastro'
            ];
            return $this->getTwig()
                ->render($response, $this->setView('cliente'), $dadosTemplate)
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
            $FieldAndValues = [
                'nome_fantasia' => $form['nome_fantasia'],
                'sobrenome_razao' => $form['sobrenome_razao'],
                'cpf_cnpj' => $form['cpf_cnpj'],
                'rg_ie' => $form['rg_ie'],
                'ativo' => $form['ativo']
            ];
            $IsSave = InsertQuery::table('customer')->save($FieldAndValues);
            if (!$IsSave) {
                return $this->SendJson($response, ['status' => false, 'msg' => 'Restrição: ' . $IsSave, 'id' => 0], 403);
            }
            $customer = SelectQuery::select('id')->from('customer')->order('id', 'desc')->fetch();
            return $this->SendJson($response, ['status' => true, 'msg' => 'Salvo com sucesso', 'id' => $customer['id']], 201);
        } catch (\Exception $e) {
            return $this->SendJson($response, ['status' => false, 'msg' => 'Restrição: ' . $e->getMessage(), 'id' => 0], 500);
        }
    }
    public function alterar($request, $response, $args)
    {
        try {
            $id = $args['id'];
            $customer = SelectQuery::select()->from('customer')->where('id', '=', $id)->fetch();
            $dadosTemplate = [
                'acao' => 'e',
                'id' => $id,
                'titulo' => 'Cadastro e edição',
                'customer' => $customer
            ];
            return $this->getTwig()
                ->render($response, $this->setView('cliente'), $dadosTemplate)
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
            $IsDelete = DeleteQuery::table('customer')
                ->where('id', '=', $id)
                ->delete();

            if (!$IsDelete) {
                echo json_encode(['status' => false, 'msg' => $IsDelete, 'id' => $id]);
                die;
            }
            echo json_encode(['status' => true, 'msg' => 'Removido com sucesso!', 'id' => $id]);
            die;
        } catch (\Throwable $th) {
            echo json_encode(['status' => false, 'msg' => "Erro: " . $th->getMessage(), 'id' => $id]);
            die;
        }
    }
    public function listcliente($request, $response)
    {
        try {

            $form = $request->getParsedBody() ?? [];

            $draw = intval($form['draw'] ?? 1);
            $start = intval($form['start'] ?? 0);
            $length = intval($form['length'] ?? 10);

            $order = $form['order'][0]['column'] ?? 0;
            $orderType = $form['order'][0]['dir'] ?? 'asc';

            $fields = [
                0 => 'id',
                1 => 'nome_fantasia',
                2 => 'sobrenome_razao',
                3 => 'cpf_cnpj',
                4 => 'rg_ie',
                5 => 'ativo',
            ];

            $orderField = $fields[$order] ?? 'id';

            $term = $form['search']['value'] ?? '';

            $recordsTotal = SelectQuery::select('COUNT(id) as total')
                ->from('customer')
                ->fetch()['total'];

            $query = SelectQuery::select('id,nome_fantasia,sobrenome_razao,cpf_cnpj,rg_ie,ativo')
                ->from('customer');

            if (!empty($term)) {
                $query
                    ->where('nome_fantasia', 'like', "%{$term}%")
                    ->where('sobrenome_razao', 'like', "%{$term}%", 'or')
                    ->where('cpf_cnpj', 'like', "%{$term}%", 'or')
                    ->where('rg_ie', 'like', "%{$term}%", 'or');
            }

            $customers = $query
                ->order($orderField, $orderType)
                ->limit($length, $start)
                ->fetchAll() ?? [];

            $recordsFiltered = count($customers);

            $customersData = [];

            foreach ($customers as $value) {

                $customersData[] = [
                    $value['id'],
                    $value['nome_fantasia'],
                    $value['sobrenome_razao'],
                    $value['cpf_cnpj'],
                    $value['rg_ie'],
                    $value['ativo'] ? 'Sim' : 'Não',
                    "<a href='/cliente/alterar/{$value['id']}' class='btn btn-warning'>
                Alterar
                </a>
                <button onclick='Delete({$value['id']})' class='btn btn-danger'>
                Excluir
                </button>"
                ];
            }

            $data = [
                "draw" => $draw,
                "recordsTotal" => intval($recordsTotal),
                "recordsFiltered" => intval($recordsTotal),
                "data" => $customersData
            ];

            $response->getBody()->write(json_encode($data));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {

            $response->getBody()->write(json_encode([
                "error" => $e->getMessage()
            ]));

            return $response->withHeader('Content-Type', 'application/json');
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
            $FieldAndValues = [
                'nome_fantasia' => $form['nome_fantasia'],
                'sobrenome_razao' => $form['sobrenome_razao'],
                'cpf_cnpj' => $form['cpf_cnpj'],
                'rg_ie' => $form['rg_ie'],
                'ativo' => $form['ativo'],
            ];
            $IsUpdate = UpdateQuery::table('customer')->set($FieldAndValues)->where('id', '=', $id)->update();
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
            // Busca todos os clientes usando sua SelectQuery
            // Selecionei as colunas baseadas no que o seu HTML original pedia
            $customers = SelectQuery::select('id, nome_fantasia, sobrenome_razao, cpf_cnpj, rg_ie')
                ->from('customer')
                ->order('nome_fantasia', 'ASC')
                ->order('sobrenome_razao', 'ASC')
                ->order('cpf_cnpj', 'ASC')
                ->order('rg_ie', 'ASC')
                ->fetchAll();

            $dadosTemplate = [
                'titulo'   => 'Relatório de Clientes',
                'clientes' => $customers,
                'total'    => count($customers)
            ];

            // Renderiza o template passando os dados
            // Nota: Certifique-se que o método render do seu Base aceita esses parâmetros
            return $this->getTwig()
                ->render($response, $this->setView('reports/reportcliente'), $dadosTemplate)
                ->withHeader('Content-Type', 'text/html')
                ->withStatus(200);
        } catch (\Exception $e) {
            $response->getBody()->write("Erro ao gerar relatório: " . $e->getMessage());
            return $response->withStatus(500);
        }
    }
}
