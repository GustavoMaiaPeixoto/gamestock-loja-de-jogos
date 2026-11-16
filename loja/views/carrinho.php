<?php
/**
 * loja/views/carrinho.php
 * Carrinho de compras: alterar quantidades, remover itens e ir para o checkout.
 */

require_once __DIR__ . '/../models/carrinho.php';

$dados = Carrinho::itens();
$ativo = '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameStock - Carrinho</title>
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
            <span class="page-tag">CARRINHO</span>
            <h1>Seu carrinho</h1>
        </div>
        <a href="loja.php" class="btn-secondary">← Continuar comprando</a>
    </div>

    <?php if (!$dados['itens']): ?>

        <div class="table-container vazio-box">
            <div class="vazio-icone">🛒</div>
            <p>Seu carrinho está vazio.</p>
            <a href="loja.php" class="btn-primary">Ver jogos</a>
        </div>

    <?php else: ?>

        <div class="checkout-layout">

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Jogo</th>
                            <th>Preço</th>
                            <th>Quantidade</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($dados['itens'] as $i): ?>
                        <tr>
                            <td>
                                <div class="game-cell">
                                    <?php if (Game::imagemExiste($i['imagem'])): ?>
                                        <img class="thumb" src="../../admin/public/imagens/<?= e($i['imagem']) ?>" alt="">
                                    <?php else: ?>
                                        <span class="thumb thumb-vazio">🎮</span>
                                    <?php endif; ?>
                                    <a class="game-title" href="jogo.php?id=<?= (int)$i['id'] ?>"><?= e($i['titulo']) ?></a>
                                </div>
                            </td>

                            <td class="price-column"><?= moeda($i['preco']) ?></td>

                            <td>
                                <form method="POST" action="../controllers/carrinho.php" class="qtd-form">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="acao" value="atualizar">
                                    <input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
                                    <input type="number" name="quantidade" value="<?= (int)$i['quantidade'] ?>" min="0" max="<?= (int)$i['estoque'] ?>">
                                    <button type="submit" class="btn-mini">Atualizar</button>
                                </form>
                            </td>

                            <td class="price-column"><?= moeda($i['subtotal']) ?></td>

                            <td class="actions">
                                <form method="POST" action="../controllers/carrinho.php">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="acao" value="remover">
                                    <input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
                                    <button type="submit" class="action-delete" title="Remover do carrinho">🗑️</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <aside class="resumo">
                <h3>Resumo do pedido</h3>

                <div class="resumo-linha">
                    <span>Itens</span>
                    <strong><?= Carrinho::quantidade() ?></strong>
                </div>
                <div class="resumo-linha resumo-total">
                    <span>Total</span>
                    <strong><?= moeda($dados['total']) ?></strong>
                </div>

                <a href="checkout.php" class="btn-primary btn-block">Finalizar compra</a>

                <form method="POST" action="../controllers/carrinho.php">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="acao" value="limpar">
                    <button type="submit" class="btn-link">Esvaziar carrinho</button>
                </form>
            </aside>

        </div>

    <?php endif; ?>

</div>

<script src="../../admin/public/js/app.js"></script>
<?php include __DIR__ . '/_rodape.php'; ?>
</body>
</html>
