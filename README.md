# Foremost — website pronto para produção (foremost.co.mz)

Site estático bilingue (Português `/` e Inglês `/en/`), sem base de dados e sem cookies.

```
foremost-website/
├── public/                 ← É ISTO QUE SE PUBLICA (conteúdo desta pasta, incluindo ficheiros ocultos)
│   ├── index.html          Página inicial (PT)         ├── en/index.html   Página inicial (EN)
│   ├── privacidade/        Política de Privacidade PT  ├── en/privacy/     Privacy Policy EN
│   ├── obrigado/           Agradecimento (formulário)  ├── 404.html        Página de erro
│   ├── assets/             css, js, fontes, imagens, ícones (tudo alojado no próprio site)
│   ├── .htaccess           Apache/LiteSpeed: HTTPS, www→sem www, cache, compressão, segurança
│   ├── _headers, _redirects  Netlify / Cloudflare Pages (ignorados noutros alojamentos)
│   └── robots.txt, sitemap.xml, manifest.webmanifest, favicon.ico, humans.txt, .well-known/security.txt
├── deploy/nginx.conf.example   Configuração de exemplo para Nginx
├── docs/AUDITORIA.md           O que foi encontrado, corrigido e medido
└── tools/atualizar-versoes.py  Actualiza o ?v= do CSS/JS depois de os editar
```

## 1. Antes de publicar (obrigatório)

