<?php
/**
 * controllers/salvar_cliente.php
 * Recebe o formulário de cliente (cadastrar ou editar), valida e grava.
 * Sem "id" no POST = novo cliente; com "id" = edição.
 */

require_once __DIR__ . '/../models/clientes.php';

exigir_login();

$lista = '../views/ver_clientes.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    flash('erro', 'Requisição inválida. Tente novamente.');
    redirecionar($lista);
}

$model = new Cliente();
$id    = (int)($_POST['id'] ?? 0);
$form  = $id ? "../views/editar_cliente.php?id=$id" : '../views/cadastrar_cliente.php';
$atual = $id ? $model->buscarPorId($id) : null;

if ($id && !$atual) {
    flash('erro', 'Cliente não encontrado.');
    redirecionar($lista);
}

$dados = Cliente::normalizar($_POST);

// Cliente com conta de acesso: o e-mail é o da conta e não muda aqui
$emailEditavel = !$atual || $atual['usuario_id'] === null;
if (!$emailEditavel) {
    $dados['email'] = $atual['email'];
}

$erros = $model->validar($dados, $id ?: null, $emailEditavel);

if ($erros) {
    voltar_com_erros($form, $erros, $dados);
}

try {
    if ($id) {
        $model->editar($id, $dados);
        flash('sucesso', 'Cliente "' . $dados['nome'] . '" atualizado com sucesso!');
    } else {
        $model->cadastrar($dados);
        flash('sucesso', 'Cliente "' . $dados['nome'] . '" cadastrado com sucesso!');
    }
    redirecionar($lista);
} catch (PDOException $e) {
    voltar_com_erros($form, ['Erro ao salvar o cliente no banco de dados.'], $dados);
}
