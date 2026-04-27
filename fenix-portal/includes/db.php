<?php
/**
 * Fênix Portal - Database Connection and Setup (SQLite3)
 * db.php
 */

// Define o caminho do banco de dados SQLite
define('DB_PATH', __DIR__ . '/data/fenix.db');

// Cria diretório de dados se não existir
if (!file_exists(dirname(DB_PATH))) {
    mkdir(dirname(DB_PATH), 0755, true);
}

try {
    // Conexão PDO com SQLite
    $pdo = new PDO("sqlite:" . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Habilitar foreign keys
    $pdo->exec("PRAGMA foreign_keys = ON");
    
    // Função para criar tabelas se não existirem
    function setupDatabase($pdo) {
        // Tabela: categorias
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS categorias (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                slug TEXT UNIQUE NOT NULL
            )
        ");
        
        // Tabela: noticias
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS noticias (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                titulo TEXT NOT NULL,
                slug TEXT UNIQUE NOT NULL,
                resumo TEXT,
                conteudo TEXT NOT NULL,
                imagem_capa TEXT,
                id_categoria INTEGER,
                data_publicacao DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (id_categoria) REFERENCES categorias(id)
            )
        ");
        
        // Tabela: enquetes
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS enquetes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                pergunta TEXT NOT NULL,
                status INTEGER DEFAULT 1,
                data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Tabela: enquetes_opcoes
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS enquetes_opcoes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_enquete INTEGER NOT NULL,
                texto_opcao TEXT NOT NULL,
                votos INTEGER DEFAULT 0,
                FOREIGN KEY (id_enquete) REFERENCES enquetes(id)
            )
        ");
        
        // Tabela: configuracoes
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS configuracoes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                logotipo_path TEXT,
                titulo_site TEXT DEFAULT 'Fênix Portal',
                texto_rodape TEXT DEFAULT '© 2024 Fênix Portal. Todos os direitos reservados.'
            )
        ");
        
        // Tabela: publicidade
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS publicidade (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                posicao TEXT NOT NULL,
                codigo_html_ou_imagem TEXT,
                status INTEGER DEFAULT 1
            )
        ");
        
        // Tabela: ultima_atualizacao (para controle do auto-update)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS ultima_atualizacao (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Inserir categorias padrão se não existirem
        $categoriasPadrao = [
            ['Gerais', 'gerais'],
            ['Nacionais e Internacionais', 'nacionais-e-internacionais'],
            ['Esportes', 'esportes'],
            ['Política', 'politica'],
            ['Gospel', 'gospel'],
            ['Guerra / Atualidades', 'guerra-atualidades']
        ];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM categorias");
        $stmt->execute();
        if ($stmt->fetch()['count'] == 0) {
            $insertStmt = $pdo->prepare("INSERT INTO categorias (nome, slug) VALUES (:nome, :slug)");
            foreach ($categoriasPadrao as $cat) {
                $insertStmt->execute(['nome' => $cat[0], 'slug' => $cat[1]]);
            }
        }
        
        // Inserir configuração padrão se não existir
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM configuracoes");
        $stmt->execute();
        if ($stmt->fetch()['count'] == 0) {
            $pdo->exec("INSERT INTO configuracoes (titulo_site, texto_rodape) VALUES ('Fênix Portal', '© 2024 Fênix Portal. Todos os direitos reservados.')");
        }
        
        // Inserir registro de última atualização se não existir
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM ultima_atualizacao");
        $stmt->execute();
        if ($stmt->fetch()['count'] == 0) {
            $pdo->exec("INSERT INTO ultima_atualizacao (timestamp) VALUES (datetime('now', '-1 hour'))");
        }
        
        // Inserir espaços publicitários padrão
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM publicidade");
        $stmt->execute();
        if ($stmt->fetch()['count'] == 0) {
            $pdo->exec("INSERT INTO publicidade (posicao, codigo_html_ou_imagem, status) VALUES 
                ('lateral_esq', '<div style=\"background:#eee;padding:20px;text-align:center;\">Espaço Publicitário Esquerdo</div>', 1)");
            $pdo->exec("INSERT INTO publicidade (posicao, codigo_html_ou_imagem, status) VALUES 
                ('lateral_dir', '<div style=\"background:#eee;padding:20px;text-align:center;\">Espaço Publicitário Direito</div>', 1)");
        }
    }
    
    setupDatabase($pdo);
    
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
