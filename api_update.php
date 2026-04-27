<?php
/**
 * Fênix Portal - API de Atualização via Webhook
 * Endpoint seguro para forçar a atualização de notícias via cron externo ou bot.
 * Uso: api_update.php?token=SEU_TOKEN_SECRETO
 */

// Configuração do Token Secreto (Em produção, mover para variável de ambiente)
define('WEBHOOK_TOKEN', 'fenix_secure_token_2024_x9z8'); 

header('Content-Type: application/json');

// Verifica o método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido. Use GET.']);
    exit;
}

// Valida o token
$token = $_GET['token'] ?? '';

if ($token !== WEBHOOK_TOKEN) {
    http_response_code(403);
    echo json_encode(['error' => 'Token inválido ou ausente.']);
    exit;
}

// Inclui as dependências necessárias
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

try {
    // Inicia o processo de atualização
    $pdo = get_db_connection();
    
    // Simula a chamada da função de update (que viria do functions.php ou rss_fetcher.php)
    // Aqui chamamos a lógica real de busca de RSS
    $result = fetch_and_save_rss_news($pdo);
    
    if ($result['success']) {
        // Atualiza o timestamp da última atualização no banco
        $stmt = $pdo->prepare("INSERT OR REPLACE INTO configuracoes (id, chave, valor) VALUES (99, 'last_update_time', ?)");
        $stmt->execute([date('Y-m-d H:i:s')]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Notícias atualizadas com sucesso!',
            'details' => $result['details'],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao buscar notícias.',
            'error' => $result['error']
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno no servidor.',
        'error' => $e->getMessage()
    ]);
}
?>
