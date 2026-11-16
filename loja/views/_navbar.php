<?php
/**
 * loja/views/_navbar.php
 * Barra superior da loja. Defina $ativo ('loja' | 'pedidos') antes de incluir.
 */
$ativo = $ativo ?? '';
$u     = usuario_logado();
$qtdCarrinho = Carrinho::quantidade();
?>
<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="loja.php">🎮 Game<span>Stock</span></a>

        <nav class="nav">
            <a href="loja.php" class="<?= $ativo === 'loja' ? 'active' : '' ?>">Loja</a>
            <?php if ($u): ?>
                <a href="meus_pedidos.php" class="<?= $ativo === 'pedidos' ? 'active' : '' ?>">Meus pedidos</a>
                <a href="minha_conta.php" class="<?= $ativo === 'conta' ? 'active' : '' ?>">Minha conta</a>
            <?php endif; ?>
            <?php if ($u && $u['tipo'] === 'admin'): ?>
                <a href="../../admin/views/dashboard.php">Painel admin</a>
            <?php endif; ?>
        </nav>

        <div class="topbar-user">
            <a href="carrinho.php" class="cart-link">🛒 Carrinho
                <?php if ($qtdCarrinho > 0): ?><span class="cart-badge"><?= (int)$qtdCarrinho ?></span><?php endif; ?>
            </a>

            <?php if ($u): ?>
                <span>👤 <?= e($u['nome']) ?></span>
                <a href="../../admin/controllers/logout.php" class="btn-ghost">Sair</a>
            <?php else: ?>
                <a href="../../login.php" class="btn-ghost">Entrar</a>
                <a href="cadastro.php" class="btn-primary btn-sm">Criar conta</a>
            <?php endif; ?>
        </div>
    </div>
</header>
