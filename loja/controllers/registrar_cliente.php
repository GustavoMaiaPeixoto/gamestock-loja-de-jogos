<?php
/**
 * loja/controllers/registrar_cliente.php
 * Registro de cliente pela própria interface: cria a conta (usuarios, tipo "user")
 * e o cadastro de cliente (clientes) na mesma transação, e já deixa o cliente logado.
 */

require_once __DIR__ . '/../../admin/models/usuarios.php';
require_once __DIR__ . '/../../admin/models/clientes.php';

$form = '../views/cadastro.php';

if (usuario_logado()) {
    redirecionar('../views/loja.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar($form);
}

$dados = [
    'nome'     => trim($_POST['nome'] ?? ''),
    'email'    => trim($_POST['email'] ?? ''),
    'telefone' => trim($_POST['telefone'] ?? ''),
];
$senha     = $_POST['senha'] ?? '';
$confirmar = $_POST['confirmar'] ?? '';

if (!csrf_valido()) {
    voltar_com_erros($form, ['Sessão expirada. Recarregue a página e tente novamente.'], $dados);
}

$usuarioModel = new Usuario();
$clienteModel = new Cliente();

// O tipo é sempre "user": ninguém vira admin pelo cadastro público
$erros = $usuarioModel->validar($dados['nome'], $dados['email'], 'user', $senha, true);

if ($senha !== $confirmar) {
    $erros[] = 'A confirmação de senha não confere.';
}

$tel = preg_replace('/\D/', '', $dados['telefone']);
if ($dados['telefone'] === '') {
    $erros[] = 'Informe o telefone.';
} elseif (strlen($tel) < 10 || strlen($tel) > 11) {
    $erros[] = 'Telefone inválido. Use DDD + número.';
}

// E-mail já ligado a outra conta de cliente
$existente = $clienteModel->buscarPorEmail($dados['email']);
if ($existente && $existente['usuario_id'] !== null) {
    $erros[] = 'Já existe uma conta com esse e-mail.';
}

if ($erros) {
    voltar_com_erros($form, array_values(array_unique($erros)), $dados);
}

$pdo = Conexao::getInstance();

try {
    // Grava a conta e o cliente juntos: ou os dois ou nenhum
    $pdo->beginTransaction();
    $usuarioModel->cadastrar($dados['nome'], $dados['email'], $senha, 'user');
    $clienteModel->vincularOuCriar((int)$pdo->lastInsertId(), $dados['nome'], $dados['email'], $dados['telefone']);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    voltar_com_erros($form, ['Erro ao criar a conta. Tente novamente.'], $dados);
}

// Já entra logado
session_regenerate_id(true);
$_SESSION['usuario'] = $usuarioModel->autenticar($dados['email'], $senha);
flash('sucesso', 'Conta criada com sucesso! Bem-vindo(a), ' . $dados['nome'] . '.');

$voltar = $_SESSION['voltar'] ?? '';
unset($_SESSION['voltar']);

redirecionar(preg_match('#^loja/views/[a-z_]+\.php$#', $voltar)
    ? '../../' . $voltar
    : '../views/loja.php');
