# Foremost — Aprovisionamento & Fornecimento

Website estático da Foremost, Lda., organizado por tipo de ficheiro (Type-Based Structure).

## Estrutura

```text
foremost-website/
├── index.html
├── robots.txt
├── sitemap.xml
├── .htaccess
├── README.md
└── assets/
    ├── css/
    │   └── styles.css
    ├── js/
    │   └── main.js
    ├── images/
    │   └── (imagens e logótipos extraídos do HTML original)
    └── icons/
        ├── favicon.ico
        └── apple-touch-icon.png
```

## Tecnologias e dependências

- HTML5
- CSS3
- JavaScript
- Three.js r128 (CDN)
- GSAP 3.12.5 e ScrollTrigger (CDN)
- Google Fonts — Plus Jakarta Sans

As bibliotecas externas continuam a ser carregadas por CDN. É necessária ligação à Internet para essas dependências e para a fonte externa.

## Executar localmente

Pode abrir `index.html` directamente num navegador. Para um servidor local, por exemplo:

```bash
python -m http.server 8000
```

Depois aceda a `http://localhost:8000`.

## Publicação

1. Envie o conteúdo desta pasta para a raiz pública do alojamento (por exemplo, `public_html/`).
2. Confirme que o servidor utiliza Apache e permite ficheiros `.htaccess`; caso contrário, adapte as regras ao servidor utilizado.
3. Active HTTPS e confirme que o certificado SSL está válido.
4. **SEO — obrigatório antes da publicação:** substitua `https://www.seudominio.co.mz/` em `sitemap.xml` pelo domínio canónico real. Depois, retire o comentário da linha `Sitemap:` em `robots.txt` e actualize-a com o mesmo domínio.
5. Confirme que os formulários, ligações WhatsApp, contactos e integrações externas funcionam no domínio de produção.

## Observações

- O HTML original foi separado em `index.html`, `assets/css/styles.css` e `assets/js/main.js`.
- As imagens incorporadas em Base64 foram extraídas para `assets/images/`, reduzindo o peso do HTML e facilitando a manutenção.
- O favicon foi gerado a partir do logótipo existente no ficheiro original.
- O `.htaccess` inclui cabeçalhos de segurança, compressão e cache. A regra de HTTPS deve ser mantida apenas se o alojamento tiver SSL activo.
- O domínio público da Foremost não foi fornecido; por isso, o sitemap contém um domínio de substituição que deve ser actualizado antes de submeter aos motores de pesquisa.
