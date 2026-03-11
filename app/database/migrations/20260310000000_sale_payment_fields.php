<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SalePaymentFields extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('sale');
        
        // Check if columns don't exist before adding
        if (!$table->hasColumn('forma_pagamento')) {
            $table->addColumn('forma_pagamento', 'string', [
                'limit' => 50,
                'null' => true,
                'after' => 'total_liquido',
                'comment' => 'Forma de pagamento: DINHEIRO, PIX, CARTAO_CREDITO, CARTAO_DEBITO, BOLETO'
            ]);
        }
        
        if (!$table->hasColumn('status')) {
            $table->addColumn('status', 'string', [
                'limit' => 20,
                'null' => true,
                'after' => 'forma_pagamento',
                'comment' => 'Status da venda: PENDENTE, FINALIZADA, CANCELADA'
            ]);
        }
        
        $table->save();
    }
}
