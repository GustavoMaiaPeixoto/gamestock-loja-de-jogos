<?php
/**
 * models/usuarios.php
 * Classe responsável pela manipulação de usuários no banco (CRUD + login).
 */

require_once __DIR__ . '/../config/conexao.php';

class Usuario
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::getInstance();
    }

    /* =====================================================
       VALIDAÇÃO
    ===================================================== */

    public function validar(string $nome, string $email, string $tipo, ?string $senha, bool $senhaObrigatoria, $ignorarId = null): array
    {
        $erros = [];

        if ($nome === '' || mb_strlen($nome) > 100) {
            $erros[] = 'Informe o nome (até 100 caracteres).';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            $erros[] = 'Informe um e-mail válido.';
        } elseif ($this->emailExiste($email, $ignorarId)) {
            $erros[] = 'Já existe um usuário cadastrado com esse e-mail.';
        }

        if (!in_array($tipo, ['admin', 'user'], true)) {
            $erros[] = 'Tipo de usuário inválido.';
        }

        if ($senhaObrigatoria || ($senha !== null && $senha !== '')) {
            if (mb_strlen((string)$senha) < 6) {
                $erros[] = 'A senha deve ter pelo menos 6 caracteres.';
            }
        }

        return $erros;
    }

    /* =====================================================
       CREATE
    ===================================================== */

    public function cadastrar(string $nome, string $email, string $senha, string $tipo = 'user'): bool
    {
        $sth = $this->pdo->prepare(
            "INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)"
        );

        return $sth->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT), $tipo]);
    }

    /* =====================================================
       READ
    ===================================================== */

    public function listar(): array
    {
        return $this->pdo->query(
            "SELECT id, nome, email, tipo, created_at, updated_at FROM usuarios ORDER BY nome ASC"
        )->fetchAll();
    }

    public function buscar(string $termo): array
    {
        $sth = $this->pdo->prepare(
            "SELECT id, nome, email, tipo, created_at, updated_at
             FROM usuarios
             WHERE nome LIKE :t OR email LIKE :t
             ORDER BY nome ASC"
        );
        $sth->execute([':t' => '%' . $termo . '%']);

        return $sth->fetchAll();
    }

    public function buscarPorId($id)
    {
        $sth = $this->pdo->prepare(
            "SELECT id, nome, email, tipo, created_at, updated_at FROM usuarios WHERE id = ?"
        );
        $sth->execute([(int)$id]);

        return $sth->fetch();
    }

    public function buscarPorEmail(string $email)
    {
        $sth = $this->pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $sth->execute([$email]);

        return $sth->fetch();
    }

    public function emailExiste(string $email, $ignorarId = null): bool
    {
        $sth = $this->pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ? AND id <> ?");
        $sth->execute([$email, (int)$ignorarId]);

        return (int)$sth->fetchColumn() > 0;
    }

    public function contarAdmins(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'admin'")->fetchColumn();
    }

    /* =====================================================
       UPDATE  (senha em branco = mantém a senha atual)
    ===================================================== */

    public function editar($id, string $nome, string $email, string $tipo, ?string $senha = null): bool
    {
        if ($senha !== null && $senha !== '') {
            $sth = $this->pdo->prepare(
                "UPDATE usuarios SET nome = ?, email = ?, tipo = ?, senha = ? WHERE id = ?"
            );
            return $sth->execute([$nome, $email, $tipo, password_hash($senha, PASSWORD_DEFAULT), (int)$id]);
        }

        $sth = $this->pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, tipo = ? WHERE id = ?");

        return $sth->execute([$nome, $email, $tipo, (int)$id]);
    }

    /* =====================================================
       DELETE
    ===================================================== */

    public function deletar($id): bool
    {
        $sth = $this->pdo->prepare("DELETE FROM usuarios WHERE id = ?");

        return $sth->execute([(int)$id]);
    }

    /* =====================================================
       LOGIN
       Senhas são guardadas com password_hash() e conferidas com password_verify().
    ===================================================== */

    public function autenticar(string $email, string $senha)
    {
        $u = $this->buscarPorEmail($email);

        if (!$u || !password_verify($senha, $u['senha'])) {
            return false;
        }

        // Atualiza o hash se o algoritmo padrão do PHP mudou
        if (password_needs_rehash($u['senha'], PASSWORD_DEFAULT)) {
            $this->pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?")
                ->execute([password_hash($senha, PASSWORD_DEFAULT), $u['id']]);
        }

        unset($u['senha']);

        return $u;
    }
}
