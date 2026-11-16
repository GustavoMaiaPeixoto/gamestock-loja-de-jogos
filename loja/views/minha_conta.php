<?php
/**
 * loja/views/minha_conta.php
 * O cliente edita seus próprios dados. O endereço salvo aqui já vem preenchido
 * no checkout. O POST é tratado em loja/controllers/salvar_conta.php.
 */

require_once __DIR__ . '/../models/carrinho.php';
require_once __DIR__ . '/../../admin/models/clientes.php';

exigir_cliente('loja/views/minha_conta.php');

$cliente       = (new Cliente())->garantirPorUsuario(usuario_logado());
$emailEditavel = false;   // o e-mail é o da conta de acesso
$erros         = erros_pegar();
$dados         = array_merge(Cliente::normalizar($cliente), old_pegar());
$ativo         = 'conta';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameStock - Minha conta</title>
    <link rel="stylesheet" href="../../admin/public/css/style.css">
    <link rel="stylesheet" href="../public/css/loja.css">
</head>
<body>

<?php include __DIR__ . '/_navbar.php'; ?>

<div class="form-container">
    <h2>👤 Minha conta</h2>

    <?php flash_exibir(); ?>
    <?php erros_exibir($erros); ?>

    <form method="POST" action="../controllers/salvar_conta.php" data-validar novalidate>
        <?= csrf_campo() ?>

        <?php include __DIR__ . '/../../admin/views/_form_cliente.php'; ?>

        <div class="form-buttons">
            <button type="submit" class="btn-primary">Salvar dados</button>
            <a href="loja.php" class="btn-secondary">Voltar à loja</a>
        </div>
    </form>
</div>

<script src="../../admin/public/js/validacao.js"></script>
<script src="../../admin/public/js/app.js"></script>
<?php include __DIR__ . '/_rodape.php'; ?>
</body>
</html>
