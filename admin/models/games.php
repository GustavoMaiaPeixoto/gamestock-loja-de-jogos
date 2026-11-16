<?php
/**
 * models/games.php
 * Classe responsável pela manipulação de jogos no banco (CRUD + upload de imagem).
 */

require_once __DIR__ . '/../config/conexao.php';

class Game
{
    public $id;
    public $titulo;
    public $descricao;
    public $preco;
    public $estoque;
    public $categoria_id;
    public $imagem;

    private $pdo;

    // Pasta física onde ficam as imagens dos jogos
    const PASTA_IMAGENS = __DIR__ . '/../public/imagens/';
    const TAMANHO_MAX   = 2097152; // 2 MB

    public function __construct(array $atrib = [])
    {
        $this->pdo = Conexao::getInstance();

        $this->id        = $atrib['id'] ?? null;
        $this->titulo    = trim($atrib['titulo'] ?? '');
        $this->descricao = trim($atrib['descricao'] ?? '');
        $this->preco     = str_replace(',', '.', trim((string)($atrib['preco'] ?? '')));
        $this->estoque   = $atrib['estoque'] ?? '';
        $this->categoria_id = ($atrib['categoria_id'] ?? '') === '' ? null : (int)$atrib['categoria_id'];
        $this->imagem    = $atrib['imagem'] ?? null;
    }

    /* =====================================================
       VALIDAÇÃO
    ===================================================== */

    public function validar(): array
    {
        $erros = [];

        if ($this->titulo === '') {
            $erros[] = 'Informe o título do jogo.';
        } elseif (mb_strlen($this->titulo) > 100) {
            $erros[] = 'O título pode ter no máximo 100 caracteres.';
        }

        if ($this->preco === '' || !is_numeric($this->preco) || (float)$this->preco < 0) {
            $erros[] = 'Informe um preço válido (maior ou igual a zero).';
        } elseif ((float)$this->preco > 99999999.99) {
            $erros[] = 'O preço informado é muito alto.';
        }

        if ($this->estoque === '' || filter_var($this->estoque, FILTER_VALIDATE_INT) === false || (int)$this->estoque < 0) {
            $erros[] = 'Informe um estoque válido (número inteiro maior ou igual a zero).';
        }

        if ($this->categoria_id !== null) {
            $sth = $this->pdo->prepare("SELECT COUNT(*) FROM categorias WHERE id = ?");
            $sth->execute([$this->categoria_id]);

            if ((int)$sth->fetchColumn() === 0) {
                $erros[] = 'A categoria escolhida não existe.';
            }
        }

        return $erros;
    }

    /* =====================================================
       CREATE
    ===================================================== */

