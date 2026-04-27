<?php
/**
 * Fênix Portal - Lógica de Times e Categorias Avançadas
 * Permite filtrar notícias por times específicos (ex: Flamengo) e categorias detalhadas.
 */

/**
 * Busca notícias de um time específico baseado no slug do time.
 * Ex: /time/flamengo
 */
function get_news_by_team($pdo, $teamSlug, $limit = 10, $offset = 0) {
    // Busca notícias onde o título ou conteúdo menciona o time, 
    // OU que possuem uma tag específica (se implementado futuramente)
    // Aqui usamos LIKE para simplicidade no SQLite sem tabela de tags complexa
    $sql = "SELECT n.*, c.nome as categoria_nome, c.slug as categoria_slug 
            FROM noticias n 
            LEFT JOIN categorias c ON n.id_categoria = c.id 
            WHERE (n.titulo LIKE :term OR n.conteudo LIKE :term) 
            AND n.data_publicacao <= datetime('now')
            ORDER BY n.data_publicacao DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    $searchTerm = '%' . $teamSlug . '%'; // Simplificação: busca pelo nome no texto
    $stmt->bindValue(':term', $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Conta o total de notícias de um time para paginação
 */
function count_news_by_team($pdo, $teamSlug) {
    $sql = "SELECT COUNT(*) as total FROM noticias 
            WHERE (titulo LIKE :term OR conteudo LIKE :term) 
            AND data_publicacao <= datetime('now')";
    
    $stmt = $pdo->prepare($sql);
    $searchTerm = '%' . $teamSlug . '%';
    $stmt->bindValue(':term', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int)$result['total'];
}

/**
 * Mapeamento de slugs amigáveis para termos de busca reais
 * Ex: 'flamengo' -> 'Flamengo', 'Mengão'
 */
function get_team_search_terms($slug) {
    $teams = [
        'flamengo' => ['Flamengo', 'Mengão', 'CRF'],
        'fluminense' => ['Fluminense', 'Tricolor', 'FCF'],
        'vasco' => ['Vasco', 'Crossmaltino', 'CRVG'],
        'botafogo' => ['Botafogo', 'Alvinegro', 'BFR'],
        'real-madrid' => ['Real Madrid', 'Merengues'],
        'barcelona' => ['Barcelona', 'Barça', 'FCB'],
    ];
    
    return $teams[$slug] ?? [ucfirst($slug)];
}

/**
 * Verifica se uma categoria é de "Urgente" (Guerra/Atualidades)
 */
function is_urgent_category($slug) {
    $urgentSlugs = ['guerra', 'atualidades', 'urgente', 'radar'];
    return in_array(strtolower($slug), $urgentSlugs);
}

/**
 * Verifica se é a janela de transferências (Esporte Internacional)
 */
function is_transfer_window_category($slug) {
    return strtolower($slug) === 'transferencias' || strtolower($slug) === 'mercado-da-bola';
}
?>
