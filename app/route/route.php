<?php

use app\controller\User;
use app\controller\Cliente;
use app\controller\Login;
use app\controller\Empresa;
use app\controller\Fornecedor;
use app\controller\Home;
use app\controller\PaymentTerms;
use app\controller\AdjustmentStock;
use app\controller\Produto;
use app\controller\Sale;
use Slim\Routing\RouteCollectorProxy;

$app->get('/', Home::class . ':home');
$app->get('/home', Home::class . ':home');

$app->get('/login', Login::class . ':login');

$app->group('/home', function (RouteCollectorProxy $group) {
});

$app->group('/venda', function (RouteCollectorProxy $group) {
    $group->get('/lista', Sale::class . ':lista');
    $group->get('/cadastro', Sale::class . ':cadastro');
    $group->get('/alterar/{id}', Sale::class . ':alterar');
    $group->get('/print', Sale::class . ':print');
    $group->post('/insert', Sale::class . ':insert');
    $group->post('/update', Sale::class . ':update');
    $group->post('/insertitem', Sale::class . ':insertitem');
    $group->post('/listitemsale', Sale::class . ':listitemsale');
    $group->post('/listsale', Sale::class . ':listsale');
    $group->post('/delete', Sale::class . ':delete');
    $group->post('/deleteitem', Sale::class . ':deleteitem');
    $group->post('/updateitem', Sale::class . ':updateItem');
});

$app->group('/login', function (RouteCollectorProxy $group) {
    $group->post('/precadastro', Login::class . ':precadastro');
    $group->post('/autenticar', Login::class . ':autenticar');
});

$app->group('/usuario', function (RouteCollectorProxy $group) {
    $group->get('/lista', User::class . ':lista');
    $group->get('/cadastro', User::class . ':cadastro');
    $group->get('/alterar/{id}', User::class . ':alterar');
    $group->get('/print', User::class . ':print');
    $group->post('/update', User::class . ':update');
    $group->post('/listuser', User::class . ':listuser');
    $group->post('/insert', User::class . ':insert');
    $group->post('/delete', User::class . ':delete');
});

$app->group('/cliente', function (RouteCollectorProxy $group) {
    $group->get('/lista', Cliente::class . ':lista');
    $group->get('/cadastro', Cliente::class . ':cadastro');
    $group->get('/alterar/{id}', Cliente::class . ':alterar');
    $group->get('/print', Cliente::class . ':print');
    $group->post('/update', Cliente::class . ':update');
    $group->post('/listcliente', Cliente::class . ':listcliente');
    $group->post('/insert', Cliente::class . ':insert');
    $group->post('/delete', Cliente::class . ':delete');
});

$app->group('/empresa', function (RouteCollectorProxy $group) {
    $group->get('/lista', Empresa::class . ':lista');
    $group->get('/cadastro', Empresa::class . ':cadastro');
    $group->get('/alterar/{id}', Empresa::class . ':alterar');
    $group->get('/print', Empresa::class . ':print');
    $group->post('/update', Empresa::class . ':update');
    $group->post('/listempresa', Empresa::class . ':listempresa');
    $group->post('/insert', Empresa::class . ':insert');
    $group->post('/delete', Empresa::class . ':delete');
});

$app->group('/fornecedor', function (RouteCollectorProxy $group) {
    $group->get('/lista', Fornecedor::class . ':lista');
    $group->get('/cadastro', Fornecedor::class . ':cadastro');
    $group->get('/alterar/{id}', Fornecedor::class . ':alterar');
    $group->get('/print', Fornecedor::class . ':print');
    $group->post('/update', Fornecedor::class . ':update');
    $group->post('/listfornecedor', Fornecedor::class . ':listfornecedor');
    $group->post('/insert', Fornecedor::class . ':insert');
    $group->post('/delete', Fornecedor::class . ':delete');
});

$app->group('/produto', function (RouteCollectorProxy $group) {
    $group->get('/lista', Produto::class . ':lista');
    $group->get('/cadastro', Produto::class . ':cadastro');
    $group->get('/alterar/{id}', Produto::class . ':alterar');
    $group->get('/print', Produto::class . ':print');
    $group->post('/update', Produto::class . ':update');
    $group->post('/listproduto', Produto::class . ':listproduto');
    $group->post('/insert', Produto::class . ':insert');
    $group->post('/delete', Produto::class . ':delete');
    $group->post('/listproductdata', Produto::class . ':listproductdata');
    $group->post('/listproductall', Produto::class . ':listproductall');
    $group->post('/adjuststock', Produto::class . ':adjuststock');
});

$app->group('/pagamento', function (RouteCollectorProxy $group) {
    $group->get('/lista', PaymentTerms::class . ':lista');
    $group->get('/cadastro', PaymentTerms::class . ':cadastro');
    $group->get('/alterar/{id}', PaymentTerms::class . ':alterar');
    $group->post('/insert', PaymentTerms::class . ':insert');
    $group->post('/listpagamento', PaymentTerms::class . ':listpagamento');
    $group->post('/listapaymentterms', PaymentTerms::class . ':listapaymentterms');
    $group->post('/insertinstallment', PaymentTerms::class . ':insertInstallment');
    $group->post('/loaddatainstallments', PaymentTerms::class . ':loaddatainstallments');
    $group->post('/deleteinstallment', PaymentTerms::class . ':deleteinstallment');
    $group->post('/delete', PaymentTerms::class . ':delete');
});

$app->group('/ajusteestoque', function (RouteCollectorProxy $group) {
    $group->get('/lista', AdjustmentStock::class . ':lista');
    $group->get('/cadastro', AdjustmentStock::class . ':cadastro');
    $group->get('/print', AdjustmentStock::class . ':print');
    $group->post('/listajusteestoque', AdjustmentStock::class . ':listajusteestoque');
    $group->post('/ajustar', AdjustmentStock::class . ':ajustarestoque');
    $group->post('/delete', AdjustmentStock::class . ':delete');
});
