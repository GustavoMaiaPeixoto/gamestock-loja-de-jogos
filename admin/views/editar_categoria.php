<?php
/**
 * views/editar_categoria.php
 * Formulário para editar categorias (o POST é tratado em controllers/salvar_categoria.php).
 */

require_once __DIR__ . '/../models/categorias.php';

exigir_login();

$id    = (int)($_GET['id'] ?? 0);
$atual = $id ? (new Categoria())->buscarPorId($id) : false;

if (!$atual) {
    flash('erro', 'Categoria não encontrada.');
    redirecionar('ver_categorias.php');
}

$erros = erros_pegar();
$dados = array_merge(['nome' => $atual['nome'], 'descricao' => (string)$atual['descricao']], old_pegar());
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameStock - Editar Categoria</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>

<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="dashboard.php">🎮 Game<span>Stock</span></a>

        <nav class="nav">
            <a href="dashboard.php" class="">Dashboard</a>
            <a href="gerenciar_games.php" class="">Jogos</a>
            <a href="ver_categorias.php" class="active">Categorias</a>
            <a href="ver_clientes.php" class="">Clientes</a>
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
    <h2>✏️ Editar Categoria #<?= (int)$id ?></h2>

    <?php erros_exibir($erros); ?>

    <form method="POST" action="../controllers/salvar_categoria.php" data-validar novalidate>
        <?= csrf_campo() ?>
        <input type="hidden" name="id" value="<?= (int)$id ?>">

        <div class="form-group">
            <label for="nome">Nome *</label>
            <input type="text" id="nome" name="nome" maxlength="50" value="<?= e($dados['nome']) ?>" required>
        </div>

        <div class="form-group">
            <label for="descricao">Descrição</label>
            <textarea id="descricao" name="descricao" rows="3" maxlength="255"><?= e($dados['descricao']) ?></textarea>
        </div>

        <div class="form-buttons">
            <button type="submit" class="btn-primary">Salvar Alterações</button>
            <a href="ver_categorias.php" class="btn-secondary">Voltar</a>
        </div>
    </form>
</div>

<script src="../public/js/validacao.js"></script>
<script src="../public/js/app.js"></script>
</body>
</html>
