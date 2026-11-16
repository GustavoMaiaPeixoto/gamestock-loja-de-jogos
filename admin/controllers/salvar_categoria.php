<?php
/**
 * controllers/salvar_categoria.php
 * Recebe o formulário de categoria (cadastrar ou editar), valida e grava.
 * Sem "id" no POST = nova categoria; com "id" = edição.
 */

require_once __DIR__ . '/../models/categorias.php';

exigir_login();

$lista = '../views/ver_categorias.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    flash('erro', 'Requisição inválida. Tente novamente.');
    redirecionar($lista);
}

$model = new Categoria();
$id    = (int)($_POST['id'] ?? 0);
$form  = $id ? "../views/editar_categoria.php?id=$id" : '../views/cadastrar_categoria.php';

if ($id && !$model->buscarPorId($id)) {
    flash('erro', 'Categoria não encontrada.');
    redirecionar($lista);
}

$dados = [
    'nome'      => trim($_POST['nome'] ?? ''),
    'descricao' => trim($_POST['descricao'] ?? ''),
];

$erros = $model->validar($dados['nome'], $dados['descricao'], $id ?: null);

if ($erros) {
    voltar_com_erros($form, $erros, $dados);
}

try {
    if ($id) {
        $model->editar($id, $dados['nome'], $dados['descricao']);
        flash('sucesso', 'Categoria "' . $dados['nome'] . '" atualizada com sucesso!');
    } else {
        $model->cadastrar($dados['nome'], $dados['descricao']);
        flash('sucesso', 'Categoria "' . $dados['nome'] . '" cadastrada com sucesso!');
    }
    redirecionar($lista);
} catch (PDOException $e) {
    voltar_com_erros($form, ['Erro ao salvar a categoria no banco de dados.'], $dados);
}
