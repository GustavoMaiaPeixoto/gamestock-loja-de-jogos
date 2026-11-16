<?php
/**
 * views/cadastrar_pedido.php
 * O admin registra um pedido para um cliente (venda no balcão, por telefone...).
 * O POST é tratado em controllers/salvar_pedido.php.
 */

require_once __DIR__ . '/../models/pedidos.php';
require_once __DIR__ . '/../models/clientes.php';
require_once __DIR__ . '/../models/games.php';

exigir_login();

$clientes = (new Cliente())->listar();
$games    = array_filter((new Game())->listarGames(), function ($g) { return (int)$g['estoque'] > 0; });

$erros = erros_pegar();
$old   = old_pegar();

$clienteId = (int)($old['cliente_id'] ?? 0);
$pagamento = $old['pagamento'] ?? 'pix';
$endereco  = $old['endereco'] ?? '';

// Linhas de jogos: as que o admin tinha preenchido ou 3 linhas vazias
$linhas = [];
foreach (($old['game_id'] ?? []) as $k => $gid) {
    $linhas[] = ['game_id' => $gid, 'quantidade' => max(1, (int)($old['quantidade'][$k] ?? 1))];
}
if (!$linhas) {
    $linhas = array_fill(0, 3, ['game_id' => '', 'quantidade' => 1]);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameStock - Cadastrar Pedido</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="../../loja/public/css/loja.css">
</head>
<body>

<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="dashboard.php">🎮 Game<span>Stock</span></a>

        <nav class="nav">
            <a href="dashboard.php" class="">Dashboard</a>
            <a href="gerenciar_games.php" class="">Jogos</a>
            <a href="ver_categorias.php" class="">Categorias</a>
            <a href="ver_clientes.php" class="">Clientes</a>
            <a href="ver_usuarios.php" class="">Usuários</a>
            <a href="ver_pedidos.php" class="active">Pedidos</a>
            <a href="relatorio_vendas.php" class="">Relatório</a>
            <a href="../../loja/views/loja.php">Ver loja</a>
        </nav>

        <div class="topbar-user">
            <span>👤 <?= e(usuario_logado()['nome']) ?></span>
            <a href="../controllers/logout.php" class="btn-ghost">Sair</a>
        </div>
    </div>
</header>

<div class="form-container form-largo">
    <h2>🧾 Cadastrar Pedido</h2>

    <?php erros_exibir($erros); ?>

    <form method="POST" action="../controllers/salvar_pedido.php" data-validar novalidate>
        <?= csrf_campo() ?>

        <div class="form-group">
            <label for="cliente_id">Cliente *</label>
            <select id="cliente_id" name="cliente_id" required data-cliente-endereco="#endereco">
                <option value="">— Escolha o cliente —</option>
                <?php foreach ($clientes as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"
                            data-endereco="<?= e(Cliente::enderecoCompleto($c)) ?>"
                            <?= $clienteId === (int)$c['id'] ? 'selected' : '' ?>>
                        <?= e($c['nome']) ?> (<?= e($c['email']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Jogos *</label>

            <div id="linhas-pedido">
                <?php foreach ($linhas as $l): ?>
                    <div class="linha-pedido">
                        <select name="game_id[]">
                            <option value="">— Escolha um jogo —</option>
                            <?php foreach ($games as $g): ?>
                                <option value="<?= (int)$g['id'] ?>" <?= (int)$l['game_id'] === (int)$g['id'] ? 'selected' : '' ?>>
                                    <?= e($g['titulo']) ?> — <?= moeda($g['preco']) ?> (estoque: <?= (int)$g['estoque'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="quantidade[]" min="1" value="<?= (int)$l['quantidade'] ?>" title="Quantidade">
                        <button type="button" class="btn-mini" data-remover-linha title="Remover linha">✕</button>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="btn-mini" data-add-linha="#linhas-pedido">+ Adicionar outro jogo</button>
        </div>

        <div class="form-group">
            <label for="pagamento">Forma de pagamento *</label>
            <select id="pagamento" name="pagamento">
                <?php foreach (Pedido::PAGAMENTOS as $chave => $rotulo): ?>
                    <option value="<?= e($chave) ?>" <?= $pagamento === $chave ? 'selected' : '' ?>><?= e($rotulo) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="endereco">Endereço de entrega *</label>
            <textarea id="endereco" name="endereco" rows="3" minlength="10" maxlength="500" required><?= e($endereco) ?></textarea>
            <small class="dica">Ao escolher o cliente, o endereço do cadastro dele é sugerido.</small>
        </div>

        <div class="form-buttons">
            <button type="submit" class="btn-primary">Cadastrar Pedido</button>
            <a href="ver_pedidos.php" class="btn-secondary">Voltar</a>
        </div>
    </form>
</div>

<script src="../public/js/validacao.js"></script>
<script src="../public/js/app.js"></script>
</body>
</html>
