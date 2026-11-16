<?php
/**
 * views/editar_cliente.php
 * Formulário para editar clientes (o POST é tratado em controllers/salvar_cliente.php).
 */

require_once __DIR__ . '/../models/clientes.php';

exigir_login();

$id    = (int)($_GET['id'] ?? 0);
$atual = $id ? (new Cliente())->buscarPorId($id) : false;

if (!$atual) {
    flash('erro', 'Cliente não encontrado.');
    redirecionar('ver_clientes.php');
}

// Cliente com conta de acesso: o e-mail é o da conta
$emailEditavel = $atual['usuario_id'] === null;

$erros = erros_pegar();
$dados = array_merge(Cliente::normalizar($atual), old_pegar());
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameStock - Editar Cliente</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>

<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="dashboard.php">🎮 Game<span>Stock</span></a>

        <nav class="nav">
            <a href="dashboard.php" class="">Dashboard</a>
            <a href="gerenciar_games.php" class="">Jogos</a>
            <a href="ver_categorias.php" class="">Categorias</a>
            <a href="ver_clientes.php" class="active">Clientes</a>
            <a href="ver_usuarios.php" class="">Usuários</a>
            <a href="ver_pedidos.php" class="">Pedidos</a>
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
    <h2>✏️ Editar Cliente #<?= (int)$id ?></h2>

    <?php erros_exibir($erros); ?>

    <form method="POST" action="../controllers/salvar_cliente.php" data-validar novalidate>
        <?= csrf_campo() ?>
        <input type="hidden" name="id" value="<?= (int)$id ?>">

        <?php include __DIR__ . '/_form_cliente.php'; ?>

        <div class="form-buttons">
            <button type="submit" class="btn-primary">Salvar Alterações</button>
            <a href="ver_clientes.php" class="btn-secondary">Voltar</a>
        </div>
    </form>
</div>

<script src="../public/js/validacao.js"></script>
<script src="../public/js/app.js"></script>
</body>
</html>
