<?php
/**
 * loja/views/pedido_confirmado.php
 * Tela exibida após finalizar a compra.
 */

require_once __DIR__ . '/../models/carrinho.php';
require_once __DIR__ . '/../../admin/models/pedidos.php';

exigir_cliente();

$pedido = (new Pedido())->buscarPorId((int)($_GET['id'] ?? 0), (int)usuario_logado()['id']);

if (!$pedido) {
    flash('erro', 'Pedido não encontrado.');
    header('Location: meus_pedidos.php');
    exit;
}

$ativo = 'pedidos';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameStock - Pedido confirmado</title>
    <link rel="stylesheet" href="../../admin/public/css/style.css">
    <link rel="stylesheet" href="../public/css/loja.css">
</head>
<body>

<?php include __DIR__ . '/_navbar.php'; ?>

<div class="page-container">

    <div class="confirmado">
        <div class="confirmado-icone">✅</div>
        <h1>Pedido realizado com sucesso!</h1>
        <p>Obrigado pela compra. Seu pedido <strong>#<?= (int)$pedido['id'] ?></strong> foi registrado e está
           <span class="status status-<?= e($pedido['status']) ?>"><?= e(Pedido::STATUS[$pedido['status']]) ?></span>.</p>

        <div class="box confirmado-box">
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

            <p class="nota"><strong>Pagamento:</strong> <?= e(Pedido::PAGAMENTOS[$pedido['pagamento']]) ?><br>
               <strong>Entrega em:</strong> <?= e($pedido['endereco']) ?></p>
        </div>

        <div class="detalhe-botoes centro">
            <a href="meus_pedidos.php" class="btn-primary">Ver meus pedidos</a>
            <a href="loja.php" class="btn-secondary">Continuar comprando</a>
        </div>
    </div>

</div>

<?php include __DIR__ . '/_rodape.php'; ?>
</body>
</html>