1. **Activar o formulário de contacto.** O formulário usa o serviço gratuito FormSubmit. No **primeiro** envio, o FormSubmit manda um email de confirmação para `info@foremost.co.mz`; é preciso clicar no link desse email. Enquanto isso não for feito, as mensagens **não chegam**. Faça um envio de teste logo após publicar.
2. **Domínio canónico = `foremost.co.mz` (sem www).** O `www` é redireccionado para o domínio sem www. Se preferir `www.foremost.co.mz`, substitua `https://foremost.co.mz` por `https://www.foremost.co.mz` em todos os ficheiros `.html`, em `sitemap.xml`, `robots.txt` e nas duas regras de redireccionamento do `.htaccess`.
3. **HTTPS activo.** Active o certificado (AutoSSL/Let's Encrypt no cPanel) **antes** de enviar o `.htaccess`, que obriga a HTTPS.

## 2. Como publicar

**cPanel / FTP (Apache ou LiteSpeed):** envie *o conteúdo* de `public/` para `public_html/` (no Gestor de Ficheiros active «Mostrar ficheiros ocultos» para ver o `.htaccess`). DNS: registo **A** do domínio (`foremost.co.mz`) para o IP do alojamento e **CNAME** `www` → `foremost.co.mz`.
> Se aparecer «Erro 500» depois de enviar o `.htaccess`, abra-o e apague a linha `Options -Indexes` (alguns alojamentos não a permitem); se persistir, peça ao suporte para activar `mod_rewrite` e `mod_headers`.

**Netlify / Cloudflare Pages:** ligue o repositório ou arraste a pasta; *publish directory* = `public`. O ficheiro `_headers` aplica a segurança e a cache. O redireccionamento `www` → sem www faz-se em *Domains* (Netlify) ou em *Redirect Rules* (Cloudflare).

**Nginx:** use `deploy/nginx.conf.example` (inclui HTTPS, redireccionamentos, cabeçalhos e cache).

## 3. Depois de publicar — verificação e Google

- [ ] `http://foremost.co.mz`, `http://www.foremost.co.mz` e `https://www.foremost.co.mz` terminam em `https://foremost.co.mz/`
- [ ] `/en/`, `/privacidade/`, `/en/privacy/`, `/sitemap.xml` e `/robots.txt` abrem; um endereço inexistente mostra a página 404
- [ ] Formulário: envie um teste e confirme que chega a `info@foremost.co.mz`
- [ ] **Google Search Console** (search.google.com/search-console): a etiqueta de verificação do Google **já está no `<head>`** de todas as páginas. Adicione a propriedade «Prefixo do URL» `https://foremost.co.mz/` (exactamente assim, sem www) → *Verificar* (método «Etiqueta HTML») → *Sitemaps* → envie `https://foremost.co.mz/sitemap.xml` → *Inspeção de URL* de `/` e `/en/` → «Pedir indexação». **Não apague a etiqueta `google-site-verification`**: deixaria o site novamente por verificar. (Alternativa: propriedade «Domínio» com registo TXT no DNS, que cobre também o `www` e subdomínios.)
- [ ] **Bing Webmaster Tools**: importe o site a partir do Search Console
- [ ] **Google Business Profile**: crie/reclame a ficha da Foremost em Maputo com o **mesmo** nome, morada e telefones do site (é o que mais ajuda nas pesquisas locais)
- [ ] Teste em: pagespeed.web.dev · search.google.com/test/rich-results · securityheaders.com
- [ ] Quando existirem perfis sociais (LinkedIn, Facebook…), acrescente-os a `"sameAs": [...]` no JSON-LD de `index.html` e `en/index.html`

### Palavras-chave (SEO)
O Google ignora a etiqueta `meta keywords` (foi incluída por completo, mas não conta); o que conta é o texto visível. As expressões abaixo estão no título, descrição, texto de abertura, títulos de secção, categorias, `alt` das imagens e dados estruturados:

| Português | English |
|---|---|
| aprovisionamento Moçambique · empresa de aprovisionamento Maputo | procurement Mozambique · procurement company Maputo |
| fornecimento industrial · soluções industriais · material eléctrico | industrial supply · industrial solutions · electrical materials |
| fornecedor de EPI · equipamentos de protecção individual | PPE supplier · personal protective equipment |
| equipamentos informáticos / TIC · fornecedor Oil & Gas · mineração | ICT equipment · oil & gas supplies · mining supplies |
| frete aéreo, marítimo e rodoviário · importação de equipamentos | air, sea and road freight · import equipment |

Para subir nos resultados: ficha do Google Business Profile, ligações (links) de parceiros e directórios, e publicar conteúdo novo (casos de sucesso, notícias).

## 4. Como editar

- **Textos:** estão em `public/index.html` (PT) e `public/en/index.html` (EN), na mesma ordem. Altere **os dois**.
- **Contactos:** procure `+258`, `info@foremost.co.mz` e `wa.me/258827847870` (nas duas páginas, na política de privacidade e no JSON-LD do `<head>`).
- **Cores e tipo de letra:** variáveis `:root` no início de `assets/css/styles.css`.
- **Imagens:** substitua em `assets/images/` (formato WebP). Se mudar uma imagem, **dê-lhe outro nome** (a cache é de 1 ano).
- **Depois de editar CSS ou JS:** `python3 tools/atualizar-versoes.py` (ou mude à mão o `?v=` nos HTML).
- **Não edite** o pequeno `<script>` no `<head>`: a sua assinatura está na regra CSP; se o alterar, o site deixa de o executar.
- **Google Analytics / Pixel (opcional, não incluído):** exigem alargar a CSP (`script-src`, `connect-src`, `img-src`) em `.htaccess` e `_headers`, aviso de cookies e actualização da Política de Privacidade.

## 5. Testar no computador
`cd public && python3 -m http.server 8000` e abra http://localhost:8000 (o `.htaccess` não é aplicado neste modo).

## 6. Notas e limitações
- O `.htaccess` e as configurações de servidor **não foram testados num servidor real** (apenas validados e simulados localmente); confirme com a lista da secção 3.
- O texto da Política de Privacidade é uma base fiel ao funcionamento do site; recomenda-se revisão por um jurista.
- Foram removidas a troca automática de Missão/Visão/Valores (inacessível e difícil de ler) e a carga de bibliotecas por CDN.
- `Strict-Transport-Security` está com 1 ano e sem `includeSubDomains`; só active esta opção se **todos** os subdomínios (ex.: webmail) tiverem HTTPS.
