<?php
/**
 * controllers/deletar_game.php
 * Exclui um jogo do banco de dados (somente via POST + token CSRF).
 */

require_once __DIR__ . '/../models/games.php';

exigir_login();

$destino = '../views/gerenciar_games.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    flash('erro', 'Requisição inválida. Tente novamente.');
    header("Location: $destino");
    exit;
}

$id    = (int)($_POST['id'] ?? 0);
$model = new Game();
$game  = $id ? $model->buscarGamePorId($id) : false;

if (!$game) {
    flash('erro', 'Jogo não encontrado.');
    header("Location: $destino");
    exit;
}

try {
    $model->id = $id;
    $model->deletarGame();
    Game::apagarImagem($game['imagem']);

    flash('sucesso', 'Jogo "' . $game['titulo'] . '" excluído com sucesso.');
} catch (PDOException $e) {
    flash('erro', 'Não foi possível excluir o jogo.');
}

header("Location: $destino");
exit;
