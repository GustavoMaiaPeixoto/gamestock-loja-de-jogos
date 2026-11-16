<?php
/**
 * loja/views/meus_pedidos.php
 * Histórico de pedidos do cliente logado (e cancelamento de pedidos pendentes).
 */

require_once __DIR__ . '/../models/carrinho.php';
require_once __DIR__ . '/../../admin/models/pedidos.php';

exigir_cliente('loja/views/meus_pedidos.php');

$pedidos = (new Pedido())->listarPorUsuario((int)usuario_logado()['id']);
$ativo   = 'pedidos';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameStock - Meus pedidos</title>
    <link rel="stylesheet" href="../../admin/public/css/style.css">
    <link rel="stylesheet" href="../public/css/loja.css">
</head>
<body>

<?php include __DIR__ . '/_navbar.php'; ?>

<div class="page-container">

    <?php flash_exibir(); ?>

    <div class="page-header">
        <div>
            <span class="page-tag">MINHA CONTA</span>
            <h1>Meus pedidos</h1>
            <p>Acompanhe o histórico das suas compras.</p>
        </div>
        <a href="loja.php" class="btn-secondary">← Continuar comprando</a>
    </div>

    <?php if (!$pedidos): ?>

        <div class="table-container vazio-box">
            <div class="vazio-icone">📦</div>
            <p>Você ainda não fez nenhum pedido.</p>
            <a href="loja.php" class="btn-primary">Ver jogos</a>
        </div>

    <?php else: ?>

        <?php foreach ($pedidos as $p): ?>
            <article class="pedido-card">

                <div class="pedido-topo">
                    <div>
                        <strong>Pedido #<?= (int)$p['id'] ?></strong>
                        <span class="nota"><?= e(data_br($p['created_at'])) ?></span>
                    </div>
                    <span class="status status-<?= e($p['status']) ?>"><?= e(Pedido::STATUS[$p['status']]) ?></span>
                </div>

                <?php foreach ($p['itens'] as $i): ?>
                    <div class="resumo-linha">
                        <span><?= (int)$i['quantidade'] ?>× <?= e($i['titulo']) ?></span>
                        <strong><?= moeda($i['preco_unitario'] * $i['quantidade']) ?></strong>
                    </div>
                <?php endforeach; ?>

                <div class="resumo-linha resumo-total">
                    <span>Total (<?= e(Pedido::PAGAMENTOS[$p['pagamento']]) ?>)</span>
                    <strong><?= moeda($p['total']) ?></strong>
                </div>

                <p class="nota"><strong>Entrega em:</strong> <?= e($p['endereco']) ?></p>

                <?php if ($p['status'] === 'pendente'): ?>
                    <form method="POST" action="../controllers/cancelar_pedido.php"
                          onsubmit="return confirm('Cancelar o pedido #<?= (int)$p['id'] ?>?');">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="btn-link btn-link-perigo">Cancelar pedido</button>
                    </form>
                <?php endif; ?>

            </article>
        <?php endforeach; ?>

    <?php endif; ?>

</div>

<script src="../../admin/public/js/app.js"></script>
<?php include __DIR__ . '/_rodape.php'; ?>
</body>
</html>
