<?php
/**
 * controllers/autenticar.php
 * Recebe o formulário do login.php, confere e-mail/senha (password_verify),
 * abre a sessão e redireciona:
 *   administrador -> dashboard   |   cliente -> loja (ou a página que ele tentava abrir)
 * Após 5 tentativas erradas, bloqueia novas tentativas por 60 segundos.
 */

require_once __DIR__ . '/../models/usuarios.php';

$form = '../../login.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar($form);
}

$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';

if (!csrf_valido()) {
    voltar_com_erros($form, ['Sessão expirada. Tente novamente.'], ['email' => $email]);
}

if (($_SESSION['bloqueado_ate'] ?? 0) > time()) {
    $falta = $_SESSION['bloqueado_ate'] - time();
    voltar_com_erros($form, ["Muitas tentativas. Aguarde $falta segundo(s) e tente de novo."], ['email' => $email]);
}

if ($email === '' || $senha === '') {
    voltar_com_erros($form, ['Informe e-mail e senha.'], ['email' => $email]);
}

$usuario = (new Usuario())->autenticar($email, $senha);

if (!$usuario) {
    $_SESSION['tentativas'] = ($_SESSION['tentativas'] ?? 0) + 1;

    if ($_SESSION['tentativas'] >= 5) {
        $_SESSION['tentativas']    = 0;
        $_SESSION['bloqueado_ate'] = time() + 60;
    }

    voltar_com_erros($form, ['E-mail ou senha incorretos.'], ['email' => $email]);
}

// Login correto: nova sessão (evita fixação de sessão)
unset($_SESSION['tentativas'], $_SESSION['bloqueado_ate']);
session_regenerate_id(true);
$_SESSION['usuario'] = $usuario;

// Volta para a página da loja que ele tentava abrir (somente caminhos internos)
$voltar = $_SESSION['voltar'] ?? '';
unset($_SESSION['voltar']);

if ($voltar !== '' && preg_match('#^loja/views/[a-z_]+\.php(\?[\w=&%+.-]*)?$#', $voltar)) {
    redirecionar('../../' . $voltar);
}

redirecionar($usuario['tipo'] === 'admin'
    ? '../views/dashboard.php'
    : '../../loja/views/loja.php');
