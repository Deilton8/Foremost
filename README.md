# Foremost — website pronto para produção (foremost.co.mz)

Site estático bilingue (Português `/` e Inglês `/en/`), sem base de dados e sem cookies, com uma pequena **API em PHP** que envia o formulário de contacto por email para `info@foremost.co.mz`.

```
foremost-website/
├── public/                  ← É ISTO QUE SE PUBLICA (o conteúdo desta pasta, incluindo ficheiros ocultos)
│   ├── index.html           Página inicial (PT)          ├── en/index.html    Página inicial (EN)
│   ├── privacidade/         Política de Privacidade PT   ├── en/privacy/      Privacy Policy EN
│   ├── obrigado/            Agradecimento (envio sem JS) ├── 404.html         Página de erro
│   ├── api/                 contact.php (envio de email), config.php (configuração), .htaccess
│   ├── assets/              css, js, fontes, imagens, ícones (tudo alojado no próprio site)
│   ├── .htaccess            Apache/LiteSpeed: HTTPS, www→sem www, cache, compressão, segurança
│   ├── _headers, _redirects Netlify / Cloudflare Pages (ignorados noutros alojamentos)
│   └── robots.txt, sitemap.xml, manifest.webmanifest, favicon.ico, humans.txt, .well-known/security.txt
├── deploy/nginx.conf.example    Configuração de exemplo para Nginx (com PHP-FPM)
├── docs/AUDITORIA.md            O que foi encontrado, corrigido e medido
└── tools/                       atualizar-versoes.py e src/main.js (código legível do JavaScript)
```

## 1. Antes de publicar (obrigatório)

1. **Alojamento com PHP 7.2 ou superior** (qualquer cPanel serve). O formulário só funciona se o alojamento executar PHP.
2. **Confirmar o envio de email.** Por omissão a API usa a função `mail()` do PHP, com remetente e destinatário `info@foremost.co.mz` (`public/api/config.php`). Depois de publicar, envie uma mensagem de teste pelo formulário e confirme que chega a `info@foremost.co.mz` (veja também a pasta de spam).
   - **Para o email não ir para o spam**: no cPanel → *Email Deliverability*, confirme que o domínio tem **SPF** e **DKIM** válidos.
   - **Se não chegar, ou chegar sempre ao spam**: em `api/config.php` mude `'transport' => 'smtp'` e preencha o bloco `smtp` (servidor, porta 465 com `ssl` ou 587 com `tls`, utilizador `info@foremost.co.mz` e a palavra-passe da caixa). O SMTP autenticado é o método mais fiável.
   - Se o alojamento recusar o parâmetro `-f` do `mail()`, ponha `'envelope_from' => false`.
   - Os erros ficam no registo de erros do PHP (cPanel → *Erros*), com o prefixo `[foremost-contact]`.
