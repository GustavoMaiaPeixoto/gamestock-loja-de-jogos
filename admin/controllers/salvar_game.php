<?php
/**
 * controllers/salvar_game.php
 * Recebe o formulário de jogo (cadastrar ou editar), valida, trata o upload
 * da imagem e grava. Sem "id" no POST = novo jogo; com "id" = edição.
 */

require_once __DIR__ . '/../models/games.php';

exigir_login();

$lista = '../views/gerenciar_games.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    flash('erro', 'Requisição inválida. Tente novamente.');
    redirecionar($lista);
}

$model = new Game();
$id    = (int)($_POST['id'] ?? 0);
$form  = $id ? "../views/editar_game.php?id=$id" : '../views/cadastrar_game.php';
$atual = $id ? $model->buscarGamePorId($id) : null;

if ($id && !$atual) {
    flash('erro', 'Jogo não encontrado.');
    redirecionar($lista);
}

$dados = [
    'titulo'       => trim($_POST['titulo'] ?? ''),
    'descricao'    => trim($_POST['descricao'] ?? ''),
    'preco'        => trim($_POST['preco'] ?? ''),
    'estoque'      => trim($_POST['estoque'] ?? ''),
    'categoria_id' => trim($_POST['categoria_id'] ?? ''),
];

$game         = new Game($dados + ['id' => $id ?: null]);
$game->imagem = $atual['imagem'] ?? null;
$erros        = $game->validar();
$up           = ['ok' => true, 'arquivo' => null, 'erro' => null];

if (!$erros) {
    $up = Game::enviarImagem($_FILES['imagem'] ?? null);

    if (!$up['ok']) {
        $erros[] = $up['erro'];
    }
}

if ($erros) {
    voltar_com_erros($form, $erros, $dados);
}

if ($up['arquivo']) {
    $game->imagem = $up['arquivo'];                       // imagem nova
} elseif ($atual && !empty($_POST['remover_imagem'])) {
    $game->imagem = null;                                 // remover a atual
}

try {
    if ($id) {
        $game->editarGame();

        // Só apaga a imagem antiga depois que o banco foi atualizado
        if ($atual['imagem'] && $atual['imagem'] !== $game->imagem) {
            Game::apagarImagem($atual['imagem']);
        }

        flash('sucesso', 'Jogo "' . $game->titulo . '" atualizado com sucesso!');
    } else {
        $game->cadastrarGame();
        flash('sucesso', 'Jogo "' . $game->titulo . '" cadastrado com sucesso!');
    }
    redirecionar($lista);
} catch (PDOException $e) {
    Game::apagarImagem($up['arquivo']);
    voltar_com_erros($form, ['Erro ao salvar o jogo no banco de dados.'], $dados);
}
