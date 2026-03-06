<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ViewProductWithStock extends AbstractMigration
{

    public function up(): void
    {
        $this->execute("
            DROP VIEW IF EXISTS view_product;

CREATE OR REPLACE VIEW view_product AS
SELECT 
    p.id::TEXT,
    p.nome,
    p.codigo_barra,
    p.descricao_curta,
    p.valor,
    p.ativo,
    TRUE AS produto,
    COALESCE(SUM(sm.quantidade_entrada) - SUM(sm.quantidade_saida), 0) AS estoque
FROM public.product p
LEFT JOIN public.stock_movement sm ON sm.id_produto = p.id
WHERE p.excluido = FALSE
GROUP BY p.id, p.nome, p.codigo_barra, p.descricao_curta, p.valor, p.ativo;
        ");
    }
    public function down(): void
    {
        $this->execute("
            DROP VIEW IF EXISTS view_product;

CREATE OR REPLACE VIEW view_product AS
SELECT 
    p.id::TEXT,
    p.nome,
    p.codigo_barra,
    p.descricao_curta,
    p.valor,
    p.ativo,
    TRUE AS produto
FROM public.product p
WHERE p.excluido = FALSE ;
        ");
    }
}

