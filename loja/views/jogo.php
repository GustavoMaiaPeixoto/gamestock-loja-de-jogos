<?php
/**
 * loja/views/jogo.php
 * Página de detalhes de um jogo, com opções de adicionar ao carrinho ou comprar agora.
 */

require_once __DIR__ . '/../models/carrinho.php';

$id = (int)($_GET['id'] ?? 0);
$g  = $id ? (new Game())->buscarGamePorId($id) : false;

if (!$g) {
    flash('erro', 'Jogo não encontrado.');
    header('Location: loja.php');
    exit;
}

$est   = (int)$g['estoque'];
$ativo = 'loja';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameStock - <?= e($g['titulo']) ?></title>
    <link rel="stylesheet" href="../../admin/public/css/style.css">
    <link rel="stylesheet" href="../public/css/loja.css">
</head>
<body>

<?php include __DIR__ . '/_navbar.php'; ?>

<div class="page-container">

    <?php flash_exibir(); ?>

    <a href="loja.php" class="voltar-link">← Voltar para a loja</a>

    <div class="detalhe">

        <div class="detalhe-imagem">
            <?php if (Game::imagemExiste($g['imagem'])): ?>
                <img src="../../admin/public/imagens/<?= e($g['imagem']) ?>" alt="Capa de <?= e($g['titulo']) ?>">
            <?php else: ?>
                <div class="game-card-vazio detalhe-vazio">🎮</div>
            <?php endif; ?>
        </div>

        <div class="detalhe-info">
            <?php if (!empty($g['categoria'])): ?>
                <span class="tag-categoria"><?= e($g['categoria']) ?></span>
            <?php endif; ?>

            <h1><?= e($g['titulo']) ?></h1>

            <p class="detalhe-preco"><?= moeda($g['preco']) ?></p>

            <?php if ($est === 0): ?>
                <span class="stock-badge stock-zero">Esgotado</span>
            <?php elseif ($est <= 5): ?>
                <span class="stock-badge stock-baixo">Restam apenas <?= $est ?> unidade(s)</span>
            <?php else: ?>
                <span class="stock-badge">Em estoque</span>
            <?php endif; ?>

            <p class="detalhe-desc">
                <?= $g['descricao'] !== '' ? nl2br(e($g['descricao'])) : 'Sem descrição disponível.' ?>
            </p>

            <?php if ($est > 0): ?>
                <form method="POST" action="../controllers/carrinho.php" class="detalhe-form">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="acao" value="adicionar">
                    <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                    <input type="hidden" name="voltar" value="jogo.php?id=<?= (int)$g['id'] ?>">

                    <label for="quantidade">Quantidade</label>
                    <input type="number" id="quantidade" name="quantidade" value="1" min="1" max="<?= $est ?>">

                    <div class="detalhe-botoes">
                        <button type="submit" class="btn-secondary">🛒 Adicionar ao carrinho</button>
                        <button type="submit" name="comprar_agora" value="1" class="btn-primary">Comprar agora</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="../../admin/public/js/app.js"></script>
<?php include __DIR__ . '/_rodape.php'; ?>
</body>
</html>
