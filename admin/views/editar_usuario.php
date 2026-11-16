<?php
/**
 * views/editar_usuario.php
 * Formulário para editar usuários (o POST é tratado em controllers/salvar_usuario.php).
 * Senha em branco mantém a senha atual.
 */

require_once __DIR__ . '/../models/usuarios.php';

exigir_login();

$id    = (int)($_GET['id'] ?? 0);
$atual = $id ? (new Usuario())->buscarPorId($id) : false;

if (!$atual) {
    flash('erro', 'Usuário não encontrado.');
    redirecionar('ver_usuarios.php');
}

$erros = erros_pegar();
$dados = array_merge(['nome' => $atual['nome'], 'email' => $atual['email'], 'tipo' => $atual['tipo']], old_pegar());
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameStock - Editar Usuário</title>
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
            <a href="ver_clientes.php" class="">Clientes</a>
            <a href="ver_usuarios.php" class="active">Usuários</a>
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
    <h2>✏️ Editar Usuário #<?= (int)$id ?></h2>

    <?php erros_exibir($erros); ?>

    <form method="POST" action="../controllers/salvar_usuario.php" data-validar novalidate>
        <?= csrf_campo() ?>
        <input type="hidden" name="id" value="<?= (int)$id ?>">

        <div class="form-group">
            <label for="nome">Nome *</label>
            <input type="text" id="nome" name="nome" maxlength="100" value="<?= e($dados['nome']) ?>" required>
        </div>

        <div class="form-group">
            <label for="email">E-mail *</label>
            <input type="email" id="email" name="email" maxlength="150" value="<?= e($dados['email']) ?>" required>
        </div>

        <div class="form-group">
            <label for="tipo">Tipo *</label>
            <select id="tipo" name="tipo">
                <option value="user"  <?= $dados['tipo'] === 'user'  ? 'selected' : '' ?>>Usuário</option>
                <option value="admin" <?= $dados['tipo'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="senha">Nova senha (opcional)</label>
                <input type="password" id="senha" name="senha" minlength="6"  placeholder="Deixe em branco para manter">
            </div>
            <div class="form-group">
                <label for="confirmar">Confirmar nova senha</label>
                <input type="password" id="confirmar" name="confirmar" minlength="6" data-igual="#senha" >
            </div>
        </div>

        <div class="form-buttons">
            <button type="submit" class="btn-primary">Salvar Alterações</button>
            <a href="ver_usuarios.php" class="btn-secondary">Voltar</a>
        </div>
    </form>
</div>

<script src="../public/js/validacao.js"></script>
<script src="../public/js/app.js"></script>
</body>
</html>
