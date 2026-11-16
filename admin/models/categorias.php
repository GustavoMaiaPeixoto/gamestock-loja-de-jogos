<?php
/**
 * models/categorias.php
 * Classe responsável pela manipulação de categorias no banco (CRUD).
 */

require_once __DIR__ . '/../config/conexao.php';

class Categoria
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::getInstance();
    }

    /* =====================================================
       VALIDAÇÃO
    ===================================================== */

    public function validar(string $nome, string $descricao, $ignorarId = null): array
    {
        $erros = [];

        if ($nome === '') {
            $erros[] = 'Informe o nome da categoria.';
        } elseif (mb_strlen($nome) > 50) {
            $erros[] = 'O nome pode ter no máximo 50 caracteres.';
        } elseif ($this->nomeExiste($nome, $ignorarId)) {
            $erros[] = 'Já existe uma categoria com esse nome.';
        }

        if (mb_strlen($descricao) > 255) {
            $erros[] = 'A descrição pode ter no máximo 255 caracteres.';
        }

        return $erros;
    }

    /* =====================================================
       CREATE
    ===================================================== */

    public function cadastrar(string $nome, string $descricao = ''): bool
    {
        $sth = $this->pdo->prepare("INSERT INTO categorias (nome, descricao) VALUES (?, ?)");

        return $sth->execute([$nome, $descricao !== '' ? $descricao : null]);
    }

    /* =====================================================
       READ
    ===================================================== */

    // Lista com a quantidade de jogos de cada categoria
    public function listar(): array
    {
        return $this->pdo->query(
            "SELECT c.*, COUNT(g.id) AS total_games
             FROM categorias c
             LEFT JOIN games g ON g.categoria_id = c.id
             GROUP BY c.id
             ORDER BY c.nome ASC"
        )->fetchAll();
    }

    public function buscar(string $termo): array
    {
        $sth = $this->pdo->prepare(
            "SELECT c.*, COUNT(g.id) AS total_games
             FROM categorias c
             LEFT JOIN games g ON g.categoria_id = c.id
             WHERE c.nome LIKE :t1 OR c.descricao LIKE :t2
             GROUP BY c.id
             ORDER BY c.nome ASC"
        );
        $sth->execute([':t1' => '%' . $termo . '%', ':t2' => '%' . $termo . '%']);

        return $sth->fetchAll();
    }

    public function buscarPorId($id)
    {
        $sth = $this->pdo->prepare(
            "SELECT c.*, (SELECT COUNT(*) FROM games g WHERE g.categoria_id = c.id) AS total_games
             FROM categorias c WHERE c.id = ?"
        );
        $sth->execute([(int)$id]);

        return $sth->fetch();
    }

    public function nomeExiste(string $nome, $ignorarId = null): bool
    {
        $sth = $this->pdo->prepare("SELECT COUNT(*) FROM categorias WHERE nome = ? AND id <> ?");
        $sth->execute([$nome, (int)$ignorarId]);

        return (int)$sth->fetchColumn() > 0;
    }

    /* =====================================================
       UPDATE
    ===================================================== */

    public function editar($id, string $nome, string $descricao = ''): bool
    {
        $sth = $this->pdo->prepare("UPDATE categorias SET nome = ?, descricao = ? WHERE id = ?");

        return $sth->execute([$nome, $descricao !== '' ? $descricao : null, (int)$id]);
    }

    /* =====================================================
       DELETE  (bloqueia se ainda houver jogos na categoria)
    ===================================================== */

    public function deletar($id): bool
    {
        $c = $this->buscarPorId($id);

        if (!$c) {
            throw new RuntimeException('Categoria não encontrada.');
        }

        if ((int)$c['total_games'] > 0) {
            throw new RuntimeException(
                'A categoria "' . $c['nome'] . '" possui ' . (int)$c['total_games'] .
                ' jogo(s). Mude a categoria deles antes de excluir.'
            );
        }

        return $this->pdo->prepare("DELETE FROM categorias WHERE id = ?")->execute([(int)$id]);
    }
}
