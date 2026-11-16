<?php
/**
 * controllers/deletar_usuario.php
 * Exclui um usuário do banco de dados (somente via POST + token CSRF).
 */

require_once __DIR__ . '/../models/usuarios.php';

exigir_login();

$destino = '../views/ver_usuarios.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    flash('erro', 'Requisição inválida. Tente novamente.');
    header("Location: $destino");
    exit;
}

$id    = (int)($_POST['id'] ?? 0);
$model = new Usuario();
$alvo  = $id ? $model->buscarPorId($id) : false;

if (!$alvo) {
    flash('erro', 'Usuário não encontrado.');
    header("Location: $destino");
    exit;
}

// Regras de segurança
if ($id === (int)usuario_logado()['id']) {
    flash('erro', 'Você não pode excluir o seu próprio usuário.');
    header("Location: $destino");
    exit;
}

if ($alvo['tipo'] === 'admin' && $model->contarAdmins() <= 1) {
    flash('erro', 'Não é possível excluir o último administrador.');
    header("Location: $destino");
    exit;
}

try {
    $model->deletar($id);
    flash('sucesso', 'Usuário "' . $alvo['nome'] . '" excluído com sucesso.');
} catch (PDOException $e) {
    // 23000 = violação de chave estrangeira (usuário tem pedidos)
    flash('erro', $e->getCode() === '23000'
        ? 'Este usuário possui pedidos e não pode ser excluído.'
        : 'Não foi possível excluir o usuário.');
}

header("Location: $destino");
exit;
