<?php
/**
 * models/pedidos.php
 * Classe responsável pela manipulação de pedidos no banco (CRUD).
 * Criar baixa o estoque; cancelar ou excluir um pedido devolve os jogos ao estoque.
 */

require_once __DIR__ . '/../config/conexao.php';

class Pedido
{
    const STATUS = [
        'pendente'  => 'Pendente',
        'pago'      => 'Pago',
        'enviado'   => 'Enviado',
        'entregue'  => 'Entregue',
        'cancelado' => 'Cancelado',
    ];

    const PAGAMENTOS = [
        'pix'    => 'Pix',
        'cartao' => 'Cartão de crédito',
        'boleto' => 'Boleto bancário',
    ];

    private $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::getInstance();
    }

    /* =====================================================
       VALIDAÇÃO
    ===================================================== */

    public function validar(string $pagamento, string $endereco): array
    {
        $erros = [];

        if (!isset(self::PAGAMENTOS[$pagamento])) {
            $erros[] = 'Escolha uma forma de pagamento.';
        }

        if (mb_strlen($endereco) < 10) {
            $erros[] = 'Informe o endereço de entrega completo.';
        } elseif (mb_strlen($endereco) > 500) {
            $erros[] = 'O endereço pode ter no máximo 500 caracteres.';
        }

        return $erros;
    }

    /* =====================================================
       CREATE
       $itens: lista de ['id', 'titulo', 'preco', 'quantidade'].
       Tudo roda em transação: se faltar estoque, nada é gravado.
    ===================================================== */

    public function criar(int $clienteId, array $itens, string $pagamento, string $endereco): int
    {
        if (!$itens) {
            throw new RuntimeException('O pedido precisa ter pelo menos um jogo.');
        }

        $this->pdo->beginTransaction();

        try {
            $total = 0;
            $baixa = $this->pdo->prepare(
                "UPDATE games SET estoque = estoque - ? WHERE id = ? AND estoque >= ?"
            );

            foreach ($itens as $i) {
                $baixa->execute([$i['quantidade'], $i['id'], $i['quantidade']]);

                if ($baixa->rowCount() === 0) {
                    throw new RuntimeException('Estoque insuficiente para "' . $i['titulo'] . '".');
                }

                $total += (float)$i['preco'] * (int)$i['quantidade'];
            }

            $sth = $this->pdo->prepare(
                "INSERT INTO pedidos (cliente_id, total, pagamento, endereco) VALUES (?, ?, ?, ?)"
            );
            $sth->execute([$clienteId, round($total, 2), $pagamento, $endereco]);
            $pedidoId = (int)$this->pdo->lastInsertId();

            $item = $this->pdo->prepare(
                "INSERT INTO pedido_itens (pedido_id, game_id, titulo, preco_unitario, quantidade)
                 VALUES (?, ?, ?, ?, ?)"
            );

            foreach ($itens as $i) {
                $item->execute([$pedidoId, $i['id'], $i['titulo'], $i['preco'], $i['quantidade']]);
            }

            $this->pdo->commit();

            return $pedidoId;

        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /* =====================================================
       READ
    ===================================================== */

    private function selectBase(): string
    {
        return "SELECT p.*, c.nome AS cliente_nome, c.email AS cliente_email, c.usuario_id AS cliente_usuario_id
                FROM pedidos p
                JOIN clientes c ON c.id = p.cliente_id";
    }

    // Se $usuarioId for informado, só retorna o pedido se for desse usuário (loja)
    public function buscarPorId($id, $usuarioId = null)
    {
        $sql    = $this->selectBase() . " WHERE p.id = ?";
        $params = [(int)$id];

        if ($usuarioId !== null) {
            $sql .= " AND c.usuario_id = ?";
            $params[] = (int)$usuarioId;
        }

        $sth = $this->pdo->prepare($sql);
        $sth->execute($params);
        $pedido = $sth->fetch();

        return $pedido ? $this->anexarItens([$pedido])[0] : false;
    }

    public function listarPorUsuario($usuarioId): array
    {
        $sth = $this->pdo->prepare($this->selectBase() . " WHERE c.usuario_id = ? ORDER BY p.id DESC");
        $sth->execute([(int)$usuarioId]);

        return $this->anexarItens($sth->fetchAll());
    }

    public function listarTodos(string $status = ''): array
    {
        $sql    = $this->selectBase();
        $params = [];

        if ($status !== '' && isset(self::STATUS[$status])) {
            $sql .= " WHERE p.status = ?";
            $params[] = $status;
        }

        $sth = $this->pdo->prepare($sql . " ORDER BY p.id DESC");
        $sth->execute($params);

        return $this->anexarItens($sth->fetchAll());
    }

    public function resumo(): array
    {
        return $this->pdo->query(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(status = 'pendente'), 0) AS pendentes,
                    COALESCE(SUM(CASE WHEN status <> 'cancelado' THEN total ELSE 0 END), 0) AS faturamento
             FROM pedidos"
        )->fetch();
    }

    private function anexarItens(array $pedidos): array
    {
        if (!$pedidos) {
            return [];
        }

        $ids = array_column($pedidos, 'id');
        $in  = implode(',', array_fill(0, count($ids), '?'));

        $sth = $this->pdo->prepare("SELECT * FROM pedido_itens WHERE pedido_id IN ($in) ORDER BY id");
        $sth->execute($ids);

        $porPedido = [];
        foreach ($sth->fetchAll() as $item) {
            $porPedido[$item['pedido_id']][] = $item;
        }

        foreach ($pedidos as &$p) {
            $p['itens'] = $porPedido[$p['id']] ?? [];
        }
        unset($p);

        return $pedidos;
    }

    /* =====================================================
       UPDATE
    ===================================================== */

    // Altera só o status
    public function atualizarStatus($id, string $novo): void
    {
        if (!isset(self::STATUS[$novo])) {
            throw new RuntimeException('Status inválido.');
        }

        $this->pdo->beginTransaction();

        try {
            $this->trocarStatus((int)$id, $novo);
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // Edita forma de pagamento, endereço e status (os itens não mudam:
    // para alterar itens, cancele o pedido e crie outro)
    public function editar($id, string $pagamento, string $endereco, string $status): void
    {
        $erros = $this->validar($pagamento, $endereco);

        if (!isset(self::STATUS[$status])) {
            $erros[] = 'Status inválido.';
        }

        if ($erros) {
            throw new RuntimeException(implode(' ', $erros));
        }

        $this->pdo->beginTransaction();

        try {
            $atual = $this->statusAtualBloqueado((int)$id);

            if ($atual === 'cancelado') {
                throw new RuntimeException('Um pedido cancelado não pode ser editado.');
            }

            $this->pdo->prepare("UPDATE pedidos SET pagamento = ?, endereco = ? WHERE id = ?")
                ->execute([$pagamento, $endereco, (int)$id]);

            $this->trocarStatus((int)$id, $status);

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /* =====================================================
       DELETE  (se o pedido não estava cancelado, devolve o estoque)
    ===================================================== */

    public function deletar($id): void
    {
        $this->pdo->beginTransaction();

        try {
            $atual = $this->statusAtualBloqueado((int)$id);

            if ($atual !== 'cancelado') {
                $this->devolverEstoque((int)$id);
            }

            $this->pdo->prepare("DELETE FROM pedidos WHERE id = ?")->execute([(int)$id]);

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /* =====================================================
       APOIO (sempre chamados dentro de uma transação)
    ===================================================== */

    private function statusAtualBloqueado(int $id): string
    {
        $sth = $this->pdo->prepare("SELECT status FROM pedidos WHERE id = ? FOR UPDATE");
        $sth->execute([$id]);
        $status = $sth->fetchColumn();

        if ($status === false) {
            throw new RuntimeException('Pedido não encontrado.');
        }

        return $status;
    }

    private function trocarStatus(int $id, string $novo): void
    {
        $atual = $this->statusAtualBloqueado($id);

        if ($atual === 'cancelado' && $novo !== 'cancelado') {
            throw new RuntimeException('Um pedido cancelado não pode ser reaberto.');
        }

        if ($atual === $novo) {
            return;
        }

        if ($novo === 'cancelado') {
            $this->devolverEstoque($id);
        }

        $this->pdo->prepare("UPDATE pedidos SET status = ? WHERE id = ?")->execute([$novo, $id]);
    }

    private function devolverEstoque(int $pedidoId): void
    {
        $itens = $this->pdo->prepare("SELECT game_id, quantidade FROM pedido_itens WHERE pedido_id = ?");
        $itens->execute([$pedidoId]);

        $devolve = $this->pdo->prepare("UPDATE games SET estoque = estoque + ? WHERE id = ?");

        foreach ($itens->fetchAll() as $i) {
            if ($i['game_id']) {
                $devolve->execute([$i['quantidade'], $i['game_id']]);
            }
        }
    }
}
