<?php
/**
 * views/ver_pedidos.php
 * Lista os pedidos da loja: filtrar, alterar status, editar e excluir.
 */

require_once __DIR__ . '/../models/pedidos.php';

exigir_login();

$model   = new Pedido();
$filtro  = $_GET['status'] ?? '';
$pedidos = $model->listarTodos($filtro);
$resumo  = $model->resumo();
$flash   = flash_pegar();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameStock - Pedidos</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="../../loja/public/css/loja.css">
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
            <a href="ver_usuarios.php" class="">Usuários</a>
            <a href="ver_pedidos.php" class="active">Pedidos</a>
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
            <span class="page-tag">VENDAS</span>
            <h1>Pedidos</h1>
            <p>Acompanhe as compras dos clientes e atualize o andamento.</p>
        </div>
        <a href="cadastrar_pedido.php" class="btn-primary">+ Cadastrar Novo Pedido</a>
    </div>

    <div class="stats">
        <div class="stat-card">
            <span>Total de pedidos</span>
            <strong><?= (int)$resumo['total'] ?></strong>
        </div>
        <div class="stat-card <?= $resumo['pendentes'] > 0 ? 'stat-alerta' : '' ?>">
            <span>Pendentes</span>
            <strong><?= (int)$resumo['pendentes'] ?></strong>
        </div>
        <div class="stat-card">
            <span>Faturamento (sem cancelados)</span>
            <strong><?= moeda($resumo['faturamento']) ?></strong>
        </div>
    </div>

    <div class="search-box">
        <form method="GET" action="">
            <select name="status" onchange="this.form.submit()">
                <option value="">Todos os status</option>
                <?php foreach (Pedido::STATUS as $chave => $rotulo): ?>
                    <option value="<?= e($chave) ?>" <?= $filtro === $chave ? 'selected' : '' ?>><?= e($rotulo) ?></option>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit" class="btn-search">Filtrar</button></noscript>
            <?php if ($filtro !== ''): ?>
                <a href="ver_pedidos.php" class="btn-clear">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Pedido</th>
                    <th>Cliente</th>
                    <th>Data</th>
                    <th>Pagamento</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Itens / entrega</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($pedidos): ?>
                <?php foreach ($pedidos as $p): ?>
                    <tr>
                        <td class="id-column">#<?= (int)$p['id'] ?></td>
                        <td>
                            <span class="game-title"><?= e($p['cliente_nome']) ?></span><br>
                            <span class="nota"><?= e($p['cliente_email']) ?></span>
                        </td>
                        <td><?= e(data_br($p['created_at'])) ?></td>
                        <td><?= e(Pedido::PAGAMENTOS[$p['pagamento']]) ?></td>
                        <td class="price-column"><?= moeda($p['total']) ?></td>
                        <td><span class="status status-<?= e($p['status']) ?>"><?= e(Pedido::STATUS[$p['status']]) ?></span></td>

                        <td>
                            <details>
                                <summary><?= count($p['itens']) ?> item(ns)</summary>
                                <ul class="lista-itens">
                                    <?php foreach ($p['itens'] as $i): ?>
                                        <li><?= (int)$i['quantidade'] ?>× <?= e($i['titulo']) ?> — <?= moeda($i['preco_unitario']) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <p class="nota"><strong>Entrega:</strong> <?= e($p['endereco']) ?></p>
                            </details>
                        </td>

                        <td class="actions">
                            <?php if ($p['status'] !== 'cancelado'): ?>
                                <a href="editar_pedido.php?id=<?= (int)$p['id'] ?>" class="action-edit" title="Editar pedido">✏️</a>
                            <?php endif; ?>
                            <button type="button" class="action-delete" title="Excluir pedido"
                                    data-excluir data-id="<?= (int)$p['id'] ?>" data-nome="Pedido #<?= (int)$p['id'] ?>">🗑️</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" class="empty-message">Nenhum pedido encontrado.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<dialog id="deleteModal" class="delete-modal">
    <form method="POST" action="../controllers/deletar_pedido.php" class="delete-modal-content">
        <?= csrf_campo() ?>
        <input type="hidden" name="id" value="">

        <div class="delete-modal-icon">🗑️</div>
        <h2>Excluir pedido?</h2>
        <p>Tem certeza que deseja excluir <strong class="js-nome"></strong>?</p>
        <span class="delete-warning">Pedidos não cancelados devolvem os jogos ao estoque. Essa ação não poderá ser desfeita.</span>

        <div class="delete-modal-actions">
            <button type="button" class="btn-cancel-delete" data-fechar>Cancelar</button>
            <button type="submit" class="btn-confirm-delete">Excluir pedido</button>
        </div>
    </form>
</dialog>

<script src="../public/js/app.js"></script>
</body>
</html>
