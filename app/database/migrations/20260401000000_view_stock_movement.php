<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ViewStockMovement extends AbstractMigration
{

    public function up(): void
    {
        $this->execute("
            DROP VIEW IF EXISTS view_stock_movement;

CREATE OR REPLACE VIEW view_stock_movement AS
WITH movimento_ordenado AS (
    SELECT 
        sm.id,
        sm.id_produto,
        p.nome AS nome_produto,
        p.codigo_barra AS codigo_barra,
        sm.quantidade_entrada,
        sm.quantidade_saida,
        CASE 
            WHEN sm.tipo = 'ENTRADA' THEN 'ENTRADA'
            WHEN sm.tipo = 'SAIDA' THEN 'SAIDA'
            ELSE 'AJUSTE'
        END AS tipo_movimento,
        sm.tipo AS tipo,
        sm.origem_movimento,
        sm.observacao,
        sm.data_cadastro,
        ROW_NUMBER() OVER (ORDER BY sm.data_cadastro ASC, sm.id ASC) AS row_num
    FROM stock_movement sm
    LEFT JOIN product p ON p.id = sm.id_produto
    WHERE p.excluido = FALSE
)
SELECT 
    mo.id::TEXT AS id,
    mo.id_produto::TEXT AS id_produto,
    mo.nome_produto,
    mo.codigo_barra,
    mo.quantidade_entrada,
    mo.quantidade_saida,
    mo.tipo_movimento,
    mo.tipo,
    mo.origem_movimento,
    mo.observacao,
    mo.data_cadastro,
    COALESCE(
        (SELECT SUM(m2.quantidade_entrada - m2.quantidade_saida) 
         FROM movimento_ordenado m2 
         WHERE m2.row_num < mo.row_num AND m2.id_produto = mo.id_produto), 0
    ) AS estoque_anterior,
    COALESCE(
        (SELECT SUM(m2.quantidade_entrada - m2.quantidade_saida) 
         FROM movimento_ordenado m2 
         WHERE m2.row_num <= mo.row_num AND m2.id_produto = mo.id_produto), 0
    ) AS estoque_atual
FROM movimento_ordenado mo
ORDER BY mo.data_cadastro ASC, mo.id ASC;
        ");
    }

    public function down(): void
    {
        $this->execute("
            DROP VIEW IF EXISTS view_stock_movement;
        ");
    }
}

