<?php
/**
 * views/dashboard.php
 * Painel inicial do administrador: números gerais, vendas recentes e alertas.
 */

require_once __DIR__ . '/../models/pedidos.php';
require_once __DIR__ . '/../models/relatorios.php';

exigir_login();

$rel      = new Relatorio();
$resumo   = $rel->resumoGeral();
$semana   = $rel->vendasUltimosDias(7);
$maximo   = max(1, max(array_column($semana, 'faturamento')));
[$de, $ate] = Relatorio::periodo(date('Y-m-d', strtotime('-29 days')), date('Y-m-d'));
$top      = $rel->topJogos($de, $ate, 5);
$baixo    = $rel->estoqueBaixo(5);
$ultimos  = $rel->ultimosPedidos(5);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameStock - Dashboard</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="../../loja/public/css/loja.css">
</head>
<body>

<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="dashboard.php">🎮 Game<span>Stock</span></a>

        <nav class="nav">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="gerenciar_games.php" class="">Jogos</a>
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

    <?php flash_exibir(); ?>

    <div class="page-header">
        <div>
            <span class="page-tag">DASHBOARD</span>
            <h1>Olá, <?= e(explode(' ', usuario_logado()['nome'])[0]) ?> 👋</h1>
            <p>Visão geral da loja.</p>
        </div>
        <a href="relatorio_vendas.php" class="btn-primary">Ver relatório de vendas</a>
    </div>

    <div class="stats">
        <div class="stat-card">
            <span>Faturamento (sem cancelados)</span>
            <strong><?= moeda($resumo['faturamento']) ?></strong>
        </div>
        <div class="stat-card <?= $resumo['pendentes'] > 0 ? 'stat-alerta' : '' ?>">
            <span>Pedidos (<?= (int)$resumo['pendentes'] ?> pendentes)</span>
            <strong><?= (int)$resumo['pedidos'] ?></strong>
        </div>
        <div class="stat-card">
            <span>Jogos / unidades em estoque</span>
            <strong><?= (int)$resumo['jogos'] ?> / <?= (int)$resumo['unidades'] ?></strong>
        </div>
        <div class="stat-card">
            <span>Clientes</span>
            <strong><?= (int)$resumo['clientes'] ?></strong>
        </div>
    </div>

    <div class="dash-grid">

        <section class="box">
            <h3>Vendas dos últimos 7 dias</h3>
            <div class="bars">
                <?php foreach ($semana as $d): ?>
                    <div class="bar-col" title="<?= e(date('d/m', strtotime($d['dia']))) ?>: <?= e(moeda($d['faturamento'])) ?>">
                        <span class="bar-valor"><?= $d['faturamento'] > 0 ? e(number_format($d['faturamento'], 0, ',', '.')) : '' ?></span>
                        <div class="bar" style="height: <?= max(3, round($d['faturamento'] / $maximo * 100)) ?>%"></div>
                        <span class="bar-dia"><?= e(date('d/m', strtotime($d['dia']))) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="box">
            <h3>Mais vendidos (30 dias)</h3>
            <?php if ($top): ?>
                <ol class="lista-rank">
                    <?php foreach ($top as $t): ?>
                        <li><span><?= e($t['titulo']) ?></span><strong><?= (int)$t['unidades'] ?> un.</strong></li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <p class="nota">Ainda não há vendas no período.</p>
            <?php endif; ?>
        </section>

        <section class="box">
            <h3>⚠️ Estoque baixo (até 5 unidades)</h3>
            <?php if ($baixo): ?>
                <ul class="lista-rank">
                    <?php foreach ($baixo as $b): ?>
                        <li>
                            <a href="editar_game.php?id=<?= (int)$b['id'] ?>"><?= e($b['titulo']) ?></a>
                            <span class="stock-badge <?= (int)$b['estoque'] === 0 ? 'stock-zero' : 'stock-baixo' ?>">
                                <?= (int)$b['estoque'] === 0 ? 'Esgotado' : (int)$b['estoque'] ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="nota">Nenhum jogo com estoque baixo. 👍</p>
            <?php endif; ?>
        </section>

        <section class="box">
            <h3>Últimos pedidos</h3>
            <?php if ($ultimos): ?>
                <ul class="lista-rank">
                    <?php foreach ($ultimos as $p): ?>
                        <li>
                            <span>#<?= (int)$p['id'] ?> · <?= e($p['cliente_nome']) ?></span>
                            <span>
                                <strong><?= moeda($p['total']) ?></strong>
                                <span class="status status-<?= e($p['status']) ?>"><?= e(Pedido::STATUS[$p['status']]) ?></span>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a href="ver_pedidos.php" class="btn-link">Ver todos os pedidos</a>
            <?php else: ?>
                <p class="nota">Nenhum pedido ainda.</p>
            <?php endif; ?>
        </section>

    </div>

    <div class="atalhos">
        <a href="cadastrar_game.php" class="btn-secondary">+ Novo jogo</a>
        <a href="cadastrar_pedido.php" class="btn-secondary">+ Novo pedido</a>
        <a href="cadastrar_cliente.php" class="btn-secondary">+ Novo cliente</a>
        <a href="ver_games.php" class="btn-secondary">Ver catálogo</a>
    </div>

</div>

<script src="../public/js/app.js"></script>
</body>
</html>
