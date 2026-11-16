<?php
/**
 * views/cadastrar_game.php
 * Formulário para adicionar novos jogos (o POST é tratado em controllers/salvar_game.php).
 */

require_once __DIR__ . '/../models/games.php';
require_once __DIR__ . '/../models/categorias.php';

exigir_login();

$categorias = (new Categoria())->listar();
$erros      = erros_pegar();
$dados      = array_merge(['titulo' => '', 'descricao' => '', 'preco' => '', 'estoque' => '0', 'categoria_id' => ''], old_pegar());
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameStock - Cadastrar Jogo</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>

<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="dashboard.php">🎮 Game<span>Stock</span></a>

        <nav class="nav">
            <a href="dashboard.php" class="">Dashboard</a>
            <a href="gerenciar_games.php" class="active">Jogos</a>
            <a href="ver_categorias.php" class="">Categorias</a>
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
    <h2>🎮 Cadastrar Jogo</h2>

    <?php erros_exibir($erros); ?>

    <form method="POST" action="../controllers/salvar_game.php" enctype="multipart/form-data" data-validar novalidate>
        <?= csrf_campo() ?>

        <div class="form-group">
            <label for="titulo">Título *</label>
            <input type="text" id="titulo" name="titulo" maxlength="100" value="<?= e($dados['titulo']) ?>" required>
        </div>

        <div class="form-group">
            <label for="descricao">Descrição</label>
            <textarea id="descricao" name="descricao" rows="4"><?= e($dados['descricao']) ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="preco">Preço (R$) *</label>
                <input type="number" id="preco" name="preco" step="0.01" min="0" value="<?= e($dados['preco']) ?>" required>
            </div>
            <div class="form-group">
                <label for="estoque">Estoque *</label>
                <input type="number" id="estoque" name="estoque" min="0" step="1" value="<?= e($dados['estoque']) ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="categoria_id">Categoria</label>
            <select id="categoria_id" name="categoria_id">
                <option value="">— Sem categoria —</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= (string)$dados['categoria_id'] === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            <small class="dica">Gerencie as categorias em <a href="ver_categorias.php">Categorias</a>.</small>
        </div>

        <div class="form-group">
            <label for="imagem">Imagem (JPG, PNG, WEBP ou GIF — até 2 MB)</label>
            <input type="file" id="imagem" name="imagem" accept="image/jpeg,image/png,image/webp,image/gif" data-preview="#previewImg">
            <img id="previewImg" class="preview-img" alt="" hidden>
        </div>

        <div class="form-buttons">
            <button type="submit" class="btn-primary">Cadastrar Jogo</button>
            <a href="gerenciar_games.php" class="btn-secondary">Voltar</a>
        </div>
    </form>
</div>

<script src="../public/js/validacao.js"></script>
<script src="../public/js/app.js"></script>
</body>
</html>
