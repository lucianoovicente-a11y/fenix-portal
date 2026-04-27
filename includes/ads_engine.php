<?php
/**
 * Fênix Portal - Ads Engine
 * Gerencia a exibição de publicidade nas laterais com lógica responsiva.
 */

function get_ads_by_position($pdo, $position) {
    // Posições: 'lateral_esq', 'lateral_dir', 'topo', 'rodape'
    $stmt = $pdo->prepare("SELECT codigo_html_ou_imagem, status FROM publicidade WHERE posicao = ? AND status = 1 ORDER BY id DESC");
    $stmt->execute([$position]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function render_ad_slot($pdo, $position, $is_mobile = false) {
    $ads = get_ads_by_position($pdo, $position);
    
    if (empty($ads)) {
        return ''; // Retorna vazio se não houver anúncios
    }

    $html = '';
    
    // Wrapper com classes Tailwind para responsividade
    // Em mobile (telas < 768px), podemos optar por ocultar ou empilhar
    $containerClass = ($position === 'lateral_esq' || $position === 'lateral_dir') 
        ? 'hidden md:block mb-6 space-y-6' 
        : 'mb-6 space-y-6';

    $html .= "<div class=\"{$containerClass}\" data-ad-position=\"{$position}\">";
    
    foreach ($ads as $ad) {
        $content = $ad['codigo_html_ou_imagem'];
        
        // Verifica se é apenas uma URL de imagem e cria a tag img automaticamente
        if (filter_var($content, FILTER_VALIDATE_URL) && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $content)) {
            $html .= "<a href=\"#\" class=\"block overflow-hidden rounded-lg shadow-sm hover:shadow-md transition-shadow\">";
            $html .= "<img src=\"{$content}\" alt=\"Publicidade\" class=\"w-full h-auto object-cover\" loading=\"lazy\">";
            $html .= "</a>";
        } else {
            // Assume que é um script (AdSense, etc) ou HTML complexo
            // Nota: Em produção, considere usar CSP (Content Security Policy) adequado
            $html .= "<div class=\"ad-content bg-gray-50 rounded-lg p-2 text-center border border-gray-200\">";
            $html .= $content;
            $html .= "</div>";
        }
    }
    
    $html .= "</div>";
    
    return $html;
}

/**
 * Função auxiliar para verificar se o usuário deve ver anúncios
 * (Pode ser expandida para verificar AdBlockers ou níveis de assinatura)
 */
function can_show_ads() {
    return true; 
}
?>
