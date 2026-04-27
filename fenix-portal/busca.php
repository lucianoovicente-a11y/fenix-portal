<?php
/**
 * Fênix Portal - Página de Busca
 * busca.php
 */

require_once __DIR__ . '/includes/functions.php';

// Auto-update: verifica se precisa atualizar notícias
if (precisaAtualizarNoticias()) {
    buscarNoticiasExternas();
    atualizarTimestamp();
}

$config = getConfiguracoes();
$categorias = getCategorias();
$publicidadeEsq = getPublicidade('lateral_esq');
$publicidadeDir = getPublicidade('lateral_dir');
$enquete = getEnqueteAtiva();

// Termos de busca
$termo_busca = isset($_GET['q']) ? trim($_GET['q']) : '';
$resultados_busca = [];

if (!empty($termo_busca)) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT n.*, c.nome as categoria_nome, c.slug as categoria_slug 
        FROM noticias n 
        LEFT JOIN categorias c ON n.id_categoria = c.id 
        WHERE n.titulo LIKE :termo OR n.resumo LIKE :termo OR n.conteudo LIKE :termo
        ORDER BY n.data_publicacao DESC
    ");
    $stmt->execute(['termo' => '%' . $termo_busca . '%']);
    $resultados_busca = $stmt->fetchAll();
}

// Processar voto na enquete
if (isset($_POST['votar_enquete']) && isset($_POST['opcao_id'])) {
    votarEnquete((int)$_POST['opcao_id']);
    header('Location: busca.php?q=' . urlencode($termo_busca));
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php gerarMetaTags('Busca: ' . ($termo_busca ?: ''), 'Resultados da busca no Fênix Portal'); ?>
    <script src="https://cdn.tailwindcss.com"></script>
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
                    <input type="text" name="q" placeholder="Buscar notícias..." value="<?php echo htmlspecialchars($termo_busca); ?>" class="border border-gray-300 px-4 py-2 rounded-l focus:outline-none focus:border-red-600">
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
            
            <!-- Resultados da Busca -->
            <section class="lg:col-span-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">
                    <?php if (!empty($termo_busca)): ?>
                        Resultados para: "<span class="text-red-600"><?php echo htmlspecialchars($termo_busca); ?></span>"
                    <?php else: ?>
                        Busca
                    <?php endif; ?>
                </h1>
                
                <?php if (!empty($termo_busca)): ?>
                    <p class="text-gray-600 mb-6"><?php echo count($resultados_busca); ?> resultado(s) encontrado(s)</p>
                    
                    <?php if (count($resultados_busca) > 0): ?>
                        <div class="space-y-6">
                            <?php foreach ($resultados_busca as $noticia): ?>
                                <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition">
                                    <a href="noticia.php?slug=<?php echo urlencode($noticia['slug']); ?>" class="block md:flex">
                                        <?php if ($noticia['imagem_capa']): ?>
                                            <div class="md:w-64 h-48 md:h-auto flex-shrink-0">
                                                <img src="<?php echo htmlspecialchars($noticia['imagem_capa']); ?>" alt="<?php echo htmlspecialchars($noticia['titulo']); ?>" class="w-full h-full object-cover">
                                            </div>
                                        <?php endif; ?>
                                        <div class="p-6 flex-1">
                                            <span class="text-red-600 font-semibold text-xs"><?php echo htmlspecialchars($noticia['categoria_nome'] ?? 'Geral'); ?></span>
                                            <h2 class="text-xl font-bold text-gray-800 mt-2 hover:text-red-600 transition"><?php echo htmlspecialchars($noticia['titulo']); ?></h2>
                                            <p class="text-gray-600 mt-2"><?php echo htmlspecialchars(mb_substr(strip_tags($noticia['resumo']), 0, 200)); ?>...</p>
                                            <span class="text-gray-400 text-sm mt-4 block"><?php echo formatarData($noticia['data_publicacao']); ?></span>
                                        </div>
                                    </a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="bg-white rounded-lg shadow-md p-8 text-center">
                            <p class="text-gray-600 text-lg">Nenhuma notícia encontrada para "<?php echo htmlspecialchars($termo_busca); ?>"</p>
                            <p class="text-gray-500 mt-2">Tente buscar com outros termos ou verifique a ortografia.</p>
                            <a href="index.php" class="inline-block mt-4 bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 transition">Voltar ao início</a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="bg-white rounded-lg shadow-md p-8 text-center">
                        <p class="text-gray-600 text-lg">Digite um termo para buscar notícias.</p>
                        <form action="busca.php" method="GET" class="mt-6 flex max-w-md mx-auto">
                            <input type="text" name="q" placeholder="Digite sua busca..." class="flex-1 border border-gray-300 px-4 py-2 rounded-l focus:outline-none focus:border-red-600">
                            <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-r hover:bg-red-700">🔍</button>
                        </form>
                    </div>
                <?php endif; ?>
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
                            <li><a href="index.php?categoria=<?php echo urlencode($cat['slug']); ?>" class="text-gray-400 hover:text-white transition"><?php echo htmlspecialchars($cat['nome']); ?></a></li>
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
