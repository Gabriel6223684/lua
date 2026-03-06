# TODO - Completar Sistema de Vendas

## Tarefas executadas:

### 1. Adicionar endpoint para listar todos os produtos (Produto.php)
- [x] Criar método `listproductall` no controller Produto
- [x] Retornar todos os produtos sem paginação

### 2. Adicionar nova rota (route.php)
- [x] Adicionar rota POST `/produto/listproductall`
- [x] Adicionar rota POST `/venda/updateitem`

### 3. Atualizar view sale.html
- [x] Adicionar tabela de produtos no modal de pesquisa
- [x] Adicionar campo de busca/filtragem
- [x] Adicionar estrutura HTML necessária
- [x] Adicionar coluna Quantidade na tabela de itens

### 4. Atualizar sale.js
- [x] Implementar função para carregar produtos via AJAX
- [x] Implementar função de filtragem/busca
- [x] Implementar seleção de produto ao clicar
- [x] Verificar se produto já está no carrinho antes de adicionar
- [x] Implementar alteração de quantidade dos itens
- [x] Criar venda sem necessidade de produto selecionado

### 5. Corrigir bugs no Sale.php
- [x] Corrigir paginação na listagem de vendas
- [x] Adicionar WHERE no método update()
- [x] Corrigir método deleteitem()
- [x] Adicionar método updateItem() para alterar quantidade
- [x] Remover validação desnecessária no método insert()
- [x] Tratar valores nulos nos totais
- [x] Adicionar método atualizarTotaisVenda()
- [x] Atualizar totais após inserir, atualizar e excluir itens

