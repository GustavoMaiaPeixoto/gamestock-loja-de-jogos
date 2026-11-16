<?php
/**
 * controllers/salvar_pedido.php
 * Recebe o formulário de pedido do admin.
 *  - Sem "id": cria o pedido para um cliente (baixa o estoque).
 *  - Com "id": edita pagamento, endereço e status.
 */

require_once __DIR__ . '/../models/pedidos.php';
require_once __DIR__ . '/../models/clientes.php';
require_once __DIR__ . '/../models/games.php';

exigir_login();

$lista = '../views/ver_pedidos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    flash('erro', 'Requisição inválida. Tente novamente.');
    redirecionar($lista);
}

$model     = new Pedido();
$id        = (int)($_POST['id'] ?? 0);
$pagamento = $_POST['pagamento'] ?? '';
$endereco  = trim($_POST['endereco'] ?? '');

/* ---------------------- EDITAR ---------------------- */
if ($id) {
    $form   = "../views/editar_pedido.php?id=$id";
    $status = $_POST['status'] ?? '';
    $dados  = ['pagamento' => $pagamento, 'endereco' => $endereco, 'status' => $status];

    try {
        $model->editar($id, $pagamento, $endereco, $status);
        flash('sucesso', 'Pedido #' . $id . ' atualizado com sucesso!');
        redirecionar($lista);
    } catch (RuntimeException $e) {
        voltar_com_erros($form, [$e->getMessage()], $dados);
    } catch (PDOException $e) {
        voltar_com_erros($form, ['Erro ao atualizar o pedido no banco de dados.'], $dados);
    }
}

/* ---------------------- CRIAR ---------------------- */
$form      = '../views/cadastrar_pedido.php';
$clienteId = (int)($_POST['cliente_id'] ?? 0);
$gameIds   = is_array($_POST['game_id'] ?? null) ? $_POST['game_id'] : [];
$qtds      = is_array($_POST['quantidade'] ?? null) ? $_POST['quantidade'] : [];

$dados = [
    'cliente_id' => $clienteId,
    'pagamento'  => $pagamento,
    'endereco'   => $endereco,
    'game_id'    => $gameIds,
    'quantidade' => $qtds,
];

$erros     = [];
$gameModel = new Game();

if (!$clienteId || !(new Cliente())->buscarPorId($clienteId)) {
    $erros[] = 'Escolha o cliente do pedido.';
}

$erros = array_merge($erros, $model->validar($pagamento, $endereco));

// Junta jogos repetidos somando as quantidades
$somas = [];
foreach ($gameIds as $k => $gid) {
    $gid = (int)$gid;
    if ($gid <= 0) {
        continue;
    }

    $q = (int)($qtds[$k] ?? 0);
    if ($q <= 0) {
        $erros[] = 'A quantidade de cada jogo deve ser maior que zero.';
        continue;
    }

    $somas[$gid] = ($somas[$gid] ?? 0) + $q;
}

$itens = [];
foreach ($somas as $gid => $q) {
    $g = $gameModel->buscarGamePorId($gid);

    if (!$g) {
        $erros[] = 'Um dos jogos escolhidos não existe mais.';
        continue;
    }

    $itens[] = ['id' => $g['id'], 'titulo' => $g['titulo'], 'preco' => $g['preco'], 'quantidade' => $q];
}

if (!$itens) {
    $erros[] = 'Adicione pelo menos um jogo ao pedido.';
}

if ($erros) {
    voltar_com_erros($form, array_values(array_unique($erros)), $dados);
}

try {
    $novoId = $model->criar($clienteId, $itens, $pagamento, $endereco);
    flash('sucesso', 'Pedido #' . $novoId . ' cadastrado com sucesso!');
    redirecionar($lista);
} catch (RuntimeException $e) {
    voltar_com_erros($form, [$e->getMessage()], $dados);
} catch (PDOException $e) {
    voltar_com_erros($form, ['Erro ao salvar o pedido no banco de dados.'], $dados);
}
