<?php
/**
 * loja/controllers/salvar_conta.php
 * O cliente logado atualiza os próprios dados (telefone, CPF, endereço...).
 */

require_once __DIR__ . '/../../admin/models/clientes.php';

exigir_cliente('loja/views/minha_conta.php');

$form = '../views/minha_conta.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    flash('erro', 'Requisição inválida. Tente novamente.');
    redirecionar($form);
}

$model   = new Cliente();
$cliente = $model->garantirPorUsuario(usuario_logado());   // sempre o cliente do usuário logado
$id      = (int)$cliente['id'];

$dados          = Cliente::normalizar($_POST);
$dados['email'] = $cliente['email'];                       // o e-mail é o da conta

$erros = $model->validar($dados, $id, false);

if ($erros) {
    voltar_com_erros($form, $erros, $dados);
}

try {
    $model->editar($id, $dados);

    // Mantém o nome da sessão atualizado
    $_SESSION['usuario']['nome'] = $dados['nome'];

    flash('sucesso', 'Seus dados foram atualizados.');
    redirecionar($form);
} catch (PDOException $e) {
    voltar_com_erros($form, ['Erro ao salvar seus dados. Tente novamente.'], $dados);
}