    public function cadastrarGame(): bool
    {
        $sth = $this->pdo->prepare(
            "INSERT INTO games (titulo, descricao, preco, estoque, categoria_id, imagem)
             VALUES (:titulo, :descricao, :preco, :estoque, :categoria_id, :imagem)"
        );

        $sth->bindValue(':titulo',    $this->titulo,    PDO::PARAM_STR);
        $sth->bindValue(':descricao', $this->descricao, PDO::PARAM_STR);
        $sth->bindValue(':preco',     $this->preco,     PDO::PARAM_STR);
        $sth->bindValue(':estoque',   (int)$this->estoque, PDO::PARAM_INT);
        $sth->bindValue(':categoria_id', $this->categoria_id, $this->categoria_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $sth->bindValue(':imagem',    $this->imagem,    $this->imagem === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

        return $sth->execute();
    }

    /* =====================================================
       READ
    ===================================================== */

    public function listarGames(): array
    {
        return $this->pdo->query(
            "SELECT g.*, c.nome AS categoria FROM games g
             LEFT JOIN categorias c ON c.id = g.categoria_id
             ORDER BY g.titulo ASC"
        )->fetchAll();
    }

    // Busca por título e/ou categoria
    public function buscar(string $nome = '', int $categoriaId = 0, string $ordem = 'titulo'): array
    {
        $sql    = "SELECT g.*, c.nome AS categoria FROM games g
                   LEFT JOIN categorias c ON c.id = g.categoria_id
                   WHERE 1=1";
        $params = [];

        if ($nome !== '') {
            $sql .= " AND g.titulo LIKE :nome";
            $params[':nome'] = '%' . $nome . '%';
        }

        if ($categoriaId > 0) {
            $sql .= " AND g.categoria_id = :categoria_id";
            $params[':categoria_id'] = $categoriaId;
        }

        $ordens = [
            'titulo'     => 'g.titulo ASC',
            'preco_asc'  => 'g.preco ASC, g.titulo ASC',
            'preco_desc' => 'g.preco DESC, g.titulo ASC',
            'novos'      => 'g.created_at DESC, g.id DESC',
        ];

        $sth = $this->pdo->prepare($sql . " ORDER BY " . ($ordens[$ordem] ?? $ordens['titulo']));
        $sth->execute($params);

        return $sth->fetchAll();
    }

    public function buscarPorNome(string $nome): array
    {
        return $this->buscar($nome);
    }

    public function buscarGamePorId($id)
    {
        $sth = $this->pdo->prepare(
            "SELECT g.*, c.nome AS categoria FROM games g
             LEFT JOIN categorias c ON c.id = g.categoria_id
             WHERE g.id = :id"
        );
        $sth->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $sth->execute();

        return $sth->fetch();
    }

    // Números do painel
    public function estatisticas(): array
    {
        return $this->pdo->query(
            "SELECT COUNT(*)                          AS total,
                    COALESCE(SUM(estoque), 0)         AS unidades,
                    COALESCE(SUM(preco * estoque), 0) AS valor,
                    COALESCE(SUM(estoque = 0), 0)     AS esgotados
             FROM games"
        )->fetch();
    }

    /* =====================================================
       UPDATE
    ===================================================== */

    public function editarGame(): bool
    {
        $sth = $this->pdo->prepare(
            "UPDATE games SET
                titulo = :titulo,
                descricao = :descricao,
                preco = :preco,
                estoque = :estoque,
                categoria_id = :categoria_id,
                imagem = :imagem
             WHERE id = :id"
        );

        $sth->bindValue(':id',        (int)$this->id,   PDO::PARAM_INT);
        $sth->bindValue(':titulo',    $this->titulo,    PDO::PARAM_STR);
        $sth->bindValue(':descricao', $this->descricao, PDO::PARAM_STR);
        $sth->bindValue(':preco',     $this->preco,     PDO::PARAM_STR);
        $sth->bindValue(':estoque',   (int)$this->estoque, PDO::PARAM_INT);
        $sth->bindValue(':categoria_id', $this->categoria_id, $this->categoria_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $sth->bindValue(':imagem',    $this->imagem,    $this->imagem === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

        return $sth->execute();
    }

    /* =====================================================
       DELETE
    ===================================================== */

    public function deletarGame(): bool
    {
        $sth = $this->pdo->prepare("DELETE FROM games WHERE id = :id");
        $sth->bindValue(':id', (int)$this->id, PDO::PARAM_INT);

        return $sth->execute();
    }

    /* =====================================================
       IMAGENS
    ===================================================== */

    /**
     * Valida e move a imagem enviada para public/imagens.
     * Retorna ['ok' => bool, 'arquivo' => nome|null, 'erro' => mensagem|null].
     * Se nenhum arquivo foi enviado, devolve ok = true e arquivo = null.
     */
    public static function enviarImagem(?array $file): array
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => true, 'arquivo' => null, 'erro' => null];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'arquivo' => null, 'erro' => 'Falha ao enviar a imagem. Tente novamente.'];
        }

        if ($file['size'] > self::TAMANHO_MAX) {
            return ['ok' => false, 'arquivo' => null, 'erro' => 'A imagem deve ter no máximo 2 MB.'];
        }

        $permitidos = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);

        if (!isset($permitidos[$mime])) {
            return ['ok' => false, 'arquivo' => null, 'erro' => 'Formato inválido. Use JPG, PNG, WEBP ou GIF.'];
        }

        if (!is_dir(self::PASTA_IMAGENS)) {
            mkdir(self::PASTA_IMAGENS, 0775, true);
        }

        $nome = bin2hex(random_bytes(8)) . '.' . $permitidos[$mime];

        if (!move_uploaded_file($file['tmp_name'], self::PASTA_IMAGENS . $nome)) {
            return ['ok' => false, 'arquivo' => null, 'erro' => 'Não foi possível salvar a imagem no servidor.'];
        }

        return ['ok' => true, 'arquivo' => $nome, 'erro' => null];
    }

    // Apaga a imagem do disco (basename evita sair da pasta)
    public static function apagarImagem(?string $arquivo): void
    {
        if (!$arquivo) {
            return;
        }

        $caminho = self::PASTA_IMAGENS . basename($arquivo);

        if (is_file($caminho)) {
            unlink($caminho);
        }
    }

    public static function imagemExiste(?string $arquivo): bool
    {
        return $arquivo && is_file(self::PASTA_IMAGENS . basename($arquivo));
    }
}
