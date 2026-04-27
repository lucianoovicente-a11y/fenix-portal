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

/**
 * Gera URL de compartilhamento para WhatsApp
 */
function gerarLinkWhatsApp($titulo, $url) {
    $texto = "Confira esta notícia: " . $titulo . " - " . $url;
    return "https://api.whatsapp.com/send?text=" . urlencode($texto);
}

/**
 * Gera botões de compartilhamento social
 */
function gerarBotoesCompartilhamento($titulo, $url) {
    $linkWhatsApp = gerarLinkWhatsApp($titulo, $url);
    $linkFacebook = "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($url);
    $linkTwitter = "https://twitter.com/intent/tweet?text=" . urlencode($titulo) . "&url=" . urlencode($url);
    $linkTelegram = "https://t.me/share/url?url=" . urlencode($url) . "&text=" . urlencode($titulo);
    
    $html = '<div class="flex flex-wrap gap-3 mt-6 pt-6 border-t border-gray-200">';
    $html .= '<span class="text-sm font-semibold text-gray-600 w-full mb-2">Compartilhar:</span>';
    
    // WhatsApp
    $html .= '<a href="' . htmlspecialchars($linkWhatsApp) . '" target="_blank" rel="noopener" 
              class="flex items-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm font-medium">
              <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
              WhatsApp
              </a>';
    
    // Facebook
    $html .= '<a href="' . htmlspecialchars($linkFacebook) . '" target="_blank" rel="noopener" 
              class="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">
              <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
              Facebook
              </a>';
    
    // Twitter/X
    $html .= '<a href="' . htmlspecialchars($linkTwitter) . '" target="_blank" rel="noopener" 
              class="flex items-center px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition text-sm font-medium">
              <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
              X
              </a>';
    
    // Telegram
    $html .= '<a href="' . htmlspecialchars($linkTelegram) . '" target="_blank" rel="noopener" 
              class="flex items-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition text-sm font-medium">
              <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
              Telegram
              </a>';
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Fetch and save RSS news - Função principal para o Webhook
 * Busca feeds RSS e salva no banco de dados
 */
function fetch_and_save_rss_news($pdo) {
    // Feeds RSS de exemplo (pode ser expandido conforme necessidade)
    $feeds = [
        'gerais' => 'https://g1.globo.com/Feed/rss.xml',
        'politica' => 'https://g1.globo.com/politica/Feed/politica/rss.xml',
        'esportes' => 'https://ge.globo.com/Feed/noticias/rss.xml',
        // Adicione mais feeds conforme necessário
    ];
    
    $noticiasAdicionadas = 0;
    $erros = [];
    
    foreach ($feeds as $categoriaSlug => $feedUrl) {
        try {
            // Usa cURL se disponível, senão file_get_contents
            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $feedUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                $rss = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode !== 200 || $rss === false) {
                    $erros[] = "Falha ao buscar feed {$categoriaSlug}: HTTP {$httpCode}";
                    continue;
                }
            } else {
                $rss = @file_get_contents($feedUrl);
                if ($rss === false) {
                    $erros[] = "Falha ao buscar feed {$categoriaSlug}";
                    continue;
                }
            }
            
            $xml = @simplexml_load_string($rss);
            if (!$xml) {
                $erros[] = "XML inválido para feed {$categoriaSlug}";
                continue;
            }
            
            // Buscar ID da categoria no banco
            $stmt = $pdo->prepare("SELECT id FROM categorias WHERE slug = :slug");
            $stmt->execute(['slug' => $categoriaSlug]);
            $catData = $stmt->fetch();
            
            if (!$catData) {
                // Tenta usar categoria 'Gerais' como fallback
                $stmt = $pdo->prepare("SELECT id FROM categorias WHERE slug = 'gerais'");
                $stmt->execute();
                $catData = $stmt->fetch();
                
                if (!$catData) {
                    $erros[] = "Categoria não encontrada: {$categoriaSlug}";
                    continue;
                }
            }
            
            $idCategoria = $catData['id'];
            
            // Processar itens do feed (limitado a 5 por categoria)
            $contador = 0;
            if (!isset($xml->channel->item)) {
                continue;
            }
            
            foreach ($xml->channel->item as $item) {
                if ($contador >= 5) {
                    break;
                }
                
                $titulo = trim((string)$item->title);
                $link = trim((string)$item->link);
                $descricao = trim((string)$item->description);
                $dataPub = (string)$item->pubDate;
                
                // Pula se já existir notícia com este título
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM noticias WHERE titulo = :titulo");
                $stmt->execute(['titulo' => $titulo]);
                if ($stmt->fetch()['count'] > 0) {
                    continue;
                }
                
                // Gerar slug único
                $slugBase = gerarSlug($titulo);
                $slug = $slugBase;
                $contadorSlug = 1;
                
                while (true) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM noticias WHERE slug = :slug");
                    $stmt->execute(['slug' => $slug]);
                    if ($stmt->fetch()['count'] == 0) {
                        break;
                    }
                    $slug = $slugBase . '-' . $contadorSlug;
                    $contadorSlug++;
                }
                
                // Extrair imagem se disponível (alguns feeds têm media:content)
                $imagem = null;
                $namespaces = $item->getNamespaces(true);
                if (isset($namespaces['media'])) {
                    $media = $item->children($namespaces['media']);
                    if (isset($media->content)) {
                        $attrs = $media->content->attributes();
                        if (isset($attrs['url'])) {
                            $imagem = (string)$attrs['url'];
                        }
                    }
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
                    'conteudo' => "<p>Fonte: <a href='" . htmlspecialchars($link) . "' target='_blank' rel='noopener'>Leia a matéria completa</a></p>" . $descricao,
                    'imagem' => $imagem,
                    'id_categoria' => $idCategoria,
                    'data_pub' => $dataPub ? date('Y-m-d H:i:s', strtotime($dataPub)) : date('Y-m-d H:i:s')
                ]);
                
                $noticiasAdicionadas++;
                $contador++;
            }
            
        } catch (Exception $e) {
            $erros[] = "Erro no feed {$categoriaSlug}: " . $e->getMessage();
            continue;
        }
    }
    
    return [
        'success' => true,
        'details' => "{$noticiasAdicionadas} notícias adicionadas.",
        'errors' => $erros
    ];
}
