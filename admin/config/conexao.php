<?php
/**
 * conexao.php
 * - Classe Conexao: conexão PDO com o MySQL (padrão Singleton).
 * - Controle de sessão.
 * - Funções auxiliares usadas em todo o sistema (login, flash, CSRF, formatação).
 */

/* =========================================================
   SESSÃO (cookie só HTTP, sem acesso por JavaScript)
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}


/* =========================================================
   CONEXÃO COM O BANCO (PDO)
========================================================= */

class Conexao
{
    const HOST    = 'localhost';
    const BANCO   = 'loja_games';
    const USUARIO = 'root';
    const SENHA   = '';

    private static ?PDO $instancia = null;

    // Impede criar objetos de fora: use Conexao::getInstance()
    private function __construct() {}

    public static function getInstance(): PDO
    {
        if (self::$instancia === null) {
            try {
                self::$instancia = new PDO(
                    'mysql:host=' . self::HOST . ';dbname=' . self::BANCO . ';charset=utf8mb4',
                    self::USUARIO,
                    self::SENHA,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
            } catch (PDOException $e) {
                http_response_code(500);
                exit('Erro! Não foi possível conectar ao banco. Importe o arquivo database.sql e confira o admin/config/conexao.php. Detalhe: '
                    . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
            }
        }

        return self::$instancia;
    }
}


/* =========================================================
   FUNÇÕES AUXILIARES
========================================================= */

// Escapa texto para exibir no HTML (evita XSS)
function e($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

// Formata um valor em reais
function moeda($valor): string
{
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}

// Formata data/hora vinda do MySQL
function data_br($data): string
{
    return $data ? date('d/m/Y H:i', strtotime($data)) : '-';
}

function redirecionar(string $url): void
{
    header('Location: ' . $url);
    exit;
}


/* =========================================================
   AUTENTICAÇÃO (as páginas em /admin são restritas a admins)
========================================================= */

function usuario_logado(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

// Chame no topo de toda view/controller do /admin.
// Funciona porque views e controllers ficam no mesmo nível de pasta.
function exigir_login(): void
{
    $u = usuario_logado();

    if (!$u) {
        redirecionar('../../login.php');
    }

    // Cliente logado tentando entrar no painel: manda para a loja
    if (($u['tipo'] ?? '') !== 'admin') {
        redirecionar('../../loja/views/loja.php');
    }
}

// Para páginas da loja que exigem conta (checkout, meus pedidos...).
// $voltar = caminho (a partir da raiz) para onde voltar depois do login.
function exigir_cliente(string $voltar = ''): void
{
    if (!usuario_logado()) {
        if ($voltar !== '') {
            $_SESSION['voltar'] = $voltar;
        }
        flash('erro', 'Entre na sua conta para continuar.');
        redirecionar('../../login.php');
    }
}


/* =========================================================
   MENSAGENS FLASH (aparecem uma única vez após redirecionar)
========================================================= */

function flash(string $tipo, string $mensagem): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

function flash_pegar(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// Imprime a mensagem flash (se houver) já formatada
function flash_exibir(): void
{
    if ($f = flash_pegar()) {
        echo '<div class="alert alert-' . e($f['tipo']) . '">' . e($f['mensagem']) . '</div>';
    }
}


/* =========================================================
   FORMULÁRIOS: o controller guarda erros + dados digitados na
   sessão e volta para o formulário; a view lê e mostra.
========================================================= */

function voltar_com_erros(string $url, array $erros, array $dadosDigitados = []): void
{
    $_SESSION['erros'] = $erros;
    $_SESSION['old']   = $dadosDigitados;
    redirecionar($url);
}

function erros_pegar(): array
{
    $erros = $_SESSION['erros'] ?? [];
    unset($_SESSION['erros']);
    return $erros;
}

function old_pegar(): array
{
    $old = $_SESSION['old'] ?? [];
    unset($_SESSION['old']);
    return $old;
}

// Imprime a caixa de erros (se houver)
function erros_exibir(array $erros): void
{
    if (!$erros) {
        return;
    }

    echo '<div class="alert alert-erro">';
    foreach ($erros as $erro) {
        echo '<div>' . e($erro) . '</div>';
    }
    echo '</div>';
}


/* =========================================================
   PROTEÇÃO CSRF (todo formulário POST leva um token)
========================================================= */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valido(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}
