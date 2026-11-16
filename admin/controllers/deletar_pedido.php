<?php
/**
 * controllers/deletar_pedido.php
 * Exclui um pedido (somente POST + CSRF). Se não estava cancelado, o estoque volta.
 */

require_once __DIR__ . '/../models/pedidos.php';

exigir_login();

$destino = '../views/ver_pedidos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    flash('erro', 'Requisição inválida. Tente novamente.');
    header("Location: $destino");
    exit;
}

$id = (int)($_POST['id'] ?? 0);

try {
    (new Pedido())->deletar($id);
    flash('sucesso', 'Pedido #' . $id . ' excluído com sucesso.');
} catch (RuntimeException $e) {
    flash('erro', $e->getMessage());
} catch (PDOException $e) {
    flash('erro', 'Não foi possível excluir o pedido.');
}

header("Location: $destino");
exit;
