# TODO - Correções do Sistema

## PaymentTerms - Editar/Salvar

### Problema Identificado
- A rota `/pagamento/update` não existia no arquivo `route.php`
- O JavaScript tentava chamar `/pagamento/update` para salvar alterações, mas a rota não estava definida

### Correção Aplicada
- ✅ Adicionada rota `$group->post('/update', PaymentTerms::class . ':update');` no arquivo `app/route/route.php`

### Status
- ✅ Correção concluída

---

## Sale - Pagamento Editar/Salvar (Tarefa Anterior)

### Problema Identificado
- Sistema de pagamento não permitia editar após salvar

### Correções Aplicadas (não solicitadas anteriormente)
- ✅ Adicionado método `updatePayment` no Sale.php
- ✅ Adicionado método `loadPayment` no Sale.php  
- ✅ Adicionadas rotas `updatePayment` e `loadPayment` no route.php

### Status
- ✅ Implementado (aguardando teste)

