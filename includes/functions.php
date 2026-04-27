<?php
/**
 * Fênix Portal - Funções Auxiliares, SEO e Automação RSS
 * functions.php
 */

require_once __DIR__ . '/db.php';

/**
 * Gera slug amigável para URLs (SEO)
 */
function gerarSlug($texto) {
    $texto = mb_strtolower($texto, 'UTF-8');
    $texto = preg_replace('/[àáâãäå]/', 'a', $texto);
    $texto = preg_replace('/[èéêë]/', 'e', $texto);
    $texto = preg_replace('/[ìíîï]/', 'i', $texto);
    $texto = preg_replace('/[òóôõö]/', 'o', $texto);
    $texto = preg_replace('/[ùúûü]/', 'u', $texto);
    $texto = preg_replace('/[ç]/', 'c', $texto);
    $texto = preg_replace('/[^a-z0-9-]/', '-', $texto);
    $texto = preg_replace('/-+/', '-', $texto);
    $texto = trim($texto, '-');
    return $texto;
}

/**
 * Busca configurações do site
 */
function getConfiguracoes() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM configuracoes LIMIT 1");
    return $stmt->fetch();
}

/**
 * Busca todas as categorias
 */
function getCategorias() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM categorias ORDER BY nome");
    return $stmt->fetchAll();
}

/**
 * Busca notícia por slug
 */
function getNoticiaPorSlug($slug) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT n.*, c.nome as categoria_nome, c.slug as categoria_slug 
        FROM noticias n 
        LEFT JOIN categorias c ON n.id_categoria = c.id 
        WHERE n.slug = :slug
    ");
    $stmt->execute(['slug' => $slug]);
    return $stmt->fetch();
}

/**
 * Busca notícias com paginação
 */
function getNoticias($limite = 10, $offset = 0, $categoria = null) {
    global $pdo;
    
    if ($categoria) {
        $stmt = $pdo->prepare("
            SELECT n.*, c.nome as categoria_nome, c.slug as categoria_slug 
            FROM noticias n 
            LEFT JOIN categorias c ON n.id_categoria = c.id 
            WHERE c.slug = :categoria
            ORDER BY n.data_publicacao DESC 
            LIMIT :limite OFFSET :offset
        ");
        $stmt->bindValue(':categoria', $categoria, PDO::PARAM_STR);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("
            SELECT n.*, c.nome as categoria_nome, c.slug as categoria_slug 
            FROM noticias n 
            LEFT JOIN categorias c ON n.id_categoria = c.id 
            ORDER BY n.data_publicacao DESC 
            LIMIT :limite OFFSET :offset
        ");
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    return $stmt->fetchAll();
}

/**
 * Busca notícia de destaque (mais recente)
 */
function getNoticiaDestaque() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT n.*, c.nome as categoria_nome, c.slug as categoria_slug 
        FROM noticias n 
        LEFT JOIN categorias c ON n.id_categoria = c.id 
        ORDER BY n.data_publicacao DESC 
        LIMIT 1
    ");
    return $stmt->fetch();
}

/**
 * Busca publicidades por posição
 */
function getPublicidade($posicao) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM publicidade WHERE posicao = :posicao AND status = 1 LIMIT 1");
    $stmt->execute(['posicao' => $posicao]);
    return $stmt->fetch();
}

/**
 * Busca enquete ativa
 */
function getEnqueteAtiva() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT e.*, eo.id as opcao_id, eo.texto_opcao, eo.votos 
        FROM enquetes e 
        LEFT JOIN enquetes_opcoes eo ON e.id = eo.id_enquete 
        WHERE e.status = 1 
        ORDER BY e.data_criacao DESC 
        LIMIT 1
    ");
    $resultados = $stmt->fetchAll();
    
    if (empty($resultados)) {
        return null;
    }
    
    $enquete = [
        'id' => $resultados[0]['id'],
        'pergunta' => $resultados[0]['pergunta'],
        'opcoes' => []
    ];
    
    foreach ($resultados as $row) {
        if ($row['opcao_id']) {
            $enquete['opcoes'][] = [
                'id' => $row['opcao_id'],
                'texto' => $row['texto_opcao'],
                'votos' => $row['votos']
            ];
        }
    }
    
    return $enquete;
}

/**
 * Registra voto na enquete
 */
