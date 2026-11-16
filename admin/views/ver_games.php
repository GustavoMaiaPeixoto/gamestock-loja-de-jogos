<?php
/**
 * views/ver_games.php
 * Exibe a lista de jogos cadastrados (catálogo somente leitura).
 */

require_once __DIR__ . '/../models/games.php';
require_once __DIR__ . '/../models/categorias.php';

exigir_login();

$model      = new Game();
$busca      = trim($_GET['buscar'] ?? '');
$categoria  = (int)($_GET['categoria'] ?? 0);
$games      = $model->buscar($busca, $categoria);
$categorias = (new Categoria())->listar();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameStock - Catálogo</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>

<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="dashboard.php">🎮 Game<span>Stock</span></a>

        <nav class="nav">
            <a href="dashboard.php" class="">Dashboard</a>
            <a href="gerenciar_games.php" class="active">Jogos</a>
            <a href="ver_categorias.php" class="">Categorias</a>
            <a href="ver_clientes.php" class="">Clientes</a>
            <a href="ver_usuarios.php" class="">Usuários</a>
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

<div class="page-container">

    <div class="page-header">
        <div>
            <span class="page-tag">CATÁLOGO</span>
            <h1>Jogos cadastrados</h1>
            <p><?= count($games) ?> jogo(s) encontrado(s).</p>
        </div>
        <a href="gerenciar_games.php" class="btn-primary">Gerenciar jogos</a>
    </div>

    <div class="search-box">
        <form method="GET" action="">
            <input type="text" name="buscar" placeholder="Buscar por título..." value="<?= e($busca) ?>">

            <select name="categoria">
                <option value="">Todas as categorias</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === $categoria ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn-search">Buscar</button>

            <?php if ($busca !== '' || $categoria > 0): ?>
                <a href="ver_games.php" class="btn-clear">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($games): ?>
        <div class="cards-grid">
            <?php foreach ($games as $g): ?>
                <article class="game-card">
                    <?php if (Game::imagemExiste($g['imagem'])): ?>
                        <img class="game-card-img" src="../public/imagens/<?= e($g['imagem']) ?>" alt="Capa de <?= e($g['titulo']) ?>">
                    <?php else: ?>
                        <div class="game-card-img game-card-vazio">🎮</div>
                    <?php endif; ?>

                    <div class="game-card-body">
                        <?php if (!empty($g['categoria'])): ?>
                            <span class="tag-categoria"><?= e($g['categoria']) ?></span>
                        <?php endif; ?>

                        <h3><?= e($g['titulo']) ?></h3>
                        <p><?= e($g['descricao']) ?></p>

                        <div class="game-card-footer">
                            <strong class="price-column"><?= moeda($g['preco']) ?></strong>
                            <?php
                                $est = (int)$g['estoque'];
                                $cls = $est === 0 ? 'stock-zero' : ($est <= 5 ? 'stock-baixo' : '');
                            ?>
                            <span class="stock-badge <?= $cls ?>"><?= $est === 0 ? 'Esgotado' : $est . ' em estoque' ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="table-container"><p class="empty-message">Nenhum jogo encontrado.</p></div>
    <?php endif; ?>

</div>

</body>
</html>
