<?php
/**
 * views/editar_pedido.php
 * Edita pagamento, endereço e status de um pedido (o POST é tratado em
 * controllers/salvar_pedido.php). Os itens não mudam aqui: para alterar
 * itens, cancele o pedido e crie outro.
 */

require_once __DIR__ . '/../models/pedidos.php';

exigir_login();

$id     = (int)($_GET['id'] ?? 0);
$pedido = $id ? (new Pedido())->buscarPorId($id) : false;

if (!$pedido) {
    flash('erro', 'Pedido não encontrado.');
    redirecionar('ver_pedidos.php');
}

if ($pedido['status'] === 'cancelado') {
    flash('erro', 'Um pedido cancelado não pode ser editado.');
    redirecionar('ver_pedidos.php');
}

$erros = erros_pegar();
$dados = array_merge(
    ['pagamento' => $pedido['pagamento'], 'endereco' => $pedido['endereco'], 'status' => $pedido['status']],
    old_pegar()
);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameStock - Editar Pedido</title>
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

<div class="form-container">
    <h2>✏️ Editar Pedido #<?= (int)$id ?></h2>

    <?php erros_exibir($erros); ?>

    <div class="box">
        <p class="nota"><strong>Cliente:</strong> <?= e($pedido['cliente_nome']) ?> (<?= e($pedido['cliente_email']) ?>)<br>
           <strong>Data:</strong> <?= e(data_br($pedido['created_at'])) ?></p>

        <?php foreach ($pedido['itens'] as $i): ?>
            <div class="resumo-linha">
                <span><?= (int)$i['quantidade'] ?>× <?= e($i['titulo']) ?></span>
                <strong><?= moeda($i['preco_unitario'] * $i['quantidade']) ?></strong>
            </div>
        <?php endforeach; ?>

        <div class="resumo-linha resumo-total">
            <span>Total</span>
            <strong><?= moeda($pedido['total']) ?></strong>
        </div>
    </div>

    <form method="POST" action="../controllers/salvar_pedido.php" data-validar novalidate>
        <?= csrf_campo() ?>
        <input type="hidden" name="id" value="<?= (int)$id ?>">

        <div class="form-row">
            <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" name="status">
                    <?php foreach (Pedido::STATUS as $chave => $rotulo): ?>
                        <option value="<?= e($chave) ?>" <?= $dados['status'] === $chave ? 'selected' : '' ?>><?= e($rotulo) ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="dica">Cancelar devolve os jogos ao estoque e não pode ser desfeito.</small>
            </div>

            <div class="form-group">
                <label for="pagamento">Forma de pagamento *</label>
                <select id="pagamento" name="pagamento">
                    <?php foreach (Pedido::PAGAMENTOS as $chave => $rotulo): ?>
                        <option value="<?= e($chave) ?>" <?= $dados['pagamento'] === $chave ? 'selected' : '' ?>><?= e($rotulo) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="endereco">Endereço de entrega *</label>
            <textarea id="endereco" name="endereco" rows="3" minlength="10" maxlength="500" required><?= e($dados['endereco']) ?></textarea>
        </div>

        <div class="form-buttons">
            <button type="submit" class="btn-primary">Salvar Alterações</button>
            <a href="ver_pedidos.php" class="btn-secondary">Voltar</a>
        </div>
    </form>
</div>

<script src="../public/js/validacao.js"></script>
</body>
</html>
