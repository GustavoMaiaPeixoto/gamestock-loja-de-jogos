
CREATE DATABASE loja_games
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE loja_games;

-- ---------- USUARIOS (contas de acesso: admin ou user) ----------
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ---------- CLIENTES (quem compra; pode ou não ter conta de acesso) ----------
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL UNIQUE,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    cpf VARCHAR(11) NULL UNIQUE,
    telefone VARCHAR(20) NULL,
    cep VARCHAR(9) NULL,
    endereco VARCHAR(200) NULL,
    cidade VARCHAR(80) NULL,
    estado CHAR(2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- ---------- CATEGORIAS ----------
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE,
    descricao VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ---------- GAMES (produtos) ----------
CREATE TABLE games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL,
    estoque INT DEFAULT 0,
    categoria_id INT NULL,
    imagem VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
);

-- ---------- PEDIDOS ----------
CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    pagamento ENUM('pix', 'cartao', 'boleto') NOT NULL,
    endereco TEXT NOT NULL,
    status ENUM('pendente', 'pago', 'enviado', 'entregue', 'cancelado') NOT NULL DEFAULT 'pendente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

CREATE TABLE pedido_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    game_id INT NULL,
    titulo VARCHAR(100) NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    quantidade INT NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE SET NULL
);


INSERT INTO usuarios (nome, email, senha, tipo) VALUES
('evandro', 'evandro@gmail.com', '$2y$12$.uUgPGSfNTHi89WZYlwD/.CK0ym2JiSybW1QRYF3oxJ6A4048gOme', 'admin'),
('pedro',   'pedro@gmail.com',   '$2y$12$qXaDyGaMS4PnLooozZx3beA/YE7kFUe4r6oJuioqOiIZgp/eysP0W',  'user');

INSERT INTO clientes (usuario_id, nome, email, cpf, telefone, cep, endereco, cidade, estado) VALUES
((SELECT id FROM usuarios WHERE email = 'pedro@gmail.com'), 'Pedro Almeida', 'pedro@gmail.com', NULL, '(38) 99999-1111', '39400-000', 'Rua das Flores, 123 - Centro', 'Montes Claros', 'MG'),
(NULL, 'Maria Souza', 'maria@exemplo.com', NULL, '(38) 98888-2222', '39401-100', 'Av. Brasil, 500 - Todos os Santos', 'Montes Claros', 'MG');

INSERT INTO categorias (nome, descricao) VALUES
('Aventura', 'Exploração, puzzles e narrativa.'),
('Ação',     'Combate e adrenalina.'),
('Sandbox',  'Mundo aberto e criatividade.'),
('Esporte',  'Simuladores esportivos.'),
('Indie',    'Jogos independentes.'),
('RPG',      'Interpretação de personagens e evolução.');

INSERT INTO games (titulo, descricao, preco, estoque, categoria_id, imagem) VALUES
('The Legend of Zelda: Breath of the Wild', 'Aventura em mundo aberto por Hyrule.', 299.99, 10, 1, 'zelda.jfif'),
('God of War', 'Jogo de ação com Kratos em uma jornada épica.', 249.99, 15, 2, 'god.jfif'),
('Minecraft', 'Jogo de construção e sobrevivência em mundo aberto.', 129.99, 20, 3, 'mine.jfif'),
('FIFA 24', 'Simulador de futebol com times e ligas oficiais.', 199.90, 3, 4, 'fifa.jfif'),
('Hollow Knight', 'Metroidvania desafiador em um reino de insetos.', 46.99, 0, 5, 'Hollow.jfif');
