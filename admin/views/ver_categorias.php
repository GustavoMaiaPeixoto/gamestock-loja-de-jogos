<?php
/**
 * views/ver_categorias.php
 * Lista de categorias (busca, editar e excluir).
 */

require_once __DIR__ . '/../models/categorias.php';

exigir_login();

$model      = new Categoria();
$busca      = trim($_GET['buscar'] ?? '');
$categorias = $busca !== '' ? $model->buscar($busca) : $model->listar();
$flash      = flash_pegar();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameStock - Categorias</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>

<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="dashboard.php">🎮 Game<span>Stock</span></a>

        <nav class="nav">
            <a href="dashboard.php" class="">Dashboard</a>
            <a href="gerenciar_games.php" class="">Jogos</a>
            <a href="ver_categorias.php" class="active">Categorias</a>
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
            <span class="page-tag">CATEGORIAS</span>
            <h1>Gerenciar Categorias</h1>
            <p>Organize os jogos da loja por categoria.</p>
        </div>
        <a href="cadastrar_categoria.php" class="btn-primary">+ Cadastrar Nova Categoria</a>
    </div>

    <div class="search-box">
        <form method="GET" action="">
            <input type="text" name="buscar" placeholder="Buscar por nome ou descrição..." value="<?= e($busca) ?>">
            <button type="submit" class="btn-search">Buscar</button>
            <?php if ($busca !== ''): ?>
                <a href="ver_categorias.php" class="btn-clear">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th>Jogos</th>
                    <th>Criada em</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($categorias): ?>
                <?php foreach ($categorias as $c): ?>
                    <tr>
                        <td class="id-column"><?= (int)$c['id'] ?></td>
                        <td class="game-title"><?= e($c['nome']) ?></td>
                        <td class="description-column"><?= e($c['descricao']) ?></td>
                        <td><span class="stock-badge"><?= (int)$c['total_games'] ?></span></td>
                        <td><?= e(data_br($c['created_at'])) ?></td>
                        <td class="actions">
                            <a href="editar_categoria.php?id=<?= (int)$c['id'] ?>" class="action-edit" title="Editar categoria">✏️</a>
                            <?php if ((int)$c['total_games'] === 0): ?>
                                <button type="button" class="action-delete" title="Excluir categoria"
                                        data-excluir data-id="<?= (int)$c['id'] ?>" data-nome="<?= e($c['nome']) ?>">🗑️</button>
                            <?php else: ?>
                                <span class="action-lock" title="Possui jogos: não pode ser excluída">🔒</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" class="empty-message">Nenhuma categoria encontrada.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<dialog id="deleteModal" class="delete-modal">
    <form method="POST" action="../controllers/deletar_categoria.php" class="delete-modal-content">
        <?= csrf_campo() ?>
        <input type="hidden" name="id" value="">

        <div class="delete-modal-icon">🗑️</div>
        <h2>Excluir categoria?</h2>
        <p>Tem certeza que deseja excluir <strong class="js-nome"></strong>?</p>
        <span class="delete-warning">Essa ação não poderá ser desfeita.</span>

        <div class="delete-modal-actions">
            <button type="button" class="btn-cancel-delete" data-fechar>Cancelar</button>
            <button type="submit" class="btn-confirm-delete">Excluir categoria</button>
        </div>
    </form>
</dialog>

<script src="../public/js/app.js"></script>
</body>
</html>
