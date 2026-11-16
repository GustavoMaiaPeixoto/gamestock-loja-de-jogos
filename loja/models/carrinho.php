<?php
/**
 * loja/models/carrinho.php
 * Carrinho de compras guardado na sessão: [id_do_jogo => quantidade].
 * Preço e estoque são sempre consultados no banco (nunca confiamos no navegador).
 */

require_once __DIR__ . '/../../admin/models/games.php';

class Carrinho
{
    private static function lista(): array
    {
        return $_SESSION['carrinho'] ?? [];
    }

    // Total de unidades (número no ícone do carrinho)
    public static function quantidade(): int
    {
        return (int)array_sum(self::lista());
    }

    // Retorna [tipo, mensagem]
    public static function adicionar(int $id, int $qtd = 1): array
    {
        $game = (new Game())->buscarGamePorId($id);

        if (!$game) {
            return ['erro', 'Jogo não encontrado.'];
        }

        $estoque = (int)$game['estoque'];

        if ($estoque <= 0) {
            return ['erro', '"' . $game['titulo'] . '" está esgotado.'];
        }

        $carrinho = self::lista();
        $novo     = ($carrinho[$id] ?? 0) + max(1, $qtd);

        if ($novo > $estoque) {
            $carrinho[$id] = $estoque;
            $_SESSION['carrinho'] = $carrinho;
            return ['erro', 'Quantidade limitada ao estoque disponível (' . $estoque . ') de "' . $game['titulo'] . '".'];
        }

        $carrinho[$id] = $novo;
        $_SESSION['carrinho'] = $carrinho;

        return ['sucesso', '"' . $game['titulo'] . '" foi adicionado ao carrinho.'];
    }

    public static function atualizar(int $id, int $qtd): array
    {
        $carrinho = self::lista();

        if (!isset($carrinho[$id])) {
            return ['erro', 'Item não está no carrinho.'];
        }

        if ($qtd <= 0) {
            self::remover($id);
            return ['sucesso', 'Item removido do carrinho.'];
        }

        $game    = (new Game())->buscarGamePorId($id);
        $estoque = $game ? (int)$game['estoque'] : 0;

        if ($qtd > $estoque) {
            $carrinho[$id] = $estoque;
            $_SESSION['carrinho'] = $carrinho;
            return ['erro', 'Só temos ' . $estoque . ' unidade(s) disponível(is).'];
        }

        $carrinho[$id] = $qtd;
        $_SESSION['carrinho'] = $carrinho;

        return ['sucesso', 'Carrinho atualizado.'];
    }

    public static function remover(int $id): void
    {
        $carrinho = self::lista();
        unset($carrinho[$id]);
        $_SESSION['carrinho'] = $carrinho;
    }

    public static function limpar(): void
    {
        unset($_SESSION['carrinho']);
    }

    /**
     * Monta os itens com dados atuais do banco.
     * Retorna ['itens' => [...], 'total' => float, 'avisos' => [...]].
     * Remove jogos apagados/esgotados e ajusta quantidades acima do estoque.
     */
    public static function itens(): array
    {
        $carrinho = self::lista();
        $vazio    = ['itens' => [], 'total' => 0.0, 'avisos' => []];

        if (!$carrinho) {
            return $vazio;
        }

        $pdo = Conexao::getInstance();

        $ids = array_keys($carrinho);
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $sth = $pdo->prepare("SELECT * FROM games WHERE id IN ($in)");
        $sth->execute($ids);

        $games = [];
        foreach ($sth->fetchAll() as $g) {
            $games[(int)$g['id']] = $g;
        }

        $itens  = [];
        $avisos = [];
        $total  = 0.0;
        $novo   = [];

        foreach ($carrinho as $id => $qtd) {
            $id = (int)$id;

            if (!isset($games[$id]) || (int)$games[$id]['estoque'] <= 0) {
                $nome = $games[$id]['titulo'] ?? 'Um jogo';
                $avisos[] = '"' . $nome . '" não está mais disponível e foi removido do carrinho.';
                continue;
            }

            $g = $games[$id];

            if ($qtd > (int)$g['estoque']) {
                $qtd = (int)$g['estoque'];
                $avisos[] = 'A quantidade de "' . $g['titulo'] . '" foi ajustada para o estoque disponível (' . $qtd . ').';
            }

            $g['quantidade'] = (int)$qtd;
            $g['subtotal']   = (float)$g['preco'] * $qtd;
            $total          += $g['subtotal'];
            $itens[]         = $g;
            $novo[$id]       = (int)$qtd;
        }

        $_SESSION['carrinho'] = $novo;

        return ['itens' => $itens, 'total' => $total, 'avisos' => $avisos];
    }
}
