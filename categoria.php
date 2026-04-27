<?php
/**
 * Fênix Portal - Página de Categoria com Paginação Otimizada
 * Suporta categorias, tags e times específicos.
 * URL: /categoria.php?slug=politica ou /categoria.php?time=flamengo
 */

require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/ads_engine.php';
require_once 'includes/team_logic.php';

$pdo = get_db_connection();

// Configurações de Paginação
$items_per_page = 12;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $items_per_page;

// Identifica o tipo de filtro (Categoria ou Time)
$categorySlug = $_GET['slug'] ?? null;
$teamSlug = $_GET['time'] ?? null;

$title = "Notícias";
$news = [];
$total_items = 0;

if ($teamSlug) {
    // Filtragem por Time
    $terms = get_team_search_terms($teamSlug);
    $title = "Notícias sobre " . ucfirst($teamSlug);
    $news = get_news_by_team($pdo, $teamSlug, $items_per_page, $offset);
    $total_items = count_news_by_team($pdo, $teamSlug);
} elseif ($categorySlug) {
    // Filtragem por Categoria
    $stmt = $pdo->prepare("SELECT id FROM categorias WHERE slug = ?");
    $stmt->execute([$categorySlug]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($category) {
        $title = "Categoria: " . $category['nome'];
        
        // Query otimizada com LIMIT e OFFSET
        $stmt = $pdo->prepare("
            SELECT n.*, c.nome as categoria_nome, c.slug as categoria_slug 
            FROM noticias n 
            JOIN categorias c ON n.id_categoria = c.id 
            WHERE n.id_categoria = ? AND n.data_publicacao <= datetime('now')
            ORDER BY n.data_publicacao DESC 
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(1, $category['id'], PDO::PARAM_INT);
        $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $news = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Conta total para paginação
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM noticias WHERE id_categoria = ? AND data_publicacao <= datetime('now')");
        $stmt->execute([$category['id']]);
        $total_items = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } else {
        http_response_code(404);
        $title = "Categoria não encontrada";
    }
} else {
    // Se não houver filtro, redireciona ou mostra tudo (opcional)
    header("Location: index.php");
    exit;
}

$total_pages = ceil($total_items / $items_per_page);

// Verifica se é categoria urgente para estilização especial
$isUrgent = is_urgent_category($categorySlug);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Fênix Portal</title>
    <meta name="description" content="Confira as últimas notícias de <?= htmlspecialchars($title) ?> no Fênix Portal.">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 font-sans">

<!-- Header Simples -->
<header class="bg-white shadow-md sticky top-0 z-50">
    <div class="container mx-auto px-4 py-4 flex justify-between items-center">
        <a href="index.php" class="text-2xl font-bold text-red-600">FÊNIX PORTAL</a>
        <nav class="hidden md:flex space-x-4">
            <a href="index.php" class="hover:text-red-600 transition">Início</a>
            <a href="?slug=politica" class="hover:text-red-600 transition">Política</a>
            <a href="?slug=esportes" class="hover:text-red-600 transition">Esportes</a>
            <a href="?time=flamengo" class="hover:text-red-600 transition">Flamengo</a>
        </nav>
    </div>
</header>

<div class="container mx-auto px-4 py-8 flex flex-col md:flex-row gap-8">
    
    <!-- Conteúdo Principal -->
    <main class="w-full md:w-3/4">
        <h1 class="text-3xl font-bold mb-6 <?= $isUrgent ? 'text-red-700 animate-pulse' : 'text-gray-900' ?>">
            <?= $isUrgent ? '🚨 ' : '' ?><?= htmlspecialchars($title) ?>
        </h1>

        <?php if (empty($news)): ?>
            <p class="text-gray-500 text-lg">Nenhuma notícia encontrada nesta seção.</p>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($news as $item): ?>
                    <article class="bg-white rounded-lg shadow hover:shadow-lg transition overflow-hidden flex flex-col">
                        <?php if ($item['imagem_capa']): ?>
                            <img src="<?= htmlspecialchars($item['imagem_capa']) ?>" alt="<?= htmlspecialchars($item['titulo']) ?>" class="w-full h-48 object-cover">
                        <?php else: ?>
                            <div class="w-full h-48 bg-gray-200 flex items-center justify-center text-gray-400">Sem imagem</div>
                        <?php endif; ?>
                        
                        <div class="p-4 flex-1 flex flex-col">
                            <span class="text-xs font-semibold text-red-600 uppercase mb-2"><?= htmlspecialchars($item['categoria_nome'] ?? 'Geral') ?></span>
                            <h2 class="text-lg font-bold mb-2 hover:text-red-600 transition">
                                <a href="noticia.php?slug=<?= htmlspecialchars($item['slug']) ?>">
                                    <?= htmlspecialchars($item['titulo']) ?>
                                </a>
                            </h2>
                            <p class="text-gray-600 text-sm mb-4 flex-1"><?= htmlspecialchars(substr($item['resumo'], 0, 100)) ?>...</p>
                            <span class="text-xs text-gray-400"><?= date('d/m/Y H:i', strtotime($item['data_publicacao'])) ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Paginação -->
            <?php if ($total_pages > 1): ?>
                <div class="mt-8 flex justify-center items-center space-x-2">
                    <?php if ($current_page > 1): ?>
                        <a href="?slug=<?= $categorySlug ?>&time=<?= $teamSlug ?>&page=<?= $current_page - 1 ?>" class="px-4 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50 transition">Anterior</a>
                    <?php endif; ?>
                    
                    <span class="px-4 py-2 text-gray-600">Página <?= $current_page ?> de <?= $total_pages ?></span>
                    
                    <?php if ($current_page < $total_pages): ?>
                        <a href="?slug=<?= $categorySlug ?>&time=<?= $teamSlug ?>&page=<?= $current_page + 1 ?>" class="px-4 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50 transition">Próxima</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <!-- Sidebar com Publicidade -->
    <aside class="w-full md:w-1/4">
        <?= render_ad_slot($pdo, 'lateral_dir') ?>
        
        <!-- Widget Extra: Times Populares -->
        <div class="bg-white p-4 rounded-lg shadow mb-6">
            <h3 class="font-bold text-lg mb-4 border-b pb-2">Times em Alta</h3>
            <ul class="space-y-2">
                <li><a href="?time=flamengo" class="block hover:text-red-600 transition">Flamengo</a></li>
                <li><a href="?time=fluminense" class="block hover:text-red-600 transition">Fluminense</a></li>
                <li><a href="?time=vasco" class="block hover:text-red-600 transition">Vasco</a></li>
                <li><a href="?time=botafogo" class="block hover:text-red-600 transition">Botafogo</a></li>
            </ul>
        </div>
    </aside>
</div>

<footer class="bg-gray-800 text-white py-8 mt-12">
    <div class="container mx-auto px-4 text-center">
        <p>&copy; <?= date('Y') ?> Fênix Portal. Todos os direitos reservados.</p>
    </div>
</footer>

</body>
</html>