function votarEnquete($idOpcao) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE enquetes_opcoes SET votos = votos + 1 WHERE id = :id");
    $stmt->execute(['id' => $idOpcao]);
    return $stmt->rowCount() > 0;
}

/**
 * Verifica se precisa atualizar notícias (auto-update 1 hora)
 */
function precisaAtualizarNoticias() {
    global $pdo;
    $stmt = $pdo->query("SELECT timestamp FROM ultima_atualizacao ORDER BY id DESC LIMIT 1");
    $resultado = $stmt->fetch();
    
    if (!$resultado) {
        return true;
    }
    
    $ultimaAtualizacao = strtotime($resultado['timestamp']);
    $agora = time();
    $diferenca = $agora - $ultimaAtualizacao;
    
    // 3600 segundos = 1 hora
    return $diferenca > 3600;
}

/**
 * Atualiza timestamp da última atualização
 */
function atualizarTimestamp() {
    global $pdo;
    $pdo->exec("INSERT INTO ultima_atualizacao (timestamp) VALUES (datetime('now'))");
}

/**
 * Função de automação - Busca notícias de fontes externas (RSS/API)
 * Esta é uma implementação de exemplo que pode ser expandida
 */
function buscarNoticiasExternas() {
    // Feeds RSS de exemplo (pode ser expandido conforme necessidade)
    $feeds = [
        'esportes' => 'https://ge.globo.com/Feed/noticias/rss.xml',
        'politica' => 'https://g1.globo.com/politica/Feed/politica/rss.xml',
        'gerais' => 'https://g1.globo.com/Feed/rss.xml'
    ];
    
    $noticiasAdicionadas = 0;
    
    foreach ($feeds as $categoria => $feedUrl) {
        try {
            $rss = @file_get_contents($feedUrl);
            if ($rss === false) {
                continue;
            }
            
            $xml = simplexml_load_string($rss);
            if (!$xml) {
                continue;
            }
            
            // Buscar ID da categoria no banco
            global $pdo;
            $stmt = $pdo->prepare("SELECT id FROM categorias WHERE slug = :slug");
            $stmt->execute(['slug' => $categoria]);
            $catData = $stmt->fetch();
            
            if (!$catData) {
                continue;
            }
            
            $idCategoria = $catData['id'];
            
            // Processar itens do feed (limitado a 5 por categoria para não sobrecarregar)
            $contador = 0;
            foreach ($xml->channel->item as $item) {
                if ($contador >= 5) {
                    break;
                }
                
                $titulo = (string)$item->title;
                $link = (string)$item->link;
                $descricao = (string)$item->description;
                $dataPub = (string)$item->pubDate;
                
                // Gerar slug único
                $slugBase = gerarSlug($titulo);
                $slug = $slugBase;
                $contadorSlug = 1;
                
                // Verificar se já existe notícia com este título
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM noticias WHERE titulo = :titulo");
                $stmt->execute(['titulo' => $titulo]);
                if ($stmt->fetch()['count'] > 0) {
                    continue;
                }
                
                // Gerar slug único se necessário
                while (true) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM noticias WHERE slug = :slug");
                    $stmt->execute(['slug' => $slug]);
                    if ($stmt->fetch()['count'] == 0) {
                        break;
                    }
                    $slug = $slugBase . '-' . $contadorSlug;
                    $contadorSlug++;
                }
                
                // Inserir notícia
                $stmt = $pdo->prepare("
                    INSERT INTO noticias (titulo, slug, resumo, conteudo, imagem_capa, id_categoria, data_publicacao) 
                    VALUES (:titulo, :slug, :resumo, :conteudo, :imagem, :id_categoria, :data_pub)
                ");
                
                $stmt->execute([
                    'titulo' => $titulo,
                    'slug' => $slug,
                    'resumo' => mb_substr(strip_tags($descricao), 0, 200) . '...',
                    'conteudo' => "<p>Fonte: <a href='$link' target='_blank' rel='noopener'>Leia a matéria completa</a></p>" . $descricao,
                    'imagem' => null,
                    'id_categoria' => $idCategoria,
                    'data_pub' => date('Y-m-d H:i:s', strtotime($dataPub))
                ]);
                
                $noticiasAdicionadas++;
                $contador++;
            }
            
        } catch (Exception $e) {
            // Continuar mesmo se houver erro em algum feed
            continue;
        }
    }
    
    return $noticiasAdicionadas;
}

/**
 * Gera meta tags SEO dinâmicas
 */
