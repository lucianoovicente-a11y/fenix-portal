<?php
/**
 * Fênix Portal - Página Principal (Index)
 * Layout em Grid com Tailwind CSS
 */

require_once __DIR__ . '/includes/functions.php';

// Auto-update: verifica se precisa atualizar notícias
if (precisaAtualizarNoticias()) {
    buscarNoticiasExternas();
    atualizarTimestamp();
}

$config = getConfiguracoes();
$categorias = getCategorias();
$noticiaDestaque = getNoticiaDestaque();
$noticias = getNoticias(8);
$publicidadeEsq = getPublicidade('lateral_esq');
$publicidadeDir = getPublicidade('lateral_dir');
$enquete = getEnqueteAtiva();

// Processar voto na enquete
if (isset($_POST['votar_enquete']) && isset($_POST['opcao_id'])) {
    votarEnquete((int)$_POST['opcao_id']);
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php gerarMetaTags($config['titulo_site'], 'Portal de notícias com informações atualizadas sobre esportes, política, gospel e atualidades.'); ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .grid-noticias {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .destaque-principal {
            grid-column: span 2;
        }
        @media (max-width: 768px) {
            .destaque-principal {
                grid-column: span 1;
            }
        }
    </style>
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
            
            <!-- Área Principal de Notícias -->
            <section class="lg:col-span-8">
                <!-- Notícia de Destaque -->
                <?php if ($noticiaDestaque): ?>
                <article class="bg-white rounded-lg shadow-lg overflow-hidden mb-8 destaque-principal">
                    <a href="noticia.php?slug=<?php echo urlencode($noticiaDestaque['slug']); ?>">
                        <?php if ($noticiaDestaque['imagem_capa']): ?>
                            <img src="<?php echo htmlspecialchars($noticiaDestaque['imagem_capa']); ?>" alt="<?php echo htmlspecialchars($noticiaDestaque['titulo']); ?>" class="w-full h-96 object-cover">
                        <?php else: ?>
                            <div class="w-full h-96 bg-gradient-to-r from-red-600 to-red-800 flex items-center justify-center">
                                <span class="text-white text-2xl font-bold">DESTAQUE</span>
                            </div>
                        <?php endif; ?>
                        <div class="p-6">
                            <span class="text-red-600 font-semibold text-sm"><?php echo htmlspecialchars($noticiaDestaque['categoria_nome'] ?? 'Geral'); ?></span>
                            <h2 class="text-3xl font-bold text-gray-800 mt-2 hover:text-red-600 transition"><?php echo htmlspecialchars($noticiaDestaque['titulo']); ?></h2>
                            <p class="text-gray-600 mt-3"><?php echo htmlspecialchars(mb_substr(strip_tags($noticiaDestaque['resumo']), 0, 200)); ?></p>
                            <span class="text-gray-400 text-sm mt-4 block"><?php echo formatarData($noticiaDestaque['data_publicacao']); ?></span>
                        </div>
                    </a>
                </article>
                <?php endif; ?>
                
                <!-- Grade de Notícias Secundárias -->
                <h3 class="text-2xl font-bold text-gray-800 mb-6 border-l-4 border-red-600 pl-4">Últimas Notícias</h3>
                <div class="grid-noticias">
                    <?php foreach ($noticias as $noticia): ?>
                        <?php if ($noticia['id'] == ($noticiaDestaque['id'] ?? 0)) continue; ?>
                        <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition">
                            <a href="noticia.php?slug=<?php echo urlencode($noticia['slug']); ?>">
                                <?php if ($noticia['imagem_capa']): ?>
                                    <img src="<?php echo htmlspecialchars($noticia['imagem_capa']); ?>" alt="<?php echo htmlspecialchars($noticia['titulo']); ?>" class="w-full h-48 object-cover">
                                <?php else: ?>
                                    <div class="w-full h-48 bg-gradient-to-r from-gray-400 to-gray-600 flex items-center justify-center">
                                        <span class="text-white font-bold">SEM IMAGEM</span>
                                    </div>
                                <?php endif; ?>
                                <div class="p-4">
                                    <span class="text-red-600 font-semibold text-xs"><?php echo htmlspecialchars($noticia['categoria_nome'] ?? 'Geral'); ?></span>
                                    <h4 class="text-lg font-bold text-gray-800 mt-2 hover:text-red-600 transition line-clamp-2"><?php echo htmlspecialchars($noticia['titulo']); ?></h4>
                                    <p class="text-gray-600 text-sm mt-2 line-clamp-2"><?php echo htmlspecialchars(mb_substr(strip_tags($noticia['resumo']), 0, 100)); ?></p>
                                    <span class="text-gray-400 text-xs mt-3 block"><?php echo formatarData($noticia['data_publicacao']); ?></span>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
                
                <!-- Paginação -->
                <div class="mt-8 flex justify-center">
                    <a href="#" class="px-6 py-3 bg-white border border-gray-300 rounded hover:bg-gray-50 text-gray-700">Carregar Mais</a>
                </div>
            </section>
            
            <!-- Sidebar Direita (Publicidade + Enquete) -->
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
