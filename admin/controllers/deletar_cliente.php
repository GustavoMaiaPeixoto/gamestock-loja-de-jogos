<?php
/**
 * controllers/deletar_cliente.php
 * Exclui um cliente (somente POST + CSRF). Clientes com pedidos não podem ser excluídos.
 */

require_once __DIR__ . '/../models/clientes.php';

exigir_login();

$destino = '../views/ver_clientes.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    flash('erro', 'Requisição inválida. Tente novamente.');
    header("Location: $destino");
    exit;
}

$model   = new Cliente();
$id      = (int)($_POST['id'] ?? 0);
$cliente = $id ? $model->buscarPorId($id) : false;

if (!$cliente) {
    flash('erro', 'Cliente não encontrado.');
    header("Location: $destino");
    exit;
}

try {
    $model->deletar($id);
    flash('sucesso', 'Cliente "' . $cliente['nome'] . '" excluído com sucesso.');
} catch (PDOException $e) {
    // 23000 = violação de chave estrangeira (cliente tem pedidos)
    flash('erro', $e->getCode() === '23000'
        ? 'Este cliente possui pedidos e não pode ser excluído.'
        : 'Não foi possível excluir o cliente.');
}

header("Location: $destino");
exit;
