<?php
/**
 * models/clientes.php
 * Classe responsável pela manipulação de clientes no banco (CRUD).
 * Um cliente pode estar ligado a uma conta de acesso (usuarios) ou não.
 */

require_once __DIR__ . '/../config/conexao.php';

class Cliente
{
    const UFS = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG',
                 'PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];

    const CAMPOS = ['nome', 'email', 'cpf', 'telefone', 'cep', 'endereco', 'cidade', 'estado'];

    private $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::getInstance();
    }

    /* =====================================================
       FUNÇÕES AUXILIARES (estáticas)
    ===================================================== */

    // Limpa e padroniza os dados vindos de um formulário
    public static function normalizar(array $d): array
    {
        $r = [];

        foreach (self::CAMPOS as $c) {
            $r[$c] = trim((string)($d[$c] ?? ''));
        }

        $r['cpf']    = preg_replace('/\D/', '', $r['cpf']);
        $r['estado'] = strtoupper($r['estado']);

        $cep = preg_replace('/\D/', '', $r['cep']);
        if (strlen($cep) === 8) {
            $r['cep'] = substr($cep, 0, 5) . '-' . substr($cep, 5);
        }

        return $r;
    }

    public static function cpfValido(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);

        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) {
                $soma += (int)$cpf[$i] * (($t + 1) - $i);
            }
            if ((int)$cpf[$t] !== ((10 * $soma) % 11) % 10) {
                return false;
            }
        }

        return true;
    }

    public static function formatarCpf(?string $cpf): string
    {
        $cpf = (string)$cpf;

        return strlen($cpf) === 11
            ? substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9)
            : $cpf;
    }

    // Monta o endereço em uma linha (usado como sugestão no checkout)
    public static function enderecoCompleto(array $c): string
    {
        $partes = [];

        if (!empty($c['endereco'])) {
            $partes[] = $c['endereco'];
        }

        $cidade = trim(($c['cidade'] ?? '') . (!empty($c['estado']) ? '/' . $c['estado'] : ''), '/');
        if ($cidade !== '') {
            $partes[] = $cidade;
        }

        if (!empty($c['cep'])) {
            $partes[] = 'CEP ' . $c['cep'];
        }

        return implode(', ', $partes);
    }

    private function nulo($v)
    {
        return $v === '' ? null : $v;
    }

    /* =====================================================
       VALIDAÇÃO  ($d já normalizado por Cliente::normalizar)
    ===================================================== */

    public function validar(array $d, $ignorarId = null, bool $validarEmail = true): array
    {
        $erros = [];

        if ($d['nome'] === '' || mb_strlen($d['nome']) > 100) {
            $erros[] = 'Informe o nome (até 100 caracteres).';
        }

        if ($validarEmail) {
            if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($d['email']) > 150) {
                $erros[] = 'Informe um e-mail válido.';
            } elseif ($this->emailExiste($d['email'], $ignorarId)) {
                $erros[] = 'Já existe um cliente com esse e-mail.';
            }
        }

        if ($d['cpf'] !== '') {
            if (!self::cpfValido($d['cpf'])) {
                $erros[] = 'CPF inválido.';
            } elseif ($this->cpfExiste($d['cpf'], $ignorarId)) {
                $erros[] = 'Já existe um cliente com esse CPF.';
            }
        }

        if ($d['telefone'] !== '') {
            $tel = preg_replace('/\D/', '', $d['telefone']);
            if (strlen($tel) < 10 || strlen($tel) > 11 || mb_strlen($d['telefone']) > 20) {
                $erros[] = 'Telefone inválido. Use DDD + número.';
            }
        }

        if ($d['cep'] !== '' && !preg_match('/^\d{5}-\d{3}$/', $d['cep'])) {
            $erros[] = 'CEP inválido. Use o formato 00000-000.';
        }

        if (mb_strlen($d['endereco']) > 200) {
            $erros[] = 'O endereço pode ter no máximo 200 caracteres.';
        }

        if (mb_strlen($d['cidade']) > 80) {
            $erros[] = 'A cidade pode ter no máximo 80 caracteres.';
        }

        if ($d['estado'] !== '' && !in_array($d['estado'], self::UFS, true)) {
            $erros[] = 'Estado (UF) inválido.';
        }

        return $erros;
    }

    /* =====================================================
       CREATE  (retorna o id criado)
    ===================================================== */

    public function cadastrar(array $d, ?int $usuarioId = null): int
    {
        $d = self::normalizar($d);

        $sth = $this->pdo->prepare(
            "INSERT INTO clientes (usuario_id, nome, email, cpf, telefone, cep, endereco, cidade, estado)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $sth->execute([
            $usuarioId, $d['nome'], $d['email'], $this->nulo($d['cpf']), $this->nulo($d['telefone']),
            $this->nulo($d['cep']), $this->nulo($d['endereco']), $this->nulo($d['cidade']), $this->nulo($d['estado']),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /* =====================================================
       READ
    ===================================================== */

    public function listar(): array
    {
        return $this->pdo->query(
            "SELECT c.*, (SELECT COUNT(*) FROM pedidos p WHERE p.cliente_id = c.id) AS total_pedidos
             FROM clientes c
             ORDER BY c.nome ASC"
        )->fetchAll();
    }

    // Busca por nome, e-mail ou CPF
    public function buscar(string $termo): array
    {
        $sql    = "SELECT c.*, (SELECT COUNT(*) FROM pedidos p WHERE p.cliente_id = c.id) AS total_pedidos
                   FROM clientes c
                   WHERE c.nome LIKE :t1 OR c.email LIKE :t2";
        $params = [':t1' => '%' . $termo . '%', ':t2' => '%' . $termo . '%'];

        $digitos = preg_replace('/\D/', '', $termo);
        if ($digitos !== '') {
            $sql .= " OR c.cpf LIKE :t3";
            $params[':t3'] = '%' . $digitos . '%';
        }

        $sth = $this->pdo->prepare($sql . " ORDER BY c.nome ASC");
        $sth->execute($params);

        return $sth->fetchAll();
    }

    public function buscarPorId($id)
    {
        $sth = $this->pdo->prepare("SELECT * FROM clientes WHERE id = ?");
        $sth->execute([(int)$id]);

        return $sth->fetch();
    }

    public function buscarPorEmail(string $email)
    {
        $sth = $this->pdo->prepare("SELECT * FROM clientes WHERE email = ?");
        $sth->execute([$email]);

        return $sth->fetch();
    }

    public function buscarPorUsuario($usuarioId)
    {
        $sth = $this->pdo->prepare("SELECT * FROM clientes WHERE usuario_id = ?");
        $sth->execute([(int)$usuarioId]);

        return $sth->fetch();
    }

    public function emailExiste(string $email, $ignorarId = null): bool
    {
        $sth = $this->pdo->prepare("SELECT COUNT(*) FROM clientes WHERE email = ? AND id <> ?");
        $sth->execute([$email, (int)$ignorarId]);

        return (int)$sth->fetchColumn() > 0;
    }

    public function cpfExiste(string $cpf, $ignorarId = null): bool
    {
        $sth = $this->pdo->prepare("SELECT COUNT(*) FROM clientes WHERE cpf = ? AND id <> ?");
        $sth->execute([$cpf, (int)$ignorarId]);

        return (int)$sth->fetchColumn() > 0;
    }

    /* =====================================================
       UPDATE
       Se o cliente tem conta (usuario_id), o e-mail NÃO muda aqui
       e o nome é sincronizado com a conta.
    ===================================================== */

    public function editar($id, array $d): bool
    {
        $d = self::normalizar($d);

        $sth = $this->pdo->prepare(
            "UPDATE clientes SET
                nome = ?,
                email = IF(usuario_id IS NULL, ?, email),
                cpf = ?, telefone = ?, cep = ?, endereco = ?, cidade = ?, estado = ?
             WHERE id = ?"
        );

        $ok = $sth->execute([
            $d['nome'], $d['email'], $this->nulo($d['cpf']), $this->nulo($d['telefone']),
            $this->nulo($d['cep']), $this->nulo($d['endereco']), $this->nulo($d['cidade']),
            $this->nulo($d['estado']), (int)$id,
        ]);

        $this->pdo->prepare(
            "UPDATE usuarios u JOIN clientes c ON c.usuario_id = u.id SET u.nome = ? WHERE c.id = ?"
        )->execute([$d['nome'], (int)$id]);

        return $ok;
    }

    /* =====================================================
       DELETE  (falha se o cliente tiver pedidos — chave estrangeira)
    ===================================================== */

    public function deletar($id): bool
    {
        return $this->pdo->prepare("DELETE FROM clientes WHERE id = ?")->execute([(int)$id]);
    }

    /* =====================================================
       LIGAÇÃO COM A CONTA DE ACESSO
    ===================================================== */

    // Chamado no cadastro público: liga a conta a um cliente existente
    // (criado antes pelo admin com o mesmo e-mail) ou cria um novo.
    public function vincularOuCriar(int $usuarioId, string $nome, string $email, string $telefone = ''): int
    {
        $existente = $this->buscarPorEmail($email);

        if ($existente && $existente['usuario_id'] === null) {
            $this->pdo->prepare("UPDATE clientes SET usuario_id = ? WHERE id = ?")
                ->execute([$usuarioId, $existente['id']]);

            return (int)$existente['id'];
        }

        return $this->cadastrar(['nome' => $nome, 'email' => $email, 'telefone' => $telefone], $usuarioId);
    }

    // Garante que o usuário logado tem um cliente (ex.: admin comprando na loja)
    public function garantirPorUsuario(array $usuario): array
    {
        $c = $this->buscarPorUsuario($usuario['id']);

        if (!$c) {
            $id = $this->vincularOuCriar((int)$usuario['id'], $usuario['nome'], $usuario['email']);
            $c  = $this->buscarPorId($id);
        }

        return $c;
    }
}
