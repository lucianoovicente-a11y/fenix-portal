<?php
/**
 * Fênix Portal - Painel Administrativo Consolidado
 * admin.php
 */

session_start();
require_once __DIR__ . '/includes/functions.php';

// Configurações de login (em produção, usar banco de dados)
$ADMIN_USER = 'admin';
$ADMIN_PASS = 'admin123';

// Processar logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Processar login
if (isset($_POST['login'])) {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    
    if ($user === $ADMIN_USER && $pass === $ADMIN_PASS) {
        $_SESSION['admin_logged'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $erro_login = "Usuário ou senha inválidos!";
    }
}

// Verificar se está logado
$logado = isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true;

// Se não está logado, mostrar formulário de login
if (!$logado) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login Admin - Fênix Portal</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100 flex items-center justify-center min-h-screen">
        <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
            <h1 class="text-3xl font-bold text-red-600 text-center mb-6">Fênix Portal</h1>
            <h2 class="text-xl text-gray-700 text-center mb-6">Painel Administrativo</h2>
            
            <?php if (isset($erro_login)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($erro_login); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2">Usuário</label>
                    <input type="text" name="username" class="w-full border border-gray-300 px-4 py-2 rounded focus:outline-none focus:border-red-600" required>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 font-bold mb-2">Senha</label>
                    <input type="password" name="password" class="w-full border border-gray-300 px-4 py-2 rounded focus:outline-none focus:border-red-600" required>
                </div>
                <button type="submit" name="login" class="w-full bg-red-600 text-white py-2 rounded hover:bg-red-700 transition font-bold">Entrar</button>
            </form>
            
            <p class="text-gray-500 text-sm mt-6 text-center">
                Demo: admin / admin123
            </p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ==========================================
// PAINEL ADMINISTRATIVO (Área Logada)
// ==========================================

$config = getConfiguracoes();
$categorias = getCategorias();

// Processar ações do formulário
$mensagem = '';
$tipo_mensagem = '';

// Atualizar notícias manualmente
if (isset($_POST['atualizar_noticias'])) {
    $qtd = buscarNoticiasExternas();
    atualizarTimestamp();
    $mensagem = "$qtd notícia(s) adicionada(s) com sucesso!";
    $tipo_mensagem = 'success';
}

// Criar/Editar Notícia
if (isset($_POST['salvar_noticia'])) {
    $titulo = $_POST['titulo'] ?? '';
    $resumo = $_POST['resumo'] ?? '';
    $conteudo = $_POST['conteudo'] ?? '';
    $id_categoria = (int)($_POST['id_categoria'] ?? 1);
    $id_noticia = isset($_POST['id_noticia']) ? (int)$_POST['id_noticia'] : null;
    
    // Gerar slug
    $slug = gerarSlug($titulo);
    
    // Upload de imagem
    $imagem_capa = null;
    if (isset($_FILES['imagem_capa']) && $_FILES['imagem_capa']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadImagem($_FILES['imagem_capa']);
        if ($upload['sucesso']) {
            $imagem_capa = $upload['caminho'];
        }
    }
    
    global $pdo;
    
    if ($id_noticia) {
        // Atualizar notícia existente
        if ($imagem_capa) {
            $stmt = $pdo->prepare("UPDATE noticias SET titulo = :titulo, slug = :slug, resumo = :resumo, conteudo = :conteudo, imagem_capa = :imagem, id_categoria = :id_categoria WHERE id = :id");
            $stmt->execute([
                'titulo' => $titulo,
                'slug' => $slug,
                'resumo' => $resumo,
                'conteudo' => $conteudo,
                'imagem' => $imagem_capa,
                'id_categoria' => $id_categoria,
                'id' => $id_noticia
            ]);
        } else {
            $stmt = $pdo->prepare("UPDATE noticias SET titulo = :titulo, slug = :slug, resumo = :resumo, conteudo = :conteudo, id_categoria = :id_categoria WHERE id = :id");
            $stmt->execute([
                'titulo' => $titulo,
                'slug' => $slug,
                'resumo' => $resumo,
                'conteudo' => $conteudo,
                'id_categoria' => $id_categoria,
                'id' => $id_noticia
            ]);
        }
        $mensagem = "Notícia atualizada com sucesso!";
        $tipo_mensagem = 'success';
    } else {
        // Criar nova notícia
        $stmt = $pdo->prepare("INSERT INTO noticias (titulo, slug, resumo, conteudo, imagem_capa, id_categoria) VALUES (:titulo, :slug, :resumo, :conteudo, :imagem, :id_categoria)");
        $stmt->execute([
            'titulo' => $titulo,
            'slug' => $slug,
            'resumo' => $resumo,
            'conteudo' => $conteudo,
            'imagem' => $imagem_capa,
            'id_categoria' => $id_categoria
        ]);
        $mensagem = "Notícia criada com sucesso!";
        $tipo_mensagem = 'success';
    }
}

// Excluir notícia
if (isset($_GET['excluir_noticia'])) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM noticias WHERE id = :id");
    $stmt->execute(['id' => (int)$_GET['excluir_noticia']]);
    $mensagem = "Notícia excluída com sucesso!";
    $tipo_mensagem = 'success';
}

// Criar Enquete
if (isset($_POST['salvar_enquete'])) {
    $pergunta = $_POST['pergunta'] ?? '';
    $opcoes = $_POST['opcoes'] ?? [];
    
    if (!empty($pergunta) && !empty($opcoes)) {
        global $pdo;
        
        // Desativar enquetes anteriores
        $pdo->exec("UPDATE enquetes SET status = 0");
        
        // Criar nova enquete
        $stmt = $pdo->prepare("INSERT INTO enquetes (pergunta, status) VALUES (:pergunta, 1)");
        $stmt->execute(['pergunta' => $pergunta]);
        $id_enquete = $pdo->lastInsertId();
        
        // Inserir opções
        $stmt_opcao = $pdo->prepare("INSERT INTO enquetes_opcoes (id_enquete, texto_opcao) VALUES (:id_enquete, :texto)");
        foreach ($opcoes as $opcao) {
            if (!empty(trim($opcao))) {
                $stmt_opcao->execute(['id_enquete' => $id_enquete, 'texto' => trim($opcao)]);
            }
        }
        
        $mensagem = "Enquete criada com sucesso!";
        $tipo_mensagem = 'success';
    }
}

// Atualizar Publicidade
if (isset($_POST['salvar_publicidade'])) {
    $posicao = $_POST['posicao'] ?? '';
    $codigo = $_POST['codigo_html_ou_imagem'] ?? '';
    $status = isset($_POST['status']) ? 1 : 0;
    
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM publicidade WHERE posicao = :posicao");
    $stmt->execute(['posicao' => $posicao]);
    
    if ($stmt->fetch()['count'] > 0) {
        $stmt = $pdo->prepare("UPDATE publicidade SET codigo_html_ou_imagem = :codigo, status = :status WHERE posicao = :posicao");
        $stmt->execute(['codigo' => $codigo, 'status' => $status, 'posicao' => $posicao]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO publicidade (posicao, codigo_html_ou_imagem, status) VALUES (:posicao, :codigo, :status)");
        $stmt->execute(['posicao' => $posicao, 'codigo' => $codigo, 'status' => $status]);
    }
    
    $mensagem = "Publicidade atualizada com sucesso!";
    $tipo_mensagem = 'success';
}

// Atualizar Configurações
if (isset($_POST['salvar_configuracoes'])) {
    $titulo_site = $_POST['titulo_site'] ?? '';
    $texto_rodape = $_POST['texto_rodape'] ?? '';
    
    // Upload de logotipo
    $logotipo_path = $config['logotipo_path'];
    if (isset($_FILES['logotipo']) && $_FILES['logotipo']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadImagem($_FILES['logotipo']);
        if ($upload['sucesso']) {
            $logotipo_path = $upload['caminho'];
        }
    }
    
    global $pdo;
    $stmt = $pdo->prepare("UPDATE configuracoes SET titulo_site = :titulo, texto_rodape = :rodape, logotipo_path = :logo WHERE id = 1");
    $stmt->execute([
        'titulo' => $titulo_site,
        'rodape' => $texto_rodape,
        'logo' => $logotipo_path
    ]);
    
    $mensagem = "Configurações atualizadas com sucesso!";
    $tipo_mensagem = 'success';
    
    // Recarregar config
    $config = getConfiguracoes();
}

// Buscar todas as notícias para listagem
global $pdo;
$stmt = $pdo->query("SELECT n.*, c.nome as categoria_nome FROM noticias n LEFT JOIN categorias c ON n.id_categoria = c.id ORDER BY n.data_publicacao DESC");
$noticias = $stmt->fetchAll();

// Buscar enquetes
$stmt = $pdo->query("SELECT * FROM enquetes ORDER BY data_criacao DESC");
$enquetes = $stmt->fetchAll();

// Buscar publicidades
$stmt = $pdo->query("SELECT * FROM publicidade");
$publicidades = $stmt->fetchAll();

// Tab ativa
$tab_ativa = $_GET['tab'] ?? 'noticias';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - <?php echo htmlspecialchars($config['titulo_site']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-red-600"><?php echo htmlspecialchars($config['titulo_site']); ?> - Painel Admin</h1>
            <div class="flex items-center gap-4">
                <a href="index.php" target="_blank" class="text-gray-600 hover:text-red-600">Ver Site</a>
                <a href="?logout=1" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Sair</a>
            </div>
        </div>
    </header>

    <!-- Mensagens -->
    <?php if ($mensagem): ?>
    <div class="container mx-auto px-4 mt-4">
        <div class="<?php echo $tipo_mensagem === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?> border px-4 py-3 rounded">
            <?php echo htmlspecialchars($mensagem); ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Navegação de Tabs -->
    <div class="container mx-auto px-4 mt-6">
        <div class="bg-white rounded-lg shadow">
            <div class="flex border-b">
                <a href="?tab=noticias" class="px-6 py-3 <?php echo $tab_ativa === 'noticias' ? 'border-b-2 border-red-600 text-red-600 font-bold' : 'text-gray-600 hover:text-red-600'; ?>">📰 Notícias</a>
                <a href="?tab=enquetes" class="px-6 py-3 <?php echo $tab_ativa === 'enquetes' ? 'border-b-2 border-red-600 text-red-600 font-bold' : 'text-gray-600 hover:text-red-600'; ?>">📊 Enquetes</a>
                <a href="?tab=publicidade" class="px-6 py-3 <?php echo $tab_ativa === 'publicidade' ? 'border-b-2 border-red-600 text-red-600 font-bold' : 'text-gray-600 hover:text-red-600'; ?>">📢 Publicidade</a>
                <a href="?tab=configuracoes" class="px-6 py-3 <?php echo $tab_ativa === 'configuracoes' ? 'border-b-2 border-red-600 text-red-600 font-bold' : 'text-gray-600 hover:text-red-600'; ?>">⚙️ Configurações</a>
                <a href="?tab=atualizar" class="px-6 py-3 <?php echo $tab_ativa === 'atualizar' ? 'border-b-2 border-red-600 text-red-600 font-bold' : 'text-gray-600 hover:text-red-600'; ?>">🔄 Auto-Update</a>
            </div>

            <!-- Conteúdo das Tabs -->
            <div class="p-6">
                
                <!-- TAB: NOTÍCIAS -->
                <?php if ($tab_ativa === 'noticias'): ?>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Formulário de Notícia -->
                    <div>
                        <h3 class="text-xl font-bold text-gray-800 mb-4">Criar/Editar Notícia</h3>
                        <form method="POST" enctype="multipart/form-data" class="space-y-4">
                            <input type="hidden" name="id_noticia" value="">
                            
                            <div>
                                <label class="block text-gray-700 font-bold mb-2">Título *</label>
                                <input type="text" name="titulo" class="w-full border border-gray-300 px-4 py-2 rounded focus:outline-none focus:border-red-600" required>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 font-bold mb-2">Resumo *</label>
                                <textarea name="resumo" rows="3" class="w-full border border-gray-300 px-4 py-2 rounded focus:outline-none focus:border-red-600" required></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 font-bold mb-2">Conteúdo *</label>
                                <textarea name="conteudo" rows="8" class="w-full border border-gray-300 px-4 py-2 rounded focus:outline-none focus:border-red-600" required></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 font-bold mb-2">Categoria *</label>
                                <select name="id_categoria" class="w-full border border-gray-300 px-4 py-2 rounded focus:outline-none focus:border-red-600" required>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nome']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 font-bold mb-2">Imagem de Capa</label>
                                <input type="file" name="imagem_capa" accept="image/*" class="w-full border border-gray-300 px-4 py-2 rounded focus:outline-none focus:border-red-600">
                            </div>
                            
                            <button type="submit" name="salvar_noticia" class="w-full bg-red-600 text-white py-2 rounded hover:bg-red-700 font-bold">Salvar Notícia</button>
                        </form>
                    </div>
                    
                    <!-- Lista de Notícias -->
                    <div>
                        <h3 class="text-xl font-bold text-gray-800 mb-4">Todas as Notícias</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full bg-white border">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-4 py-2 text-left">Título</th>
                                        <th class="px-4 py-2 text-left">Categoria</th>
                                        <th class="px-4 py-2 text-left">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($noticias as $n): ?>
                                    <tr class="border-t">
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($n['titulo']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($n['categoria_nome'] ?? 'Geral'); ?></td>
                                        <td class="px-4 py-2">
                                            <a href="noticia.php?slug=<?php echo urlencode($n['slug']); ?>" target="_blank" class="text-blue-600 hover:underline mr-2">Ver</a>
                                            <a href="?excluir_noticia=<?php echo $n['id']; ?>" onclick="return confirm('Tem certeza?')" class="text-red-600 hover:underline">Excluir</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- TAB: ENQUETES -->
                <?php if ($tab_ativa === 'enquetes'): ?>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800 mb-4">Criar Nova Enquete</h3>
                        <form method="POST" class="space-y-4">
                            <div>
                                <label class="block text-gray-700 font-bold mb-2">Pergunta *</label>
                                <input type="text" name="pergunta" class="w-full border border-gray-300 px-4 py-2 rounded focus:outline-none focus:border-red-600" required>
                            </div>
                            
                            <div id="opcoes_container">
                                <label class="block text-gray-700 font-bold mb-2">Opções *</label>
                                <div class="space-y-2">
                                    <input type="text" name="opcoes[]" placeholder="Opção 1" class="w-full border border-gray-300 px-4 py-2 rounded" required>
                                    <input type="text" name="opcoes[]" placeholder="Opção 2" class="w-full border border-gray-300 px-4 py-2 rounded" required>
                                </div>
                            </div>
                            
                            <button type="button" onclick="adicionarOpcao()" class="text-blue-600 hover:underline text-sm">+ Adicionar mais opção</button>
                            
                            <button type="submit" name="salvar_enquete" class="w-full bg-red-600 text-white py-2 rounded hover:bg-red-700 font-bold">Criar Enquete</button>
                        </form>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-bold text-gray-800 mb-4">Enquetes Existentes</h3>
                        <div class="space-y-4">
                            <?php foreach ($enquetes as $enq): ?>
                            <div class="bg-gray-50 p-4 rounded border">
                                <div class="flex justify-between items-center mb-2">
                                    <h4 class="font-bold text-gray-800"><?php echo htmlspecialchars($enq['pergunta']); ?></h4>
                                    <span class="text-xs <?php echo $enq['status'] ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600'; ?> px-2 py-1 rounded">
                                        <?php echo $enq['status'] ? 'Ativa' : 'Inativa'; ?>
                                    </span>
                                </div>
                                <p class="text-gray-500 text-sm">Criada em: <?php echo formatarData($enq['data_criacao']); ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <script>
                function adicionarOpcao() {
                    const container = document.getElementById('opcoes_container');
                    const div = document.createElement('div');
                    div.className = 'space-y-2';
                    div.innerHTML = '<input type="text" name="opcoes[]" placeholder="Nova opção" class="w-full border border-gray-300 px-4 py-2 rounded">';
                    container.appendChild(div);
                }
                </script>
                <?php endif; ?>
                
                <!-- TAB: PUBLICIDADE -->
                <?php if ($tab_ativa === 'publicidade'): ?>
                <div class="space-y-8">
                    <?php foreach ($publicidades as $pub): ?>
                    <div class="bg-gray-50 p-6 rounded border">
                        <h3 class="text-xl font-bold text-gray-800 mb-4">
                            <?php echo $pub['posicao'] === 'lateral_esq' ? '📍 Publicidade - Lateral Esquerda' : '📍 Publicidade - Lateral Direita'; ?>
                        </h3>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="posicao" value="<?php echo htmlspecialchars($pub['posicao']); ?>">
                            
                            <div>
                                <label class="block text-gray-700 font-bold mb-2">Código HTML ou Imagem</label>
                                <textarea name="codigo_html_ou_imagem" rows="6" class="w-full border border-gray-300 px-4 py-2 rounded focus:outline-none focus:border-red-600"><?php echo htmlspecialchars($pub['codigo_html_ou_imagem']); ?></textarea>
                                <p class="text-gray-500 text-sm mt-2">Cole o código do banner ou script de publicidade aqui.</p>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" name="status" id="status_<?php echo $pub['id']; ?>" <?php echo $pub['status'] ? 'checked' : ''; ?> class="mr-2">
                                <label for="status_<?php echo $pub['id']; ?>" class="text-gray-700">Ativo</label>
                            </div>
                            
                            <button type="submit" name="salvar_publicidade" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 font-bold">Salvar Publicidade</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- TAB: CONFIGURAÇÕES -->
                <?php if ($tab_ativa === 'configuracoes'): ?>
                <div class="max-w-2xl">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Configurações Globais</h3>
                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <div>
                            <label class="block text-gray-700 font-bold mb-2">Título do Site</label>
                            <input type="text" name="titulo_site" value="<?php echo htmlspecialchars($config['titulo_site']); ?>" class="w-full border border-gray-300 px-4 py-2 rounded focus:outline-none focus:border-red-600">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 font-bold mb-2">Logotipo</label>
                            <?php if ($config['logotipo_path']): ?>
                                <img src="<?php echo htmlspecialchars($config['logotipo_path']); ?>" alt="Logotipo atual" class="h-16 mb-2">
                            <?php endif; ?>
                            <input type="file" name="logotipo" accept="image/*" class="w-full border border-gray-300 px-4 py-2 rounded focus:outline-none focus:border-red-600">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 font-bold mb-2">Texto do Rodapé</label>
                            <textarea name="texto_rodape" rows="3" class="w-full border border-gray-300 px-4 py-2 rounded focus:outline-none focus:border-red-600"><?php echo htmlspecialchars($config['texto_rodape']); ?></textarea>
                        </div>
                        
                        <button type="submit" name="salvar_configuracoes" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 font-bold">Salvar Configurações</button>
                    </form>
                </div>
                <?php endif; ?>
                
                <!-- TAB: ATUALIZAR NOTÍCIAS -->
                <?php if ($tab_ativa === 'atualizar'): ?>
                <div class="max-w-2xl">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Atualização Automática de Notícias</h3>
                    
                    <div class="bg-blue-50 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-6">
                        <p><strong>Como funciona:</strong> O sistema verifica automaticamente a cada hora se há novas notícias nos feeds RSS configurados e as adiciona ao banco de dados.</p>
                    </div>
                    
                    <div class="bg-gray-50 p-6 rounded border mb-6">
                        <h4 class="font-bold text-gray-800 mb-2">Última atualização:</h4>
                        <?php
                        global $pdo;
                        $stmt = $pdo->query("SELECT timestamp FROM ultima_atualizacao ORDER BY id DESC LIMIT 1");
                        $ultima = $stmt->fetch();
                        ?>
                        <p class="text-gray-600"><?php echo $ultima ? formatarData($ultima['timestamp']) : 'Nunca'; ?></p>
                    </div>
                    
                    <form method="POST">
                        <button type="submit" name="atualizar_noticias" class="bg-red-600 text-white px-8 py-3 rounded hover:bg-red-700 font-bold text-lg">
                            🔄 Atualizar Notícias Agora
                        </button>
                        <p class="text-gray-500 text-sm mt-2">Isso buscará notícias dos feeds RSS configurados (G1, GE, etc.)</p>
                    </form>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-16 py-8">
        <div class="container mx-auto px-4 text-center">
            <p><?php echo htmlspecialchars($config['texto_rodape']); ?></p>
        </div>
    </footer>
</body>
</html>
