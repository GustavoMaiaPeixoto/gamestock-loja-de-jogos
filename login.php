<?php
/**
 * login.php
 * Tela de login (só exibe o formulário; o POST é tratado em
 * admin/controllers/autenticar.php).
 */

require_once __DIR__ . '/admin/config/conexao.php';

// Já logado: vai direto para a área do seu perfil
if ($u = usuario_logado()) {
    redirecionar($u['tipo'] === 'admin' ? 'admin/views/dashboard.php' : 'loja/views/loja.php');
}

$erros = erros_pegar();
$old   = old_pegar();
$email = $old['email'] ?? '';
$flash = flash_pegar();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameStock - Login</title>
    <link rel="stylesheet" href="admin/public/css/style.css">
</head>
<body class="login-body">

    <main class="login-card">

        <div class="login-logo">🎮</div>
        <span class="page-tag">GAMESTOCK</span>
        <h1>Entrar na sua conta</h1>
        <p class="login-sub">Compre jogos ou gerencie a loja.</p>

        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['tipo']) ?>"><?= e($flash['mensagem']) ?></div>
        <?php endif; ?>

        <?php erros_exibir($erros); ?>

        <form method="POST" action="admin/controllers/autenticar.php" autocomplete="on" data-validar novalidate>
            <?= csrf_campo() ?>

            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required>
            </div>

            <button type="submit" class="btn-primary btn-block">Entrar</button>
        </form>

        <p class="login-links">
            Ainda não tem conta? <a href="loja/views/cadastro.php">Criar conta</a><br>
            <a href="loja/views/loja.php">← Voltar para a loja</a>
        </p>

    </main>

    <script src="admin/public/js/validacao.js"></script>
</body>
</html>
