<?php
/**
 * models/relatorios.php
 * Consultas de leitura usadas no dashboard e no relatório de vendas.
 * Pedidos cancelados nunca entram nos valores de venda.
 */

require_once __DIR__ . '/../config/conexao.php';

class Relatorio
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::getInstance();
    }

    /* =====================================================
       PERÍODO (datas no formato AAAA-MM-DD)
       Padrão: do dia 1 do mês atual até hoje.
    ===================================================== */

    public static function periodo(?string $de, ?string $ate): array
    {
        $valida = function (?string $d) {
            $obj = $d ? DateTime::createFromFormat('Y-m-d', $d) : false;
            return ($obj && $obj->format('Y-m-d') === $d) ? $d : null;
        };

        $de  = $valida($de)  ?? date('Y-m-01');
        $ate = $valida($ate) ?? date('Y-m-d');

        if ($de > $ate) {
            [$de, $ate] = [$ate, $de];
        }

        return [$de, $ate];
    }

    // Condição SQL do período (usa 2 parâmetros posicionais: de, ate)
    private function filtro(): string
    {
        return "p.status <> 'cancelado'
                AND p.created_at >= ?
                AND p.created_at < DATE_ADD(?, INTERVAL 1 DAY)";
    }

    private function consulta(string $sql, array $params = []): array
    {
        $sth = $this->pdo->prepare($sql);
        $sth->execute($params);

        return $sth->fetchAll();
    }

    /* =====================================================
       DASHBOARD
    ===================================================== */

    public function resumoGeral(): array
    {
        return $this->pdo->query(
            "SELECT
                (SELECT COUNT(*) FROM games)       AS jogos,
                (SELECT COALESCE(SUM(estoque), 0) FROM games) AS unidades,
                (SELECT COUNT(*) FROM categorias)  AS categorias,
                (SELECT COUNT(*) FROM clientes)    AS clientes,
                (SELECT COUNT(*) FROM usuarios)    AS usuarios,
                (SELECT COUNT(*) FROM pedidos)     AS pedidos,
                (SELECT COUNT(*) FROM pedidos WHERE status = 'pendente') AS pendentes,
                (SELECT COALESCE(SUM(total), 0) FROM pedidos WHERE status <> 'cancelado') AS faturamento"
        )->fetch();
    }

    public function estoqueBaixo(int $limite = 5): array
    {
        return $this->consulta(
            "SELECT id, titulo, estoque FROM games WHERE estoque <= ? ORDER BY estoque ASC, titulo ASC LIMIT 8",
            [$limite]
        );
    }

    public function ultimosPedidos(int $n = 5): array
    {
        return $this->consulta(
            "SELECT p.id, p.total, p.status, p.created_at, c.nome AS cliente_nome
             FROM pedidos p JOIN clientes c ON c.id = p.cliente_id
             ORDER BY p.id DESC LIMIT " . (int)$n
        );
    }

    // Vendas dos últimos N dias (inclui dias sem venda, com zero)
    public function vendasUltimosDias(int $dias = 7): array
    {
        $ate = date('Y-m-d');
        $de  = date('Y-m-d', strtotime('-' . ($dias - 1) . ' days'));

        $mapa = [];
        foreach ($this->vendasPorDia($de, $ate) as $l) {
            $mapa[$l['dia']] = (float)$l['faturamento'];
        }

        $saida = [];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $dia = date('Y-m-d', strtotime("-$i days"));
            $saida[] = ['dia' => $dia, 'faturamento' => $mapa[$dia] ?? 0.0];
        }

        return $saida;
    }

    /* =====================================================
       RELATÓRIO DE VENDAS
    ===================================================== */

    public function totais(string $de, string $ate): array
    {
        $a = $this->consulta(
            "SELECT COUNT(*) AS pedidos, COALESCE(SUM(p.total), 0) AS faturamento
             FROM pedidos p WHERE " . $this->filtro(), [$de, $ate]
        )[0];

        $b = $this->consulta(
            "SELECT COALESCE(SUM(i.quantidade), 0) AS itens
             FROM pedido_itens i JOIN pedidos p ON p.id = i.pedido_id WHERE " . $this->filtro(), [$de, $ate]
        )[0];

        $a['itens']  = (int)$b['itens'];
        $a['ticket'] = $a['pedidos'] > 0 ? $a['faturamento'] / $a['pedidos'] : 0;

        return $a;
    }

    public function vendasPorDia(string $de, string $ate): array
    {
        return $this->consulta(
            "SELECT DATE(p.created_at) AS dia, COUNT(*) AS pedidos, SUM(p.total) AS faturamento
             FROM pedidos p WHERE " . $this->filtro() . "
             GROUP BY DATE(p.created_at) ORDER BY dia ASC", [$de, $ate]
        );
    }

    public function topJogos(string $de, string $ate, int $limite = 10): array
    {
        return $this->consulta(
            "SELECT i.titulo, SUM(i.quantidade) AS unidades, SUM(i.quantidade * i.preco_unitario) AS valor
             FROM pedido_itens i JOIN pedidos p ON p.id = i.pedido_id
             WHERE " . $this->filtro() . "
             GROUP BY i.titulo ORDER BY unidades DESC, valor DESC LIMIT " . (int)$limite, [$de, $ate]
        );
    }

    public function vendasPorCategoria(string $de, string $ate): array
    {
        return $this->consulta(
            "SELECT COALESCE(c.nome, 'Sem categoria') AS categoria,
                    SUM(i.quantidade) AS unidades, SUM(i.quantidade * i.preco_unitario) AS valor
             FROM pedido_itens i
             JOIN pedidos p ON p.id = i.pedido_id
             LEFT JOIN games g ON g.id = i.game_id
             LEFT JOIN categorias c ON c.id = g.categoria_id
             WHERE " . $this->filtro() . "
             GROUP BY COALESCE(c.nome, 'Sem categoria') ORDER BY valor DESC", [$de, $ate]
        );
    }

    public function vendasPorPagamento(string $de, string $ate): array
    {
        return $this->consulta(
            "SELECT p.pagamento, COUNT(*) AS pedidos, SUM(p.total) AS valor
             FROM pedidos p WHERE " . $this->filtro() . "
             GROUP BY p.pagamento ORDER BY valor DESC", [$de, $ate]
        );
    }

    // Todos os pedidos do período (inclusive cancelados) para exportar
    public function pedidosDoPeriodo(string $de, string $ate): array
    {
        return $this->consulta(
            "SELECT p.id, p.created_at, c.nome AS cliente_nome, c.email AS cliente_email,
                    p.pagamento, p.status, p.total,
                    (SELECT COALESCE(SUM(i.quantidade), 0) FROM pedido_itens i WHERE i.pedido_id = p.id) AS itens
             FROM pedidos p JOIN clientes c ON c.id = p.cliente_id
             WHERE p.created_at >= ? AND p.created_at < DATE_ADD(?, INTERVAL 1 DAY)
             ORDER BY p.id ASC", [$de, $ate]
        );
    }
}
