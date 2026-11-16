<?php
/**
 * views/relatorio_vendas.php
 * Relatório de vendas por período: totais, por dia, mais vendidos,
 * por categoria e por forma de pagamento. Pode ser impresso ou exportado em CSV.
 */

require_once __DIR__ . '/../models/pedidos.php';
require_once __DIR__ . '/../models/relatorios.php';

exigir_login();

[$de, $ate] = Relatorio::periodo($_GET['de'] ?? null, $_GET['ate'] ?? null);

$rel        = new Relatorio();
$totais     = $rel->totais($de, $ate);
$porDia     = $rel->vendasPorDia($de, $ate);
$top        = $rel->topJogos($de, $ate, 10);
$porCateg   = $rel->vendasPorCategoria($de, $ate);
$porPagto   = $rel->vendasPorPagamento($de, $ate);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameStock - Relatório de vendas</title>
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
            <a href="ver_pedidos.php" class="">Pedidos</a>
            <a href="relatorio_vendas.php" class="active">Relatório</a>
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
            <span class="page-tag">RELATÓRIO</span>
            <h1>Relatório de vendas</h1>
            <p>Período: <?= e(date('d/m/Y', strtotime($de))) ?> a <?= e(date('d/m/Y', strtotime($ate))) ?> (pedidos cancelados não entram).</p>
        </div>
        <div class="no-print detalhe-botoes">
            <button type="button" class="btn-secondary" onclick="window.print()">🖨️ Imprimir</button>
            <a href="../controllers/exportar_vendas.php?de=<?= e($de) ?>&ate=<?= e($ate) ?>" class="btn-primary">⬇ Exportar CSV</a>
        </div>
    </div>

    <div class="search-box no-print">
        <form method="GET" action="">
            <label class="nota" for="de">De</label>
            <input type="date" id="de" name="de" value="<?= e($de) ?>" max="<?= e(date('Y-m-d')) ?>">
            <label class="nota" for="ate">até</label>
            <input type="date" id="ate" name="ate" value="<?= e($ate) ?>" max="<?= e(date('Y-m-d')) ?>">
            <button type="submit" class="btn-search">Filtrar</button>
            <a href="relatorio_vendas.php" class="btn-clear">Este mês</a>
        </form>
    </div>

    <div class="stats">
        <div class="stat-card"><span>Pedidos</span><strong><?= (int)$totais['pedidos'] ?></strong></div>
        <div class="stat-card"><span>Jogos vendidos</span><strong><?= (int)$totais['itens'] ?></strong></div>
        <div class="stat-card"><span>Faturamento</span><strong><?= moeda($totais['faturamento']) ?></strong></div>
        <div class="stat-card"><span>Ticket médio</span><strong><?= moeda($totais['ticket']) ?></strong></div>
    </div>

    <div class="dash-grid">

        <section class="box">
            <h3>Vendas por dia</h3>
            <?php if ($porDia): ?>
                <table class="tabela-simples">
                    <thead><tr><th>Dia</th><th>Pedidos</th><th>Faturamento</th></tr></thead>
                    <tbody>
                    <?php foreach ($porDia as $l): ?>
                        <tr>
                            <td><?= e(date('d/m/Y', strtotime($l['dia']))) ?></td>
                            <td><?= (int)$l['pedidos'] ?></td>
                            <td class="price-column"><?= moeda($l['faturamento']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="nota">Sem vendas no período.</p>
            <?php endif; ?>
        </section>

        <section class="box">
            <h3>Jogos mais vendidos</h3>
            <?php if ($top): ?>
                <table class="tabela-simples">
                    <thead><tr><th>Jogo</th><th>Unid.</th><th>Valor</th></tr></thead>
                    <tbody>
                    <?php foreach ($top as $l): ?>
                        <tr>
                            <td><?= e($l['titulo']) ?></td>
                            <td><?= (int)$l['unidades'] ?></td>
                            <td class="price-column"><?= moeda($l['valor']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="nota">Sem vendas no período.</p>
            <?php endif; ?>
        </section>

        <section class="box">
            <h3>Vendas por categoria</h3>
            <?php if ($porCateg): ?>
                <table class="tabela-simples">
                    <thead><tr><th>Categoria</th><th>Unid.</th><th>Valor</th></tr></thead>
                    <tbody>
                    <?php foreach ($porCateg as $l): ?>
                        <tr>
                            <td><?= e($l['categoria']) ?></td>
                            <td><?= (int)$l['unidades'] ?></td>
                            <td class="price-column"><?= moeda($l['valor']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="nota">Sem vendas no período.</p>
            <?php endif; ?>
        </section>

        <section class="box">
            <h3>Forma de pagamento</h3>
            <?php if ($porPagto): ?>
                <table class="tabela-simples">
                    <thead><tr><th>Forma</th><th>Pedidos</th><th>Valor</th></tr></thead>
                    <tbody>
                    <?php foreach ($porPagto as $l): ?>
                        <tr>
                            <td><?= e(Pedido::PAGAMENTOS[$l['pagamento']]) ?></td>
                            <td><?= (int)$l['pedidos'] ?></td>
                            <td class="price-column"><?= moeda($l['valor']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="nota">Sem vendas no período.</p>
            <?php endif; ?>
        </section>

    </div>

</div>

</body>
</html>
