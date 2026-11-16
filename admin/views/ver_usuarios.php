<?php
/**
 * views/ver_usuarios.php
 * Exibe a lista de usuários cadastrados (com ações de editar e excluir).
 */

require_once __DIR__ . '/../models/usuarios.php';

exigir_login();

$model    = new Usuario();
$busca    = trim($_GET['buscar'] ?? '');
$usuarios = $busca !== '' ? $model->buscar($busca) : $model->listar();
$meuId    = (int)usuario_logado()['id'];
$flash    = flash_pegar();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameStock - Usuários</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>

<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="dashboard.php">🎮 Game<span>Stock</span></a>

        <nav class="nav">
            <a href="dashboard.php" class="">Dashboard</a>
            <a href="gerenciar_games.php" class="">Jogos</a>
            <a href="ver_categorias.php" class="">Categorias</a>
            <a href="ver_clientes.php" class="">Clientes</a>
            <a href="ver_usuarios.php" class="active">Usuários</a>
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
            <span class="page-tag">USUÁRIOS</span>
            <h1>Gerenciar Usuários</h1>
            <p>Visualize, pesquise e gerencie os usuários do sistema.</p>
        </div>
        <a href="cadastrar_usuario.php" class="btn-primary">+ Cadastrar Novo Usuário</a>
    </div>

    <div class="search-box">
        <form method="GET" action="">
            <input type="text" name="buscar" placeholder="Buscar por nome ou e-mail..." value="<?= e($busca) ?>">
            <button type="submit" class="btn-search">Buscar</button>
            <?php if ($busca !== ''): ?>
                <a href="ver_usuarios.php" class="btn-clear">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Tipo</th>
                    <th>Criado em</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($usuarios): ?>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td class="id-column"><?= (int)$u['id'] ?></td>
                        <td class="game-title">
                            <?= e($u['nome']) ?>
                            <?php if ((int)$u['id'] === $meuId): ?><span class="tag-voce">você</span><?php endif; ?>
                        </td>
                        <td><?= e($u['email']) ?></td>
                        <td><span class="badge badge-<?= e($u['tipo']) ?>"><?= $u['tipo'] === 'admin' ? 'Administrador' : 'Usuário' ?></span></td>
                        <td><?= e(data_br($u['created_at'])) ?></td>
                        <td class="actions">
                            <a href="editar_usuario.php?id=<?= (int)$u['id'] ?>" class="action-edit" title="Editar usuário">✏️</a>
                            <?php if ((int)$u['id'] !== $meuId): ?>
                                <button type="button" class="action-delete" title="Excluir usuário"
                                        data-excluir data-id="<?= (int)$u['id'] ?>" data-nome="<?= e($u['nome']) ?>">🗑️</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" class="empty-message">Nenhum usuário encontrado.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<dialog id="deleteModal" class="delete-modal">
    <form method="POST" action="../controllers/deletar_usuario.php" class="delete-modal-content">
        <?= csrf_campo() ?>
        <input type="hidden" name="id" value="">

        <div class="delete-modal-icon">🗑️</div>
        <h2>Excluir usuário?</h2>
        <p>Tem certeza que deseja excluir <strong class="js-nome"></strong>?</p>
        <span class="delete-warning">Essa ação não poderá ser desfeita.</span>

        <div class="delete-modal-actions">
            <button type="button" class="btn-cancel-delete" data-fechar>Cancelar</button>
            <button type="submit" class="btn-confirm-delete">Excluir usuário</button>
        </div>
    </form>
</dialog>

<script src="../public/js/app.js"></script>
</body>
</html>
