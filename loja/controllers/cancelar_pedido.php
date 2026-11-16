<?php
/**
 * loja/controllers/cancelar_pedido.php
 * O cliente cancela um pedido seu que ainda está pendente (o estoque volta).
 */

require_once __DIR__ . '/../../admin/models/pedidos.php';

exigir_cliente('loja/views/meus_pedidos.php');

$destino = '../views/meus_pedidos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    flash('erro', 'Requisição inválida. Tente novamente.');
    header("Location: $destino");
    exit;
}

$model  = new Pedido();
$pedido = $model->buscarPorId((int)($_POST['id'] ?? 0), (int)usuario_logado()['id']);

if (!$pedido) {
    flash('erro', 'Pedido não encontrado.');
} elseif ($pedido['status'] !== 'pendente') {
    flash('erro', 'Só é possível cancelar pedidos pendentes.');
} else {
    try {
        $model->atualizarStatus($pedido['id'], 'cancelado');
        flash('sucesso', 'Pedido #' . $pedido['id'] . ' cancelado.');
    } catch (Throwable $e) {
        flash('erro', 'Não foi possível cancelar o pedido.');
    }
}

header("Location: $destino");
exit;
