<?php
/**
 * Fênix Portal - Página Individual de Notícia (SEO Otimizado)
 * noticia.php
 */

require_once __DIR__ . '/includes/functions.php';

// Auto-update: verifica se precisa atualizar notícias
if (precisaAtualizarNoticias()) {
    buscarNoticiasExternas();
    atualizarTimestamp();
}

// Obter slug da URL
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if (empty($slug)) {
    header('Location: index.php');
    exit;
}

$noticia = getNoticiaPorSlug($slug);

if (!$noticia) {
    http_response_code(404);
    echo "Notícia não encontrada.";
    exit;
}

$config = getConfiguracoes();
$categorias = getCategorias();
$publicidadeEsq = getPublicidade('lateral_esq');
$publicidadeDir = getPublicidade('lateral_dir');
$enquete = getEnqueteAtiva();

// Notícias relacionadas (mesma categoria)
$noticiasRelacionadas = [];
if ($noticia['categoria_slug']) {
    $noticiasRelacionadas = getNoticias(4, 0, $noticia['categoria_slug']);
    // Remover a notícia atual das relacionadas
    $noticiasRelacionadas = array_filter($noticiasRelacionadas, function($n) use ($noticia) {
        return $n['id'] != $noticia['id'];
    });
}

// Processar voto na enquete
if (isset($_POST['votar_enquete']) && isset($_POST['opcao_id'])) {
    votarEnquete((int)$_POST['opcao_id']);
    header('Location: noticia.php?slug=' . urlencode($slug));
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php gerarMetaTags($noticia['titulo'], $noticia['resumo'], $noticia['imagem_capa']); ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php echo gerarSchemaNewsArticle($noticia); ?>
</head>
<body class="bg-gray-100">
    <!-- Cabeçalho -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <!-- Logotipo -->
                <a href="index.php" class="text-3xl font-bold text-red-600">
                    <?php if ($config['logotipo_path']): ?>
                        <img src="<?php echo htmlspecialchars($config['logotipo_path']); ?>" alt="<?php echo htmlspecialchars($config['titulo_site']); ?>" class="h-12">
                    <?php else: ?>
                        <?php echo htmlspecialchars($config['titulo_site']); ?>
                    <?php endif; ?>
                </a>
                
                <!-- Menu de Categorias -->
                <nav class="hidden md:block">
                    <ul class="flex space-x-6">
                        <li><a href="index.php" class="text-gray-700 hover:text-red-600 font-medium">Home</a></li>
                        <?php foreach ($categorias as $cat): ?>
                            <li><a href="?categoria=<?php echo urlencode($cat['slug']); ?>" class="text-gray-700 hover:text-red-600 font-medium"><?php echo htmlspecialchars($cat['nome']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
                
                <!-- Barra de Busca -->
                <form action="busca.php" method="GET" class="flex">
                    <input type="text" name="q" placeholder="Buscar notícias..." class="border border-gray-300 px-4 py-2 rounded-l focus:outline-none focus:border-red-600">
                    <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-r hover:bg-red-700">🔍</button>
                </form>
            </div>
            
            <!-- Menu Mobile -->
            <div class="md:hidden mt-4">
                <select onchange="if(this.value) window.location.href=this.value" class="w-full border border-gray-300 px-4 py-2 rounded">
                    <option value="index.php">Selecione uma categoria...</option>
                    <option value="index.php">Home</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="?categoria=<?php echo urlencode($cat['slug']); ?>"><?php echo htmlspecialchars($cat['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Sidebar Esquerda (Publicidade) -->
            <?php if ($publicidadeEsq): ?>
            <aside class="hidden lg:block lg:col-span-2">
                <div class="sticky top-24">
                    <?php echo $publicidadeEsq['codigo_html_ou_imagem']; ?>
                </div>
            </aside>
            <?php endif; ?>
            
            <!-- Artigo Principal -->
            <article class="lg:col-span-8 bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Imagem de Capa -->
                <?php if ($noticia['imagem_capa']): ?>
                    <img src="<?php echo htmlspecialchars($noticia['imagem_capa']); ?>" alt="<?php echo htmlspecialchars($noticia['titulo']); ?>" class="w-full h-[400px] object-cover">
                <?php else: ?>
                    <div class="w-full h-[400px] bg-gradient-to-r from-red-600 to-red-800 flex items-center justify-center">
                        <span class="text-white text-3xl font-bold"><?php echo htmlspecialchars($noticia['categoria_nome'] ?? 'Notícia'); ?></span>
                    </div>
                <?php endif; ?>
                
                <!-- Conteúdo do Artigo -->
                <div class="p-8">
                    <!-- Categoria e Data -->
                    <div class="flex items-center gap-4 mb-4">
                        <a href="?categoria=<?php echo urlencode($noticia['categoria_slug'] ?? 'gerais'); ?>" class="bg-red-600 text-white px-3 py-1 rounded text-sm font-semibold hover:bg-red-700 transition">
                            <?php echo htmlspecialchars($noticia['categoria_nome'] ?? 'Geral'); ?>
                        </a>
                        <span class="text-gray-500 text-sm">📅 <?php echo formatarData($noticia['data_publicacao']); ?></span>
                    </div>
                    
                    <!-- Título -->
                    <h1 class="text-4xl font-bold text-gray-800 mb-6 leading-tight">
                        <?php echo htmlspecialchars($noticia['titulo']); ?>
                    </h1>
                    
                    <!-- Resumo -->
                    <div class="text-xl text-gray-600 mb-6 italic border-l-4 border-red-600 pl-4">
                        <?php echo nl2br(htmlspecialchars($noticia['resumo'])); ?>
                    </div>
                    
                    <!-- Conteúdo Completo -->
                    <div class="prose prose-lg max-w-none text-gray-700">
                        <?php echo $noticia['conteudo']; ?>
                    </div>
                    
                    <!-- Compartilhamento -->
                    <div class="mt-8 pt-8 border-t border-gray-200">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Compartilhar:</h3>
                        <div class="flex gap-4">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}"); ?>" target="_blank" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Facebook</a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}"); ?>&text=<?php echo urlencode($noticia['titulo']); ?>" target="_blank" class="bg-sky-500 text-white px-4 py-2 rounded hover:bg-sky-600 transition">Twitter</a>
                            <a href="https://wa.me/?text=<?php echo urlencode($noticia['titulo'] . ' - ' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}"); ?>" target="_blank" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition">WhatsApp</a>
                        </div>
                    </div>
                </div>
            </article>
            
            <!-- Sidebar Direita (Publicidade + Enquete + Relacionadas) -->
            <aside class="lg:col-span-2 space-y-8">
                <?php if ($publicidadeDir): ?>
                <div class="sticky top-24">
                    <?php echo $publicidadeDir['codigo_html_ou_imagem']; ?>
                </div>
                <?php endif; ?>
                
                <!-- Enquete -->
                <?php if ($enquete): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h4 class="text-lg font-bold text-gray-800 mb-4">📊 Enquete</h4>
                    <p class="text-gray-700 mb-4 font-medium"><?php echo htmlspecialchars($enquete['pergunta']); ?></p>
                    <form method="POST">
                        <?php foreach ($enquete['opcoes'] as $opcao): ?>
                            <label class="flex items-center mb-3 cursor-pointer">
                                <input type="radio" name="opcao_id" value="<?php echo $opcao['id']; ?>" class="mr-3 text-red-600 focus:ring-red-600">
                                <span class="text-gray-700"><?php echo htmlspecialchars($opcao['texto']); ?></span>
                            </label>
                        <?php endforeach; ?>
                        <button type="submit" name="votar_enquete" class="w-full bg-red-600 text-white py-2 rounded hover:bg-red-700 transition">Votar</button>
                    </form>
                </div>
                <?php endif; ?>
            </aside>
            
        </div>
        
        <!-- Notícias Relacionadas -->
        <?php if (!empty($noticiasRelacionadas)): ?>
        <section class="mt-12">
            <h3 class="text-2xl font-bold text-gray-800 mb-6 border-l-4 border-red-600 pl-4">Notícias Relacionadas</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach (array_slice($noticiasRelacionadas, 0, 4) as $relacionada): ?>
                    <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition">
                        <a href="noticia.php?slug=<?php echo urlencode($relacionada['slug']); ?>">
                            <?php if ($relacionada['imagem_capa']): ?>
                                <img src="<?php echo htmlspecialchars($relacionada['imagem_capa']); ?>" alt="<?php echo htmlspecialchars($relacionada['titulo']); ?>" class="w-full h-40 object-cover">
                            <?php else: ?>
                                <div class="w-full h-40 bg-gradient-to-r from-gray-400 to-gray-600 flex items-center justify-center">
                                    <span class="text-white text-sm font-bold">SEM IMAGEM</span>
                                </div>
                            <?php endif; ?>
                            <div class="p-4">
                                <h4 class="text-sm font-bold text-gray-800 hover:text-red-600 transition line-clamp-2"><?php echo htmlspecialchars($relacionada['titulo']); ?></h4>
                                <span class="text-gray-400 text-xs mt-2 block"><?php echo formatarData($relacionada['data_publicacao']); ?></span>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <!-- Rodapé -->
    <footer class="bg-gray-800 text-white mt-16">
        <div class="container mx-auto px-4 py-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h5 class="text-xl font-bold mb-4"><?php echo htmlspecialchars($config['titulo_site']); ?></h5>
                    <p class="text-gray-400">Seu portal de notícias com informações atualizadas 24 horas por dia.</p>
                </div>
                <div>
                    <h5 class="text-lg font-bold mb-4">Categorias</h5>
                    <ul class="space-y-2">
                        <?php foreach (array_slice($categorias, 0, 5) as $cat): ?>
                            <li><a href="?categoria=<?php echo urlencode($cat['slug']); ?>" class="text-gray-400 hover:text-white transition"><?php echo htmlspecialchars($cat['nome']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div>
                    <h5 class="text-lg font-bold mb-4">Links Úteis</h5>
                    <ul class="space-y-2">
                        <li><a href="admin.php" class="text-gray-400 hover:text-white transition">Painel Admin</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">Política de Privacidade</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">Termos de Uso</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p><?php echo htmlspecialchars($config['texto_rodape']); ?></p>
            </div>
        </div>
    </footer>
</body>
</html>
