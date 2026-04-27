# Fênix Portal - Portal de Notícias em PHP Puro

Portal de notícias completo, dinâmico e funcional desenvolvido 100% em PHP 8.x Vanilla com SQLite3.

## 🚀 Características

- **100% PHP Puro** - Sem frameworks (Laravel, Slim, etc.)
- **SQLite3** - Banco de dados leve via PDO
- **SEO Otimizado** - Meta tags dinâmicas, Schema.org, URLs amigáveis
- **Responsivo** - Tailwind CSS via CDN
- **Auto-Update** - Atualização automática de notícias via RSS (1 hora)
- **Painel Admin** - CRUD completo, enquetes, publicidade, configurações

## 📁 Estrutura de Arquivos

```
fenix-portal/
├── admin.php           # Painel administrativo consolidado
├── index.php           # Página principal (Grid de notícias)
├── noticia.php         # Página individual de notícia (SEO)
├── busca.php           # Página de busca
├── includes/
│   ├── db.php          # Conexão e setup do banco SQLite
│   └── functions.php   # Funções auxiliares, SEO, automação RSS
├── data/
│   └── fenix.db        # Banco de dados SQLite (criado automaticamente)
└── uploads/            # Imagens enviadas (logotipo, capas)
```

## 🗄️ Banco de Dados (SQLite)

Tabelas criadas automaticamente:

- `categorias` - Categorias editoriais
- `noticias` - Notícias com slug para SEO
- `enquetes` - Enquetes ativas/inativas
- `enquetes_opcoes` - Opções de votos
- `configuracoes` - Configurações globais do site
- `publicidade` - Banners laterais
- `ultima_atualizacao` - Controle do auto-update

### Categorias Incluídas

1. Gerais
2. Nacionais e Internacionais
3. Esportes (foco em Futebol)
4. Política
5. Gospel
6. Guerra / Atualidades

## 🔧 Instalação

1. **Clone ou copie os arquivos** para seu servidor web

2. **Permissões de diretório:**
   ```bash
   chmod 755 data/
   chmod 755 uploads/
   ```

3. **Acesse o site:**
   - Front-end: `http://seudominio.com/index.php`
   - Admin: `http://seudominio.com/admin.php`

4. **Login Admin (Demo):**
   - Usuário: `admin`
   - Senha: `admin123`

## ⚙️ Funcionalidades

### Front-End
- ✅ Grid moderno com destaque principal
- ✅ Menu dinâmico de categorias
- ✅ Barra de busca
- ✅ Sidebars para publicidade
- ✅ Enquetes interativas
- ✅ Totalmente responsivo (mobile-first)

### SEO de Elite
- ✅ URLs amigáveis (`/noticia/slug-da-noticia`)
- ✅ Meta tags dinâmicas (title, description)
- ✅ Open Graph (Facebook/WhatsApp)
- ✅ Twitter Cards
- ✅ Schema.org JSON-LD (NewsArticle)
- ✅ Canonical URLs

### Painel Administrativo
- ✅ **Notícias:** CRUD completo com upload de imagem
- ✅ **Enquetes:** Criar perguntas com múltiplas opções
- ✅ **Publicidade:** Gerenciar banners laterais (HTML/Scripts)
- ✅ **Configurações:** Logotipo, título do site, rodapé
- ✅ **Auto-Update:** Botão para atualizar notícias via RSS

### Automação
- ✅ **Auto-Update (1 hora):** Verifica automaticamente feeds RSS
- ✅ **Atualização Manual:** Botão no admin para forçar atualização
- ✅ **Feeds configurados:** G1, GE (esportes), Política

## 🎨 Tecnologias Utilizadas

| Tecnologia | Uso |
|------------|-----|
| PHP 8.x | Backend (Vanilla) |
| SQLite3 | Banco de dados |
| PDO | Acesso ao banco |
| Tailwind CSS | Estilização (via CDN) |
| HTML5/CSS3 | Estrutura e Grid |
| JavaScript Vanilla | Interatividade |

## 📊 Recursos de SEO Implementados

### Meta Tags Dinâmicas
```html
<title>Título da Notícia | Fênix Portal</title>
<meta name="description" content="Resumo da notícia...">
<meta property="og:title" content="Título da Notícia">
<meta property="og:image" content="imagem.jpg">
<meta name="twitter:card" content="summary_large_image">
```

### Schema.org JSON-LD
```json
{
  "@context": "https://schema.org",
  "@type": "NewsArticle",
  "headline": "Título da Notícia",
  "datePublished": "2024-01-01",
  "publisher": {...}
}
```

## 🔐 Segurança

- Sessões PHP para autenticação admin
- Prepared statements (PDO) contra SQL Injection
- htmlspecialchars() contra XSS
- Validação de upload de imagens
- Foreign keys habilitadas no SQLite

## 📝 Uso do Painel Admin

### Criar Notícia
1. Acesse `admin.php` e faça login
2. Vá na aba **Notícias**
3. Preencha título, resumo, conteúdo
4. Selecione a categoria
5. Upload opcional de imagem de capa
6. Clique em "Salvar Notícia"

### Criar Enquete
1. Vá na aba **Enquetes**
2. Digite a pergunta
3. Adicione as opções (mínimo 2)
4. Clique em "Criar Enquete"

### Gerenciar Publicidade
1. Vá na aba **Publicidade**
2. Cole o código HTML/Script do banner
3. Ative/desative conforme necessário
4. Salve as alterações

### Atualizar Notícias
1. Vá na aba **Auto-Update**
2. Clique em "Atualizar Notícias Agora"
3. O sistema buscará feeds RSS externos

## 🌐 URLs Amigáveis

O sistema gera slugs automáticos:
- Título: "Flamengo vence o clássico"
- URL: `noticia.php?slug=flamengo-vence-o-classico`

## 🔄 Sistema de Auto-Update

O sistema verifica a cada hora se há novas notícias:
1. Verifica timestamp da última atualização
2. Se > 60 minutos, busca feeds RSS
3. Processa e insere notícias no banco
4. Atualiza timestamp

Feeds RSS configurados:
- G1 (Gerais)
- GE (Esportes)
- G1 Política

## 📱 Responsividade

- Mobile-first com Tailwind CSS
- Menu hamburguer para mobile
- Grid adaptativo (1-4 colunas)
- Sidebars ocultas em mobile

## ⚠️ Notas Importantes

1. **Produção:** Altere as credenciais admin em `admin.php`
2. **HTTPS:** Configure SSL para produção
3. **Backups:** Faça backup regular do arquivo `data/fenix.db`
4. **Logs:** Monitore erros do PHP no servidor

## 📄 Licença

Projeto desenvolvido para fins educacionais e comerciais.

---

**Fênix Portal** © 2024 - Todos os direitos reservados.
