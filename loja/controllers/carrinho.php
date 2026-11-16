<?php
/**
 * loja/controllers/carrinho.php
 * Ações do carrinho (somente POST): adicionar, atualizar, remover e limpar.
 */

require_once __DIR__ . '/../models/carrinho.php';

$padrao = '../views/carrinho.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    flash('erro', 'Requisição inválida. Tente novamente.');
    header("Location: $padrao");
    exit;
}

$acao = $_POST['acao'] ?? '';
$id   = (int)($_POST['id'] ?? 0);
$qtd  = (int)($_POST['quantidade'] ?? 1);

// Para onde voltar (só nomes de páginas da loja, com query string simples)
$voltar = $_POST['voltar'] ?? '';
$destino = preg_match('/^(loja|jogo|carrinho)\.php(\?[\w=&%+.-]*)?$/', $voltar)
    ? '../views/' . $voltar
    : $padrao;

switch ($acao) {
    case 'adicionar':
        [$tipo, $msg] = Carrinho::adicionar($id, $qtd);
        flash($tipo, $msg);

        // Botão "Comprar agora" vai direto para o checkout
        if (!empty($_POST['comprar_agora']) && $tipo === 'sucesso') {
            $destino = '../views/checkout.php';
        }
        break;

    case 'atualizar':
        [$tipo, $msg] = Carrinho::atualizar($id, $qtd);
        flash($tipo, $msg);
        $destino = $padrao;
        break;

    case 'remover':
        Carrinho::remover($id);
        flash('sucesso', 'Item removido do carrinho.');
        $destino = $padrao;
        break;

    case 'limpar':
        Carrinho::limpar();
        flash('sucesso', 'Carrinho esvaziado.');
        $destino = $padrao;
        break;

    default:
        flash('erro', 'Ação inválida.');
        $destino = $padrao;
}

header("Location: $destino");
exit;
