<?php
/**
 * controllers/deletar_categoria.php
 * Exclui uma categoria (somente POST + CSRF). Bloqueia se ainda houver jogos nela.
 */

require_once __DIR__ . '/../models/categorias.php';

exigir_login();

$destino = '../views/ver_categorias.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    flash('erro', 'Requisição inválida. Tente novamente.');
    header("Location: $destino");
    exit;
}

$model = new Categoria();
$id    = (int)($_POST['id'] ?? 0);
$cat   = $id ? $model->buscarPorId($id) : false;

try {
    $model->deletar($id);
    flash('sucesso', 'Categoria "' . $cat['nome'] . '" excluída com sucesso.');
} catch (RuntimeException $e) {
    flash('erro', $e->getMessage());
} catch (PDOException $e) {
    flash('erro', 'Não foi possível excluir a categoria.');
}

header("Location: $destino");
exit;