3. **Domínio canónico = `foremost.co.mz` (sem www).** O `www` é redireccionado para o domínio sem www. Se preferir `www.foremost.co.mz`, substitua `https://foremost.co.mz` por `https://www.foremost.co.mz` em todos os `.html`, em `sitemap.xml`, `robots.txt`, `api/config.php` (`allowed_hosts` já aceita ambos) e nas duas regras de redireccionamento do `.htaccess`.
4. **HTTPS activo.** Active o certificado (AutoSSL/Let's Encrypt no cPanel) **antes** de enviar o `.htaccess`, que obriga a HTTPS.

### Como funciona a API de contacto (`public/api/contact.php`)
- `POST /api/contact.php` com os campos `Nome` e `Mensagem` (obrigatórios), `email` e/ou `Telefone` (pelo menos um), `Empresa`, `Servico`, `Categoria`. Responde em JSON (`success`, `message`, `error`) quando o pedido tem `Accept: application/json`; sem JavaScript redirecciona para `/obrigado/`.
- **Segurança**: só aceita pedidos vindos do próprio site (cabeçalhos `Origin`/`Referer`); valida e limpa todos os campos (sem injecção de cabeçalhos de email); campo-armadilha (`website`) e tempo mínimo de preenchimento contra robots; recusa mensagens com mais de 3 ligações; **máximo de 5 envios por IP em 15 minutos** (ajustável em `rate_limit`); limite de tamanho do pedido; `config.php` não é acessível pela web.
- Cada email inclui data, idioma da página, IP e navegador, e tem `Reply-To` com o email do visitante (basta responder).
- **Testar por linha de comandos**:
  `curl -i -X POST https://foremost.co.mz/api/contact.php -H "Accept: application/json" -d "Nome=Teste" -d "email=voce@exemplo.com" -d "Mensagem=Mensagem de teste da API"`

## 2. Como publicar

**cPanel / FTP (Apache ou LiteSpeed):** envie *o conteúdo* de `public/` para `public_html/` (no Gestor de Ficheiros active «Mostrar ficheiros ocultos» para ver o `.htaccess`). DNS: registo **A** do domínio (`foremost.co.mz`) para o IP do alojamento e **CNAME** `www` → `foremost.co.mz`.
> Se aparecer «Erro 500» depois de enviar o `.htaccess`, abra-o e apague a linha `Options -Indexes` (alguns alojamentos não a permitem); se persistir, peça ao suporte para activar `mod_rewrite` e `mod_headers`.

**Nginx:** use `deploy/nginx.conf.example` (inclui HTTPS, redireccionamentos, cabeçalhos, cache e PHP-FPM para a API; ajuste o socket do PHP).

**Netlify / Cloudflare Pages:** servem o site (*publish directory* = `public`, com `_headers`), mas **não executam PHP**: o formulário não funcionaria. Para estes serviços seria preciso trocar a API por uma função serverless; fale connosco se for esse o caso.

## 3. Depois de publicar — verificação e Google

- [ ] `http://foremost.co.mz`, `http://www.foremost.co.mz` e `https://www.foremost.co.mz` terminam em `https://foremost.co.mz/`
- [ ] `/en/`, `/privacidade/`, `/en/privacy/`, `/sitemap.xml` e `/robots.txt` abrem; um endereço inexistente mostra a página 404
- [ ] Formulário: envie um teste (PT e EN) e confirme que chega a `info@foremost.co.mz`
- [ ] **Google Search Console** (search.google.com/search-console): a etiqueta de verificação do Google **já está no `<head>`** de todas as páginas. Adicione a propriedade «Prefixo do URL» `https://foremost.co.mz/` (exactamente assim, sem www) → *Verificar* (método «Etiqueta HTML») → *Sitemaps* → envie `https://foremost.co.mz/sitemap.xml` → *Inspeção de URL* de `/` e `/en/` → «Pedir indexação». **Não apague a etiqueta `google-site-verification`**: deixaria o site novamente por verificar.
- [ ] **Bing Webmaster Tools**: importe o site a partir do Search Console
- [ ] **Google Business Profile**: crie/reclame a ficha da Foremost em Maputo com o **mesmo** nome, morada e telefones do site
- [ ] Teste em: pagespeed.web.dev · search.google.com/test/rich-results · securityheaders.com
- [ ] Quando existirem perfis sociais (LinkedIn, Facebook…), acrescente-os a `"sameAs": [...]` no JSON-LD de `index.html` e `en/index.html`

### Palavras-chave (SEO)
O Google ignora a etiqueta `meta keywords`; o que conta é o texto visível. As expressões abaixo estão no título, descrição, texto de abertura, títulos, categorias, `alt` das imagens e dados estruturados:

| Português | English |
|---|---|
| aprovisionamento Moçambique · empresa de aprovisionamento Maputo | procurement Mozambique · procurement company Maputo |
| fornecimento industrial · soluções industriais · material eléctrico | industrial supply · industrial solutions · electrical materials |
| fornecedor de EPI · equipamentos de protecção individual | PPE supplier · personal protective equipment |
| equipamentos informáticos / TIC · fornecedor Oil & Gas · mineração | ICT equipment · oil & gas supplies · mining supplies |
| frete aéreo, marítimo e rodoviário · importação de equipamentos | air, sea and road freight · import equipment |

## 4. Desempenho (PageSpeed)
O site foi optimizado para o PageSpeed Insights (a versão anterior marcava **58** em telemóvel). O que mudou e como se comporta:
- **Nada pesado corre antes de o visitante interagir.** O globo 3D (three.js) e as animações do GSAP/ScrollTrigger só são descarregados no primeiro movimento do rato, toque, tecla ou scroll (ou ao fim de 12 s, no caso do globo). Entretanto o globo aparece desenhado em 2D, sem pedidos de rede, quase idêntico ao 3D. O JavaScript do arranque passou de 200 KB para 10 KB.
- O texto de abertura (o maior elemento do ecrã) aparece de imediato; animações contínuas e decorativas só arrancam depois da primeira interação.
- Carga inicial: de 84 pedidos / 1,86 MB para **15 pedidos / 0,5 MB**; imagens em WebP com carregamento diferido, fontes e bibliotecas alojadas no próprio site, cache de 1 ano.
- **Para manter a pontuação**: não acrescente scripts de terceiros (chat, pixels, analytics) sem avaliar o impacto; use imagens WebP com `width`/`height`; mantenha a compressão (gzip/Brotli) e o HTTP/2 activos no alojamento.
- Os valores medidos estão em `docs/AUDITORIA.md`. O PageSpeed varia de execução para execução (±5 pontos); faça 2–3 testes.

## 5. Como editar
- **Textos:** estão em `public/index.html` (PT) e `public/en/index.html` (EN), na mesma ordem. Altere **os dois**. O texto de abertura (por baixo do título) começa por «Aprovisionamento e Fornecimento de Equipamentos…».
- **Contactos:** procure `+258`, `info@foremost.co.mz` e `wa.me/258827847870` (nas duas páginas, na política de privacidade, em `api/config.php` e no JSON-LD do `<head>`).
- **Cores e tipo de letra:** variáveis `:root` no início de `assets/css/styles.css`.
- **Imagens:** substitua em `assets/images/` (formato WebP). Se mudar uma imagem, **dê-lhe outro nome** (a cache é de 1 ano).
- **JavaScript:** `public/assets/js/main.js` está minificado. O código legível está em `tools/src/main.js`; depois de o editar, gere o ficheiro final com `npx terser tools/src/main.js -c passes=2 -m --toplevel -o public/assets/js/main.js`.
- **Depois de editar CSS ou JS:** `python3 tools/atualizar-versoes.py` (ou mude à mão o `?v=` nos HTML).
- **Não edite** o pequeno `<script>` no `<head>`: a sua assinatura está na regra CSP; se o alterar, o site deixa de o executar.
- **Google Analytics / Pixel (opcional, não incluído):** exigem alargar a CSP (`script-src`, `connect-src`, `img-src`) em `.htaccess` e `_headers`, aviso de cookies e actualização da Política de Privacidade.

## 6. Testar no computador
- Só o site: `cd public && python3 -m http.server 8000` e abra http://localhost:8000 (o `.htaccess` e a API não funcionam neste modo).
- Com a API: `php -S localhost:8000 -t public` (o envio de email só funciona se o PHP local tiver um serviço de email ou se usar `'transport' => 'smtp'`).

## 7. Notas e limitações
- A API foi testada com `mail()` (através de um `sendmail` de teste) e com SMTP (servidor de teste local, incluindo STARTTLS, SSL e autenticação), mas **não com o seu alojamento nem com a sua caixa de correio reais**: confirme com um envio de teste (secção 1).
- O `.htaccess` e as configurações de servidor **não foram testados num servidor real** (apenas validados e simulados localmente).
- O texto da Política de Privacidade é uma base fiel ao funcionamento do site; recomenda-se revisão por um jurista.
- Foram removidas a troca automática de Missão/Visão/Valores (inacessível e difícil de ler), a carga de bibliotecas por CDN e o serviço externo FormSubmit.
- `Strict-Transport-Security` está com 1 ano e sem `includeSubDomains`; só active esta opção se **todos** os subdomínios (ex.: webmail) tiverem HTTPS.
