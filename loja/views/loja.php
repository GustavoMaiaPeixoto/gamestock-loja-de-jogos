<?php
/**
 * loja/views/loja.php
 * Vitrine da loja: o cliente navega, pesquisa, filtra e adiciona jogos ao carrinho.
 */

require_once __DIR__ . '/../models/carrinho.php';
require_once __DIR__ . '/../../admin/models/categorias.php';

$model      = new Game();
$busca      = trim($_GET['buscar'] ?? '');
$categoria  = (int)($_GET['categoria'] ?? 0);
$ordem      = $_GET['ordem'] ?? 'titulo';
$games      = $model->buscar($busca, $categoria, $ordem);
$categorias = (new Categoria())->listar();

// Para voltar à mesma página (com filtros) depois de adicionar ao carrinho
$voltar = 'loja.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');

$ativo = 'loja';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameStock - Loja</title>
    <link rel="stylesheet" href="../../admin/public/css/style.css">
    <link rel="stylesheet" href="../public/css/loja.css">
</head>
<body>

<?php include __DIR__ . '/_navbar.php'; ?>

<div class="page-container">

    <?php flash_exibir(); ?>

    <?php $filtrando = $busca !== '' || $categoria > 0 || $ordem !== 'titulo'; ?>

    <?php if (!$filtrando): ?>
        <section class="hero">
            <span class="page-tag">BEM-VINDO À GAMESTOCK</span>
            <h1>Os melhores jogos, direto na sua casa</h1>
            <p>Somos uma loja de games com catálogo para todos os estilos: aventura, ação, esporte, RPG e muito mais.
               Pesquise, escolha e compre em poucos cliques.</p>
            <div class="hero-botoes">
                <a href="#catalogo" class="btn-primary">Ver jogos</a>
                <?php if (!usuario_logado()): ?>
                    <a href="cadastro.php" class="btn-secondary">Criar minha conta</a>
                <?php endif; ?>
            </div>
        </section>

        <div class="info-strip">
            <div class="info-item"><span class="info-icone">🚚</span><strong>Entrega para todo o Brasil</strong>Receba no endereço do seu cadastro.</div>
            <div class="info-item"><span class="info-icone">💳</span><strong>Pix, cartão ou boleto</strong>Escolha a forma de pagamento no checkout.</div>
            <div class="info-item"><span class="info-icone">📦</span><strong>Estoque em tempo real</strong>Você só compra o que está disponível.</div>
            <div class="info-item"><span class="info-icone">💬</span><strong>Atendimento</strong>Seg a Sex, 9h às 18h — (38) 3200-0000.</div>
        </div>
    <?php endif; ?>

    <div class="chips" id="catalogo">
        <a href="loja.php" class="chip <?= $categoria === 0 ? 'ativo' : '' ?>">Todos</a>
        <?php foreach ($categorias as $c): ?>
            <a href="loja.php?categoria=<?= (int)$c['id'] ?>" class="chip <?= $categoria === (int)$c['id'] ? 'ativo' : '' ?>"><?= e($c['nome']) ?></a>
        <?php endforeach; ?>
    </div>

    <h2 class="secao-titulo">Nossos jogos <span class="nota">(<?= count($games) ?>)</span></h2>

    <div class="search-box">
        <form method="GET" action="">
            <input type="text" name="buscar" placeholder="Buscar jogo..." value="<?= e($busca) ?>">

            <select name="categoria">
                <option value="">Todas as categorias</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === $categoria ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="ordem">
                <option value="titulo"     <?= $ordem === 'titulo'     ? 'selected' : '' ?>>Nome (A–Z)</option>
                <option value="preco_asc"  <?= $ordem === 'preco_asc'  ? 'selected' : '' ?>>Menor preço</option>
                <option value="preco_desc" <?= $ordem === 'preco_desc' ? 'selected' : '' ?>>Maior preço</option>
                <option value="novos"      <?= $ordem === 'novos'      ? 'selected' : '' ?>>Lançamentos</option>
            </select>

            <button type="submit" class="btn-search">Buscar</button>

            <?php if ($busca !== '' || $categoria > 0 || $ordem !== 'titulo'): ?>
                <a href="loja.php" class="btn-clear">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($games): ?>
        <div class="cards-grid">
            <?php foreach ($games as $g): ?>
                <?php $est = (int)$g['estoque']; ?>
                <article class="game-card">

                    <a href="jogo.php?id=<?= (int)$g['id'] ?>">
                        <?php if (Game::imagemExiste($g['imagem'])): ?>
                            <img class="game-card-img" src="../../admin/public/imagens/<?= e($g['imagem']) ?>" alt="Capa de <?= e($g['titulo']) ?>">
                        <?php else: ?>
                            <div class="game-card-img game-card-vazio">🎮</div>
                        <?php endif; ?>
                    </a>

                    <div class="game-card-body">
                        <?php if (!empty($g['categoria'])): ?>
                            <span class="tag-categoria"><?= e($g['categoria']) ?></span>
                        <?php endif; ?>

                        <h3><a href="jogo.php?id=<?= (int)$g['id'] ?>"><?= e($g['titulo']) ?></a></h3>
                        <p><?= e($g['descricao']) ?></p>

                        <div class="game-card-footer">
                            <strong class="price-column"><?= moeda($g['preco']) ?></strong>
                            <?php if ($est === 0): ?>
                                <span class="stock-badge stock-zero">Esgotado</span>
                            <?php elseif ($est <= 5): ?>
                                <span class="stock-badge stock-baixo">Restam <?= $est ?></span>
                            <?php endif; ?>
                        </div>

                        <form method="POST" action="../controllers/carrinho.php" class="card-form">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="acao" value="adicionar">
                            <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                            <input type="hidden" name="voltar" value="<?= e($voltar) ?>">

                            <button type="submit" class="btn-primary btn-sm btn-block" <?= $est === 0 ? 'disabled' : '' ?>>
                                <?= $est === 0 ? 'Indisponível' : '🛒 Adicionar ao carrinho' ?>
                            </button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="table-container"><p class="empty-message">Nenhum jogo encontrado.</p></div>
    <?php endif; ?>

</div>

<script src="../../admin/public/js/app.js"></script>
<?php include __DIR__ . '/_rodape.php'; ?>
</body>
</html>
