<?php
/**
 * loja/views/cadastro.php
 * Registro de cliente (nome, e-mail, telefone e senha).
 * O POST é tratado em loja/controllers/registrar_cliente.php.
 */

require_once __DIR__ . '/../models/carrinho.php';

if (usuario_logado()) {
    redirecionar('loja.php');
}

$erros = erros_pegar();
$dados = array_merge(['nome' => '', 'email' => '', 'telefone' => ''], old_pegar());
$ativo = '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameStock - Criar conta</title>
    <link rel="stylesheet" href="../../admin/public/css/style.css">
    <link rel="stylesheet" href="../public/css/loja.css">
</head>
<body>

<?php include __DIR__ . '/_navbar.php'; ?>

<div class="form-container">
    <h2>👤 Criar conta</h2>

    <?php erros_exibir($erros); ?>

    <form method="POST" action="../controllers/registrar_cliente.php" data-validar novalidate>
        <?= csrf_campo() ?>

        <div class="form-group">
            <label for="nome">Nome *</label>
            <input type="text" id="nome" name="nome" maxlength="100" value="<?= e($dados['nome']) ?>" required autofocus>
        </div>

        <div class="form-group">
            <label for="email">E-mail *</label>
            <input type="email" id="email" name="email" maxlength="150" value="<?= e($dados['email']) ?>" required>
        </div>

        <div class="form-group">
            <label for="telefone">Telefone *</label>
            <input type="text" id="telefone" name="telefone" maxlength="20" value="<?= e($dados['telefone']) ?>"
                   placeholder="(38) 99999-9999" data-mascara="telefone" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="senha">Senha * (mín. 6 caracteres)</label>
                <input type="password" id="senha" name="senha" minlength="6" required>
            </div>
            <div class="form-group">
                <label for="confirmar">Confirmar senha *</label>
                <input type="password" id="confirmar" name="confirmar" minlength="6" data-igual="#senha" required>
            </div>
        </div>

        <div class="form-buttons">
            <button type="submit" class="btn-primary">Criar conta</button>
            <a href="loja.php" class="btn-secondary">Voltar</a>
        </div>

        <p class="login-links">Já tem conta? <a href="../../login.php">Entrar</a></p>
    </form>
</div>

<script src="../../admin/public/js/validacao.js"></script>
<?php include __DIR__ . '/_rodape.php'; ?>
</body>
</html>
