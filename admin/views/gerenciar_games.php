<?php
/**
 * views/gerenciar_games.php
 * Página principal de gerenciamento dos jogos (painel + tabela com ações).
 */

require_once __DIR__ . '/../models/games.php';
require_once __DIR__ . '/../models/categorias.php';

exigir_login();

$model      = new Game();
$busca      = trim($_GET['buscar'] ?? '');
$categoria  = (int)($_GET['categoria'] ?? 0);
$games      = $model->buscar($busca, $categoria);
$categorias = (new Categoria())->listar();
$stats      = $model->estatisticas();
$flash      = flash_pegar();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameStock - Gerenciar Jogos</title>
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

    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['tipo']) ?>"><?= e($flash['mensagem']) ?></div>
    <?php endif; ?>

    <div class="page-header">
        <div>
            <span class="page-tag">PAINEL ADMINISTRATIVO</span>
            <h1>Gerenciar Jogos</h1>
            <p>Cadastre, pesquise, edite e exclua os jogos da loja.</p>
        </div>
        <a href="cadastrar_game.php" class="btn-primary">+ Cadastrar Novo Jogo</a>
    </div>

    <div class="stats">
        <div class="stat-card">
            <span>Jogos cadastrados</span>
            <strong><?= (int)$stats['total'] ?></strong>
        </div>
        <div class="stat-card">
            <span>Unidades em estoque</span>
            <strong><?= (int)$stats['unidades'] ?></strong>
        </div>
        <div class="stat-card">
            <span>Valor em estoque</span>
            <strong><?= moeda($stats['valor']) ?></strong>
        </div>
        <div class="stat-card <?= $stats['esgotados'] > 0 ? 'stat-alerta' : '' ?>">
            <span>Jogos esgotados</span>
            <strong><?= (int)$stats['esgotados'] ?></strong>
        </div>
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
                <a href="gerenciar_games.php" class="btn-clear">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Jogo</th>
                    <th>Descrição</th>
                    <th>Preço</th>
                    <th>Estoque</th>
                    <th>Categoria</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($games): ?>
                <?php foreach ($games as $g): ?>
                    <tr>
                        <td class="id-column"><?= (int)$g['id'] ?></td>

                        <td>
                            <div class="game-cell">
                                <?php if (Game::imagemExiste($g['imagem'])): ?>
                                    <img class="thumb" src="../public/imagens/<?= e($g['imagem']) ?>" alt="">
                                <?php else: ?>
                                    <span class="thumb thumb-vazio">🎮</span>
                                <?php endif; ?>
                                <span class="game-title"><?= e($g['titulo']) ?></span>
                            </div>
                        </td>

                        <td class="description-column"><?= e($g['descricao']) ?></td>
                        <td class="price-column"><?= moeda($g['preco']) ?></td>

                        <td>
                            <?php
                                $est = (int)$g['estoque'];
                                $cls = $est === 0 ? 'stock-zero' : ($est <= 5 ? 'stock-baixo' : '');
                            ?>
                            <span class="stock-badge <?= $cls ?>"><?= $est === 0 ? 'Esgotado' : $est ?></span>
                        </td>

                        <td><?= !empty($g['categoria']) ? e($g['categoria']) : '<span class="no-image">—</span>' ?></td>

                        <td class="actions">
                            <a href="editar_game.php?id=<?= (int)$g['id'] ?>" class="action-edit" title="Editar jogo">✏️</a>
                            <button type="button" class="action-delete" title="Excluir jogo"
                                    data-excluir data-id="<?= (int)$g['id'] ?>" data-nome="<?= e($g['titulo']) ?>">🗑️</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" class="empty-message">Nenhum jogo encontrado.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<dialog id="deleteModal" class="delete-modal">
    <form method="POST" action="../controllers/deletar_game.php" class="delete-modal-content">
        <?= csrf_campo() ?>
        <input type="hidden" name="id" value="">

        <div class="delete-modal-icon">🗑️</div>
        <h2>Excluir jogo?</h2>
        <p>Tem certeza que deseja excluir <strong class="js-nome"></strong>?</p>
        <span class="delete-warning">Essa ação não poderá ser desfeita.</span>

        <div class="delete-modal-actions">
            <button type="button" class="btn-cancel-delete" data-fechar>Cancelar</button>
            <button type="submit" class="btn-confirm-delete">Excluir jogo</button>
        </div>
    </form>
</dialog>

<script src="../public/js/app.js"></script>
</body>
</html>