function gerarMetaTags($titulo = null, $descricao = null, $imagem = null) {
    $config = getConfiguracoes();
    $tituloSite = $config['titulo_site'] ?? 'Fênix Portal';
    
    if ($titulo) {
        $tituloCompleto = htmlspecialchars($titulo . ' | ' . $tituloSite, ENT_QUOTES, 'UTF-8');
    } else {
        $tituloCompleto = htmlspecialchars($tituloSite, ENT_QUOTES, 'UTF-8');
    }
    
    $metaDescription = $descricao ? htmlspecialchars(mb_substr(strip_tags($descricao), 0, 160), ENT_QUOTES, 'UTF-8') : 'Portal de notícias com informações atualizadas sobre esportes, política, gospel e atualidades.';
    
    $urlAtual = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
    
    $ogImagem = $imagem ? $imagem : 'https://via.placeholder.com/1200x630?text=Fênix+Portal';
    
    echo "<title>{$tituloCompleto}</title>\n";
    echo "<meta name=\"description\" content=\"{$metaDescription}\">\n";
    echo "<meta name=\"robots\" content=\"index, follow\">\n";
    echo "<link rel=\"canonical\" href=\"{$urlAtual}\">\n";
    
    // Open Graph (Facebook/WhatsApp)
    echo "<meta property=\"og:type\" content=\"article\">\n";
    echo "<meta property=\"og:title\" content=\"" . htmlspecialchars($titulo ?: $tituloSite, ENT_QUOTES, 'UTF-8') . "\">\n";
    echo "<meta property=\"og:description\" content=\"{$metaDescription}\">\n";
    echo "<meta property=\"og:url\" content=\"{$urlAtual}\">\n";
    echo "<meta property=\"og:image\" content=\"{$ogImagem}\">\n";
    echo "<meta property=\"og:site_name\" content=\"{$tituloSite}\">\n";
    
    // Twitter Cards
    echo "<meta name=\"twitter:card\" content=\"summary_large_image\">\n";
    echo "<meta name=\"twitter:title\" content=\"" . htmlspecialchars($titulo ?: $tituloSite, ENT_QUOTES, 'UTF-8') . "\">\n";
    echo "<meta name=\"twitter:description\" content=\"{$metaDescription}\">\n";
    echo "<meta name=\"twitter:image\" content=\"{$ogImagem}\">\n";
}

/**
 * Gera JSON-LD Schema.org para NewsArticle
 */
function gerarSchemaNewsArticle($noticia) {
    if (!$noticia) {
        return '';
    }
    
    $config = getConfiguracoes();
    $tituloSite = $config['titulo_site'] ?? 'Fênix Portal';
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'NewsArticle',
        'headline' => $noticia['titulo'],
        'description' => strip_tags($noticia['resumo']),
        'image' => $noticia['imagem_capa'] ?: 'https://via.placeholder.com/1200x630?text=Fênix+Portal',
        'datePublished' => $noticia['data_publicacao'],
        'dateModified' => $noticia['data_publicacao'],
        'author' => [
            '@type' => 'Organization',
            'name' => $tituloSite
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => $tituloSite,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $config['logotipo_path'] ?: 'https://via.placeholder.com/200x60?text=Logo'
            ]
        ]
    ];
    
    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Formata data para exibição
 */
function formatarData($data) {
    return date('d/m/Y H:i', strtotime($data));
}

/**
 * Função de upload de imagem
 */
function uploadImagem($arquivo, $diretorio = 'uploads') {
    $diretorioCompleto = __DIR__ . '/../' . $diretorio;
    
    if (!file_exists($diretorioCompleto)) {
        mkdir($diretorioCompleto, 0755, true);
    }
    
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($extensao, $extensoesPermitidas)) {
        return ['sucesso' => false, 'erro' => 'Extensão não permitida'];
    }
    
    if ($arquivo['size'] > 5000000) { // 5MB max
        return ['sucesso' => false, 'erro' => 'Arquivo muito grande'];
    }
    
    $nomeUnico = uniqid() . '.' . $extensao;
    $caminhoDestino = $diretorioCompleto . '/' . $nomeUnico;
    
    if (move_uploaded_file($arquivo['tmp_name'], $caminhoDestino)) {
        return ['sucesso' => true, 'caminho' => $diretorio . '/' . $nomeUnico];
    }
    
    return ['sucesso' => false, 'erro' => 'Erro ao salvar arquivo'];
}
