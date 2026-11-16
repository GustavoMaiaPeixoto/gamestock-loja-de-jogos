<?php
/**
 * loja/controllers/finalizar_compra.php
 * Transforma o carrinho em pedido (baixa o estoque e limpa o carrinho).
 */

require_once __DIR__ . '/../models/carrinho.php';
require_once __DIR__ . '/../../admin/models/pedidos.php';
require_once __DIR__ . '/../../admin/models/clientes.php';

exigir_cliente('loja/views/checkout.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    flash('erro', 'Requisição inválida. Tente novamente.');
    header('Location: ../views/checkout.php');
    exit;
}

$pagamento = $_POST['pagamento'] ?? '';
$endereco  = trim($_POST['endereco'] ?? '');

// Guarda o que foi digitado para preencher de novo se der erro
$_SESSION['checkout'] = ['pagamento' => $pagamento, 'endereco' => $endereco];

$pedidoModel = new Pedido();
$erros       = $pedidoModel->validar($pagamento, $endereco);

if ($erros) {
    flash('erro', implode(' ', $erros));
    header('Location: ../views/checkout.php');
    exit;
}

$carrinho = Carrinho::itens();

if (!$carrinho['itens']) {
    flash('erro', 'Seu carrinho está vazio.');
    header('Location: ../views/carrinho.php');
    exit;
}

try {
    // Todo pedido pertence a um cliente (criado automaticamente se ainda não existir)
    $cliente  = (new Cliente())->garantirPorUsuario(usuario_logado());
    $pedidoId = $pedidoModel->criar((int)$cliente['id'], $carrinho['itens'], $pagamento, $endereco);

    Carrinho::limpar();
    unset($_SESSION['checkout']);

    header('Location: ../views/pedido_confirmado.php?id=' . $pedidoId);
    exit;

} catch (RuntimeException $e) {
    flash('erro', $e->getMessage());
    header('Location: ../views/carrinho.php');
    exit;
} catch (PDOException $e) {
    flash('erro', 'Não foi possível concluir o pedido. Tente novamente.');
    header('Location: ../views/checkout.php');
    exit;
}
