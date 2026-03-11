# TODO - Correção DataTables

## Objetivo
Corrigir URLs de linguagem do DataTables para versão 2.3.7

## Tarefas
- [x] Corrigir listestoque.js
- [x] Corrigir listsale.js
- [x] Corrigir listacliente.js
- [x] Corrigir listaempresa.js
- [x] Corrigir listafornecedor.js
- [x] Corrigir listauser.js
- [x] Corrigir listaproduto.js
- [x] Corrigir listpaymentterms.js

## Conclusão
- [x] Todas as correções foram aplicadas

---

## Correções Adicionais no Backend

### SelectQuery.php
- Corrigido método `where()` para não adicionar lógica AND/OR na primeira condição
- Adicionado sistema de geração de nomes únicos para placeholders (evita conflitos)

### Connection.php
- Adicionado charset UTF-8 na conexão PDO

---

# TODO - Sistema Profissional de Estoque

## Objetivo
Implementar sistema de estoque com entrada e saída

## Tarefas
- [x] Criar migration para view de movimentações (view_stock_movement)
- [x] Criar controller Stock.php com endpoints
- [x] Criar view liststock.html
- [x] Criar JavaScript liststock.js
- [x] Adicionar rotas no route.php
- [x] Adicionar link no menu lateral

## Conclusão
- [x] Sistema de estoque profissional implementado

