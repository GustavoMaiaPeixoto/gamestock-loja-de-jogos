<?php
/**
 * controllers/salvar_usuario.php
 * Recebe o formulário de usuário (cadastrar ou editar), valida e grava.
 * Sem "id" no POST = novo usuário; com "id" = edição (senha em branco mantém a atual).
 */

require_once __DIR__ . '/../models/usuarios.php';

exigir_login();

$lista = '../views/ver_usuarios.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    flash('erro', 'Requisição inválida. Tente novamente.');
    redirecionar($lista);
}

$model = new Usuario();
$id    = (int)($_POST['id'] ?? 0);
$form  = $id ? "../views/editar_usuario.php?id=$id" : '../views/cadastrar_usuario.php';
$atual = $id ? $model->buscarPorId($id) : null;

if ($id && !$atual) {
    flash('erro', 'Usuário não encontrado.');
    redirecionar($lista);
}

$dados = [
    'nome'  => trim($_POST['nome'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'tipo'  => $_POST['tipo'] ?? 'user',
];
$senha     = $_POST['senha'] ?? '';
$confirmar = $_POST['confirmar'] ?? '';

$erros = $model->validar($dados['nome'], $dados['email'], $dados['tipo'], $senha, !$id, $id ?: null);

if ($senha !== $confirmar) {
    $erros[] = 'A confirmação de senha não confere.';
}

if ($atual) {
    // Regras de segurança da edição
    if ($atual['tipo'] === 'admin' && $dados['tipo'] !== 'admin') {
        if ($id === (int)usuario_logado()['id']) {
            $erros[] = 'Você não pode remover o seu próprio acesso de administrador.';
        } elseif ($model->contarAdmins() <= 1) {
            $erros[] = 'Este é o último administrador: ele não pode virar usuário comum.';
        }
    }
}

if ($erros) {
    voltar_com_erros($form, $erros, $dados);
}

try {
    if ($id) {
        $model->editar($id, $dados['nome'], $dados['email'], $dados['tipo'], $senha);

        // Mantém a sessão atualizada se o próprio admin se editou
        if ($id === (int)usuario_logado()['id']) {
            $_SESSION['usuario'] = $model->buscarPorId($id);
        }

        flash('sucesso', 'Usuário "' . $dados['nome'] . '" atualizado com sucesso!');
    } else {
        $model->cadastrar($dados['nome'], $dados['email'], $senha, $dados['tipo']);
        flash('sucesso', 'Usuário "' . $dados['nome'] . '" cadastrado com sucesso!');
    }
    redirecionar($lista);
} catch (PDOException $e) {
    voltar_com_erros($form, ['Erro ao salvar o usuário no banco de dados.'], $dados);
}
