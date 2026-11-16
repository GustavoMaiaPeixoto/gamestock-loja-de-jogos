<?php
/**
 * loja/views/checkout.php
 * Finalização da compra: endereço de entrega e forma de pagamento.
 * Exige que o cliente esteja logado.
 */

require_once __DIR__ . '/../models/carrinho.php';
require_once __DIR__ . '/../../admin/models/pedidos.php';
require_once __DIR__ . '/../../admin/models/clientes.php';

exigir_cliente('loja/views/checkout.php');

$dados = Carrinho::itens();

if (!$dados['itens']) {
    flash('erro', 'Seu carrinho está vazio.');
    header('Location: carrinho.php');
    exit;
}

$antes     = $_SESSION['checkout'] ?? [];
$cliente   = (new Cliente())->garantirPorUsuario(usuario_logado());
$endereco  = $antes['endereco'] ?? Cliente::enderecoCompleto($cliente);
$pagamento = $antes['pagamento'] ?? 'pix';
$ativo     = '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameStock - Finalizar compra</title>
    <link rel="stylesheet" href="../../admin/public/css/style.css">
    <link rel="stylesheet" href="../public/css/loja.css">
</head>
<body>

<?php include __DIR__ . '/_navbar.php'; ?>

<div class="page-container">

    <?php flash_exibir(); ?>

    <?php foreach ($dados['avisos'] as $aviso): ?>
        <div class="alert alert-aviso"><?= e($aviso) ?></div>
    <?php endforeach; ?>

    <div class="page-header">
        <div>
            <span class="page-tag">CHECKOUT</span>
            <h1>Finalizar compra</h1>
        </div>
        <a href="carrinho.php" class="btn-secondary">← Voltar ao carrinho</a>
    </div>

    <form method="POST" action="../controllers/finalizar_compra.php" class="checkout-layout" data-validar novalidate>
        <?= csrf_campo() ?>

        <div class="checkout-form">

            <div class="box">
                <h3>1. Endereço de entrega</h3>
                <div class="form-group">
                    <label for="endereco">Rua, número, bairro, cidade, estado e CEP *</label>
                    <textarea id="endereco" name="endereco" rows="4" minlength="10" maxlength="500" required
                              placeholder="Ex.: Rua das Flores, 123 - Centro, Montes Claros/MG - 39400-000"><?= e($endereco) ?></textarea>
                    <small class="dica">Preenchido com o endereço do seu cadastro. <a href="minha_conta.php">Editar meus dados</a></small>
                </div>
            </div>

            <div class="box">
                <h3>2. Forma de pagamento</h3>

                <?php foreach (Pedido::PAGAMENTOS as $chave => $rotulo): ?>
                    <label class="opcao-pagamento">
                        <input type="radio" name="pagamento" value="<?= e($chave) ?>" <?= $pagamento === $chave ? 'checked' : '' ?> required>
                        <span><?= e($rotulo) ?></span>
                    </label>
                <?php endforeach; ?>

                <p class="nota">Ambiente de demonstração: nenhum dado de pagamento é coletado e nenhuma cobrança é feita.</p>
            </div>

        </div>

        <aside class="resumo">
            <h3>Seu pedido</h3>

            <?php foreach ($dados['itens'] as $i): ?>
                <div class="resumo-linha">
                    <span><?= (int)$i['quantidade'] ?>× <?= e($i['titulo']) ?></span>
                    <strong><?= moeda($i['subtotal']) ?></strong>
                </div>
            <?php endforeach; ?>

            <div class="resumo-linha resumo-total">
                <span>Total</span>
                <strong><?= moeda($dados['total']) ?></strong>
            </div>

            <button type="submit" class="btn-primary btn-block">Confirmar pedido</button>
        </aside>
    </form>

</div>

<script src="../../admin/public/js/validacao.js"></script>
<script src="../../admin/public/js/app.js"></script>
<?php include __DIR__ . '/_rodape.php'; ?>
</body>
</html>
