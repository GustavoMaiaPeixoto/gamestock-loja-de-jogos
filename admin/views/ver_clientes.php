<?php
/**
 * views/ver_clientes.php
 * Lista de clientes cadastrados (busca, editar e excluir).
 */

require_once __DIR__ . '/../models/clientes.php';

exigir_login();

$model    = new Cliente();
$busca    = trim($_GET['buscar'] ?? '');
$clientes = $busca !== '' ? $model->buscar($busca) : $model->listar();
$flash    = flash_pegar();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameStock - Clientes</title>
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
            <a href="ver_clientes.php" class="active">Clientes</a>
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
            <span class="page-tag">CLIENTES</span>
            <h1>Gerenciar Clientes</h1>
            <p>Quem compra na loja. Clientes com conta entram sozinhos; os demais você cadastra aqui.</p>
        </div>
        <a href="cadastrar_cliente.php" class="btn-primary">+ Cadastrar Novo Cliente</a>
    </div>

    <div class="search-box">
        <form method="GET" action="">
            <input type="text" name="buscar" placeholder="Buscar por nome, e-mail ou CPF..." value="<?= e($busca) ?>">
            <button type="submit" class="btn-search">Buscar</button>
            <?php if ($busca !== ''): ?>
                <a href="ver_clientes.php" class="btn-clear">Limpar</a>
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
                    <th>CPF</th>
                    <th>Telefone</th>
                    <th>Cidade/UF</th>
                    <th>Conta</th>
                    <th>Pedidos</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($clientes): ?>
                <?php foreach ($clientes as $c): ?>
                    <tr>
                        <td class="id-column"><?= (int)$c['id'] ?></td>
                        <td class="game-title"><?= e($c['nome']) ?></td>
                        <td><?= e($c['email']) ?></td>
                        <td><?= $c['cpf'] ? e(Cliente::formatarCpf($c['cpf'])) : '<span class="no-image">—</span>' ?></td>
                        <td><?= $c['telefone'] ? e($c['telefone']) : '<span class="no-image">—</span>' ?></td>
                        <td><?= $c['cidade'] ? e($c['cidade'] . ($c['estado'] ? '/' . $c['estado'] : '')) : '<span class="no-image">—</span>' ?></td>
                        <td>
                            <?php if ($c['usuario_id'] !== null): ?>
                                <span class="badge badge-sim">Com conta</span>
                            <?php else: ?>
                                <span class="badge badge-nao">Sem conta</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int)$c['total_pedidos'] ?></td>
                        <td class="actions">
                            <a href="editar_cliente.php?id=<?= (int)$c['id'] ?>" class="action-edit" title="Editar cliente">✏️</a>
                            <?php if ((int)$c['total_pedidos'] === 0): ?>
                                <button type="button" class="action-delete" title="Excluir cliente"
                                        data-excluir data-id="<?= (int)$c['id'] ?>" data-nome="<?= e($c['nome']) ?>">🗑️</button>
                            <?php else: ?>
                                <span class="action-lock" title="Possui pedidos: não pode ser excluído">🔒</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="9" class="empty-message">Nenhum cliente encontrado.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<dialog id="deleteModal" class="delete-modal">
    <form method="POST" action="../controllers/deletar_cliente.php" class="delete-modal-content">
        <?= csrf_campo() ?>
        <input type="hidden" name="id" value="">

        <div class="delete-modal-icon">🗑️</div>
        <h2>Excluir cliente?</h2>
        <p>Tem certeza que deseja excluir <strong class="js-nome"></strong>?</p>
        <span class="delete-warning">Essa ação não poderá ser desfeita.</span>

        <div class="delete-modal-actions">
            <button type="button" class="btn-cancel-delete" data-fechar>Cancelar</button>
            <button type="submit" class="btn-confirm-delete">Excluir cliente</button>
        </div>
    </form>
</dialog>

<script src="../public/js/app.js"></script>
</body>
</html>
