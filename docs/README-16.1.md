# Desafio 16.1 - O tema Noite Assombrada

## Resumo

Tema filho do Luma (`Webjump/halloween`) com identidade visual completa de Halloween, aplicado a toda a loja e com banner e rodapé configuráveis pelo Admin:

- **Herança limpa do Luma**: herda de `Magento/luma` e altera só a camada de apresentação; **nenhum arquivo em `vendor/` ou no tema Luma foi modificado**;
- **Direção de arte gótica vitoriana**: paleta em _Preto Meia-Noite_, _Cinza Carvão_, _Branco Osso_, _Laranja Abóbora_ e _Vermelho Sangue Seco_, evitando o laranja neon e o roxo genérico;
- **Tipografia local**: **Cormorant Garamond** (`.woff2`) carregada com `@font-face` + `@{baseDir}`, sem CDN externa;
- **Arquitetura LESS padronizada**: `_theme.less` só com variáveis da UI Library; `_extend.less` como índice de parciais por domínio em `extend/`;
- **Abrangência total**: header, navegação, breadcrumbs, botões, links, PLP, PDP e footer;
- **Configurável pelo Admin**: banner da home (imagem, texto, botão e seção inteira) e imagem do rodapé, com destino do botão escolhido em uma árvore de coleções;
- **Responsivo**: sem rolagem horizontal de 320px a 1920px, com breakpoints explícitos, e logo centralizada em todas as larguras;
- **Efeitos de Halloween em RequireJS**: cursor de vassoura, teia no canto superior esquerdo, morcegos de entrada e loader abóbora — com guarda de `prefers-reduced-motion`, de viewport e de páginas transacionais.

---

## Objetivo

Compreender e dominar a arquitetura de temas do Magento 2: criação de temas filhos, mecanismo de herança (`<parent>`), convenções de nomenclatura e diretórios, sobrescrita de variáveis globais da _Magento UI Library_ (`_theme.less`), extensão de estilos (`_extend.less`), importação de tipografia local com interpolação de caminho (`@{baseDir}`) e o ciclo de vida de compilação e deploy de arquivos estáticos.

---

## Arquitetura de Apresentação e Decisões de Design

A identidade visual foi concebida segundo os princípios de **Frontend Design**, criando uma experiência imersiva e memorável para o e-commerce:

```text
                                Magento/blank
                                      │
                                Magento/luma
                                      │ (parent)
                          Webjump/halloween (Tema Filho)
                     ┌────────────────┴────────────────┐
                     ▼                                 ▼
             web/css/source/_theme.less        web/css/source/_extend.less
          (Variáveis da UI Library)             (Regras CSS e @font-face)
                     │                                 │
                     └────────────────┬────────────────┘
                                      ▼
                        Magento LESS Preprocessor
                                      │
                                      ▼
                   pub/static/.../css/styles-m.css & styles-l.css
```

### Paleta de Cores e Atmosfera

| Token                   | Hex       | Aplicação                                                                   |
| ----------------------- | --------- | --------------------------------------------------------------------------- |
| **Midnight Black**      | `#0A0A0C` | Fundo principal da página (`@page__background-color`).                      |
| **Obsidian Charcoal**   | `#141416` | Superfícies, Header, Painel superior, Footer e modais.                      |
| **Deep Surface Border** | `#26262B` | Linhas divisórias, bordas de componentes e separadores.                     |
| **Bone White**          | `#D4D4D0` | Tipografia base e textos corridos (máxima legibilidade sem cansaço visual). |
| **Eerie Pumpkin**       | `#D84B20` | Acentos principais, links, preços, estados hover e destaques.               |
| **Dried Blood Red**     | `#5C0000` | Botões de ação primária (Call to Action).                                   |
| **Vampiric Crimson**    | `#7A0000` | Hover dos botões primários com sombra/brilho difuso em laranja.             |

---

## Estrutura de Arquivos

```text
src/app/design/frontend/Webjump/halloween/
├── media/preview.jpg                             # Preview do tema no Admin
├── registration.php                              # Registro no ComponentRegistrar
├── theme.xml                                     # Título, <parent> e preview
├── requirejs-config.js                           # Alias "halloween-fx" + deps do init
├── README.md                                     # Documentação interna do tema
├── Magento_Checkout/web/css/source/_extend.less  # Minicarrinho: superfície escura
├── Magento_Catalog/web/css/source/_extend.less   # Toolbar: campos escuros e modo ativo
├── Magento_Theme/layout/default.xml              # Logo, arte do rodapé e ordem do copyright
├── Webjump_Gustavo/layout/cms_index_index.xml   # Posiciona o hero e remove o bloco legado
└── web/
    ├── css/source/
    │   ├── _theme.less                           # Só variáveis da UI Library
    │   ├── _extend.less                          # Índice dos parciais de extend/
    │   └── extend/
    │       ├── _breadcrumbs.less                 # Breadcrumbs e títulos de widget
    │       ├── _buttons.less                     # Ações primária e secundária
    │       ├── _fonts.less                       # @font-face local (Cormorant Garamond)
    │       ├── _footer.less                      # Links, copyright, arte e newsletter
    │       ├── _halloween-fx.less                # Cursor, teia, loader e entrada
    │       ├── _halloween-hero.less              # Hero, variantes e breakpoints
    │       ├── _header.less                      # Header sticky e logo centralizada
    │       ├── _product-cards.less               # Cards da listagem
    │       ├── _product-page.less                # PDP: título, preço e abas
    │       └── _typography.less                  # Família tipográfica dos títulos
    ├── fonts/cormorant-garamond-bold.woff2       # Fonte local (WOFF2)
    ├── js/halloween-fx/
    │   ├── init.js                               # Guards e orquestração dos efeitos
    │   └── bats.js                               # Morcegos de entrada (~900ms)
    └── images/
        ├── cursor-broom.svg                      # Cursor de vassoura (repouso)
        ├── cursor-broom2.svg                     # Cursor de vassoura (clique)
        ├── footer-halloween.jpg                  # Arte da faixa do rodapé
        ├── halloween-banner.jpg                  # Arte do hero da home
        ├── loader-pumpkin.svg                    # Abóbora do loader
        ├── logo-halloween.png                    # Logo temática do header
        └── web-corner.svg                        # Teia do canto superior esquerdo

src/app/code/Webjump/Gustavo/
├── Model/Config/Source/CategoryCollection.php    # Árvore de coleções do destino do banner
├── ViewModel/Halloween.php                       # Lê a config, resolve URLs e decide o que renderizar
├── etc/
│   ├── config.xml                                # Valores padrão das 3 seções do tema
│   └── adminhtml/system.xml                      # Seções "Bloco Home", "Avaliações" e "Tema Halloween"
└── view/frontend/templates/
    ├── halloween_footer_bg.phtml                 # Faixa de imagem condicional do rodapé
    └── halloween_hero.phtml                      # Banner condicional

docs/README-16.1.md                                # Este documento
```

---

## Explicação de cada componente

- **`registration.php` / `theme.xml`**: registram `frontend/Webjump/halloween`, com título `Webjump Halloween`, `<parent>Magento/luma</parent>` e `media/preview.jpg` (800x600) para **Content > Design > Configuration**.
- **`_theme.less`**: contém **apenas** variáveis já existentes na biblioteca, o que mantém o tema herdável sem colisão de escopo.
- **`_extend.less` + `extend/`**: o blank declara `//@magento_import 'source/_extend.less';` em `styles-m/l.less`; o pré-processador o resolve pela cadeia de fallback. O arquivo virou um **índice** e cada parcial cuida de um domínio e dos **próprios breakpoints**.
- **`Magento_Checkout/web/css/source/_extend.less` e `Magento_Catalog/web/css/source/_extend.less`**: ajustes pontuais de componentes do core que não têm parciais próprios no tema. Parciais de módulo são compiladas dentro do mesmo `styles-m.css`, **depois** dos `_extend.less` globais, então vencem o Luma em caso de empate de especificidade.
- **`Magento_Theme/layout/default.xml`**: (1) troca `logo_file` por `images/logo-halloween.png`; (2) injeta `halloween.footer.bg` no `footer-container`, depois de `footer`, para a arte ocupar a largura total; (3) move o `copyright` de volta ao rodapé, abaixo da arte. Ordem resultante: `footer content` → `halloween-footer-bg` → `copyright`.
- **Módulo `Webjump_Gustavo`**: `system.xml`/`config.xml` (configuração), `ViewModel/Halloween.php` (lê a config, resolve URLs e decide o que renderizar) e os dois templates condicionais.
- **`cms_index_index.xml` do tema**: um layout do tema com o mesmo handle **substitui integralmente** o do módulo, por isso o bloco legado `home_block` não é mais renderizado na home.

### Efeitos de Halloween

- **`requirejs-config.js`**: declara `paths: { 'halloween-fx': 'js/halloween-fx' }` e `deps: ['halloween-fx/init']`. O `deps` é o que faz o efeito carregar **sem `data-mage-init`** em nenhum template: o RequireJS trata `init.js` como dependência de bootstrap. Sem o `paths`, o RequireJS procuraria `halloween-fx.js` na raiz do base URL, receberia `404 text/plain` e derrubaria o módulo inteiro.
- **`js/halloween-fx/init.js`**: único entry point. Avalia três guardas — `desktop` (`min-width: 768px`), `motion` (`prefers-reduced-motion`) e `transactional` (`/checkout` e `/customer`) — e repassa o resultado para `bats.js`. Não há `webs.js` nem `spider.js`: a teia é CSS e não entra aqui, e a aranha central foi removida. Espera o `DOMContentLoaded` se o documento ainda estiver carregando.
- **`js/halloween-fx/bats.js`**: 5 morcegos SVG cruzam a tela em ~900ms e são removidos do DOM. Nenhum listener, nenhum `capture`, nenhum `pointer-events`: os morcegos não podem interceptar clique nem bloquear hover. É por isso que eles são o único efeito liberado no mobile.
- **`_halloween-fx.less`**: cursor (repouso e `:active`), teia no `body::before`, loader e animações de entrada. Tudo o que é cursor e teia fica dentro de `min-width: @screen__m` (768px). O cursor declara `width/height` em pixels porque o navegador recusa cursor acima de 128px e o trace do potrace vem em 1024pt. O `body::after` faz preload do cursor ativo para o primeiro clique não piscar; o `body::before` faz a teia — não colidem porque cada um ocupa um pseudo-elemento.
- **Camadas (`z-index`)**: teia `250`, acima do header sticky (`200`) e abaixo dos painéis interativos de submenu e minicarrinho (`899+`), que precisam desenhar por cima. O `body::before` herda `pointer-events: none`, então não existe faixa invisível roubando clique do header.
- **Logo centralizada**: `extend/_header.less` centraliza com `left: 50%` + `translateX(-50%)`, e não com `float: none` nem `position: absolute` — ver _Decisão 7_.
- **Assets em `web/images/`**: `web-corner.svg` (141KB) e os cursores são arquivos estáticos, não SVG inline no JS. O `viewBox` foi ajustado ao bounding box real da arte, medido no canvas — o do Illustrator é o canvas do documento, e a arte ocupava só 10,6% dele.

---

## Variáveis da Biblioteca Sobrescritas e Justificativa

Em conformidade com a convenção do Magento 2 e da _Magento UI Library_, todas as variáveis declaradas no `_theme.less` mapeiam diretamente elementos do design system do core:

| Variável                                 | Valor Definido                                | Por que foi sobrescrita?                                                                                                                                             |
| ---------------------------------------- | --------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `@page__background-color`                | `#0A0A0C`                                     | Substitui o fundo branco `#ffffff` padrão do Luma pelo preto meia-noite, definindo o tom escuro de toda a loja.                                                      |
| `@panel__background-color`               | `#141416`                                     | Garante que o painel superior de boas-vindas/login do cabeçalho harmonize com o fundo escuro, com contraste refinado.                                                |
| `@sidebar__background-color`             | `#141416`                                     | Alinha a cor dos blocos laterais de carrinho, checkout e conta com a superfície escura.                                                                              |
| `@border-color__base`                    | `#26262B`                                     | Troca as bordas cinza claro do Luma por um grafite escuro sutil, evitando linhas brancas ou estouradas no tema noturno.                                              |
| `@primary__color`                        | `#D84B20`                                     | Substitui a cor de destaque do Luma pelo laranja abóbora de Halloween, alcançando ícones de navegação e acentos globais.                                             |
| `@primary__color__dark`                  | `#5C0000`                                     | Tom da escala da cor primária fixado na paleta do tema, em vez do valor derivado automaticamente.                                                                    |
| `@primary__color__darker`                | `#141416`                                     | Tom mais escuro da escala primária, alinhado à superfície escura.                                                                                                    |
| `@primary__color__lighter`               | `#8E8E93`                                     | Tom claro da escala primária fixado no cinza médio da paleta.                                                                                                        |
| `@primary__color__light`                 | `#D4D4D0`                                     | Tom claro da escala primária, igual ao branco osso do texto.                                                                                                         |
| `@secondary__color`                      | `#5C0000`                                     | Define a cor de apoio profunda (vermelho sangue) utilizada como base dos botões de ação.                                                                             |
| `@text__color`                           | `#D4D4D0`                                     | Define o tom "Branco Osso" para o corpo do texto em toda a loja. Garante contraste WCAG AAA contra o fundo `#0A0A0C`, mantendo alta legibilidade sem cansar a vista. |
| `@text__color__intense`                  | `#FFFFFF`                                     | Branco puro para textos com ênfase alta e labels críticas.                                                                                                           |
| `@text__color__muted`                    | `#8E8E93`                                     | Cinza médio para textos secundários, como SKUs, breadcrumbs e notas de rodapé.                                                                                       |
| `@heading__color__base`                  | `#F0EDE6`                                     | Define cor clara e uniforme para todos os cabeçalhos (`h1` a `h6`), mantendo harmonia com o texto base.                                                              |
| `@heading__font-family__base`            | `'Cormorant Garamond', Georgia, serif`        | Associa a família tipográfica gótica vitoriana a todos os títulos do sistema de forma declarativa.                                                                   |
| `@heading__font-weight__base`            | `700`                                         | Assegura que os títulos sejam renderizados no peso negrito nativo do arquivo de fonte carregado.                                                                     |
| `@h1__font-color` a `@h6__font-color`    | `@heading__color__base`                       | Garante que nenhuma regra específica de título do Luma reverta as cores de cabeçalho.                                                                                |
| `@link__color`                           | `#D84B20`                                     | Links em toda a loja assumem o tom laranja abóbora.                                                                                                                  |
| `@link__hover__color`                    | `#FF7043`                                     | Efeito hover com tom de chama acesa, indicando interatividade claramente ao usuário.                                                                                 |
| `@link__visited__color`                  | `#D84B20`                                     | Links visitados mantêm o laranja abóbora, sem voltar à cor padrão do Luma.                                                                                           |
| `@link__active__color`                   | `#FF5722`                                     | Estado ativo (clique) do link em laranja mais vivo.                                                                                                                  |
| `@button__color`                         | `#D4D4D0`                                     | Texto legível para botões secundários.                                                                                                                               |
| `@button__background`                    | `#1E1E24`                                     | Botões secundários ganham superfície cinza carvão metálica em vez do cinza claro do Luma.                                                                            |
| `@button__border`                        | `1px solid #3A3A42`                           | Borda refinada para botões secundários.                                                                                                                              |
| `@button__hover__color`                  | `#FFFFFF`                                     | Texto branco no hover dos botões secundários.                                                                                                                        |
| `@button__hover__background`             | `#2A2A33`                                     | Superfície um pouco mais clara no hover, como feedback de interação.                                                                                                 |
| `@button__hover__border`                 | `1px solid #D84B20`                           | Borda laranja no hover dos botões secundários.                                                                                                                       |
| `@button__active__background`            | `#141416`                                     | Fundo escuro ao clicar no botão secundário.                                                                                                                          |
| `@button__active__border`                | `1px solid #D84B20`                           | Borda laranja no estado ativo, coerente com o hover.                                                                                                                 |
| `@button-primary__color`                 | `#FFFFFF`                                     | Texto branco sobre o vermelho sangue, garantindo contraste no CTA.                                                                                                   |
| `@button-primary__background`            | `#5C0000`                                     | Botões de conversão ("Adicionar ao Carrinho", "Finalizar Compra") ganham o tom dramático de vermelho sangue seco.                                                    |
| `@button-primary__border`                | `1px solid #7A0000`                           | Borda do botão de ação primária em tom vermelho rubi.                                                                                                                |
| `@button-primary__hover__color`          | `#FFFFFF`                                     | Mantém o texto branco no hover do CTA.                                                                                                                               |
| `@button-primary__hover__background`     | `#7A0000`                                     | Clareamento controlado no hover do botão primário para feedback tátil de clique.                                                                                     |
| `@button-primary__hover__border`         | `1px solid #D84B20`                           | Borda laranja no hover do CTA, ligando o botão à cor de destaque do tema.                                                                                            |
| `@button-primary__active__background`    | `#4A0000`                                     | Vermelho ainda mais escuro ao pressionar o CTA.                                                                                                                      |
| `@button-primary__active__border`        | `1px solid #4A0000`                           | Borda acompanhando o fundo no estado ativo do CTA.                                                                                                                   |
| `@header__background-color`              | `#141416`                                     | Modifica o fundo do cabeçalho principal (`.page-header`) através da variável do módulo `Magento_Theme`.                                                              |
| `@header-panel__background-color`        | `#101012`                                     | Escurece o topo da página para criar hierarquia visual com o cabeçalho principal.                                                                                    |
| `@header-panel__text-color`              | `#D4D4D0`                                     | Texto claro no painel superior (boas-vindas/login) sobre o fundo escuro.                                                                                             |
| `@header-icons-color`                    | `#D4D4D0`                                     | Ícones de busca, menu mobile e conta passam a ser claros.                                                                                                            |
| `@header-icons-color-hover`              | `#D84B20`                                     | Efeito hover dos ícones de cabeçalho em laranja.                                                                                                                     |
| `@minicart-icons-color`                  | `#D4D4D0`                                     | Ícone do minicarrinho claro, como os demais ícones do cabeçalho.                                                                                                     |
| `@minicart-icons-color-hover`            | `#D84B20`                                     | Hover do ícone do minicarrinho em laranja.                                                                                                                           |
| `@navigation__background`                | `#141416`                                     | Fundo da barra de categorias principal (`.nav-sections`).                                                                                                            |
| `@navigation-level0-item__active__color` | `#D84B20`                                     | Categoria ativa na navegação destacada com a cor tema.                                                                                                               |
| `@footer__background-color`              | `#141416`                                     | Define a cor de fundo do rodapé através da variável da biblioteca.                                                                                                   |
| `@footer-links-color`                    | `#F0EDE6`                                     | Links do rodapé em branco osso. O cinza médio anterior (`#A0A09C`) ficava invisível sobre o fundo escuro, o principal problema de legibilidade relatado.             |
| `@footer-links-color-hover`              | `#FF7043`                                     | Hover dos links do rodapé em tom de chama acesa, sinalizando interatividade.                                                                                         |
| `@footer-links-color-current`            | `#FFFFFF`                                     | Títulos dos grupos de links (elementos `<strong>`) em branco puro, para separar a heading do restante da lista.                                                      |
| `@footer-links-separator-border-color`   | `#26262B`                                     | Divisórias entre os grupos de links discretas e compatíveis com o tema noturno.                                                                                      |
| `@copyright__background-color`           | `#0E0E10`                                     | Faixa inferior de direitos autorais no tom mais escuro do rodapé.                                                                                                    |
| `@form-element-input__background`        | `#141416`                                     | Campos de formulário (inputs de texto, busca, campos de endereço) ganham fundo escuro.                                                                               |
| `@form-element-input__border-color`      | `#3A3A42`                                     | Contorno nítido e discreto para os campos de input.                                                                                                                  |
| `@form-element-input__border`            | `1px solid @form-element-input__border-color` | Monta o contorno do campo a partir da cor definida acima.                                                                                                            |
| `@form-element-input__color`             | `#D4D4D0`                                     | Texto digitado nos campos com alta legibilidade em branco osso.                                                                                                      |
| `@form-element-input-placeholder__color` | `#6E6E73`                                     | Placeholder discreto, distinguível do texto digitado.                                                                                                                |
| `@price-color` / `@product-info-price`   | `#D84B20`                                     | Preços dos produtos na listagem (catálogo) e na página interna de produto em destaque laranja.                                                                       |
| `@product-price__muted__color`           | `#8E8E93`                                     | Preço secundário (por exemplo, o preço antigo) em cinza médio, sem competir com o preço em laranja.                                                                  |

---

## Configuração pelo Admin

A seção do módulo **`webjump_gustavo`** tem três grupos (`etc/adminhtml/system.xml`), todos em **Stores > Configuration > Webjump Gustavo**. O menu lateral do admin (**Webjump > Avaliações**) é separado e trata do CRUD de reviews.

### `halloween_theme` — rótulo no admin: "Tema Halloween"

| Chave                                                   | Tipo                   | Padrão                                                                  | Função                                                          |
| ------------------------------------------------------- | ---------------------- | ----------------------------------------------------------------------- | --------------------------------------------------------------- |
| `hero_enabled`                                          | Sim/Não                | `1`                                                                     | Liga/desliga o banner inteiro (sem container vazio).            |
| `hero_show_image` / `hero_image` / `hero_opacity`       | Sim/Não, imagem, texto | `1` / — / `0.85`                                                        | Arte de fundo; sem upload usa `halloween-banner.jpg`.           |
| `hero_show_text` / `hero_title` / `hero_subtitle`       | Sim/Não, texto         | `1` / `Noite Assombrada` / `A coleção mais aterrorizante do ano chegou` | Título e subtítulo.                                             |
| `hero_show_button` / `hero_button_text`                 | Sim/Não, texto         | `1` / `Ver Coleção`                                                     | CTA do banner.                                                  |
| `hero_button_collection` / `hero_button_link`           | Select, texto          | — / `/catalogsearch/result/?q=halloween`                                | Destino do CTA: **coleção → link manual → `/`**.                |
| `footer_show_image` / `footer_image` / `footer_opacity` | Sim/Não, imagem, texto | `1` / — / `0.35`                                                        | Faixa de arte do rodapé; sem upload usa `footer-halloween.jpg`. |

**Variantes do banner** (o template decide o que renderizar): padrão → `halloween-hero`; sem imagem → `--no-image` (gradiente sólido); sem texto e sem botão → `--image-only`; tudo desligado ou `hero_enabled = 0` → nada é emitido.

**Select de coleções** (`CategoryCollection`): usa a raiz da **store atual** (na store 1, ID `2`, path `1/2`), só categorias ativas, ordem alfabética, níveis indentados e a opção `-- Informar o link manualmente --`. A URL é resolvida por `Category::getCategoryUrl()`, respeitando rewrites por loja. O campo de link manual foi mantido para landing pages fora do catálogo.
### `general` — rótulo no admin: "Bloco Home"

| Chave             | Tipo  | Padrão                                                                                     | Função                  |
| ----------------- | ----- | ------------------------------------------------------------------------------------------ | ----------------------- |
| `title`           | Texto | `Webjump Gustavo - Home Block`                                                             | Título do bloco legado. |
| `welcome_message` | Texto | `Bloco renderizado na página inicial utilizando a arquitetura de ViewModels do Magento 2.` | Texto do bloco legado.  |

Esse bloco **não aparece na home**: o `cms_index_index.xml` do tema substitui integralmente o do módulo, então o template fica sem renderização. A seção foi mantida porque é o esqueleto do ViewModel exigido pelo enunciado.

---

## Decisões de Arquitetura

### 1. Parciais em `extend/`, e não direto em `source/`

Arquivos como `_buttons.less`, `_breadcrumbs.less` e `_typography.less` colocados em `web/css/source/` **sombreariam** os de mesmo nome do blank/Luma pela cadeia de fallback de temas. Isso substituiria, por acidente, estilos-base inteiros da UI Library. Dentro de `extend/`, o caminho é exclusivo do tema, e o `_extend.less` (único arquivo auto-carregado) apenas importa os parciais.

### 2. Media queries explícitas, sem `.media-width()`

O mixin `.media-width()` foi concebido para os `_module.less` dos módulos. O coletor do Luma reemite o mesmo conteúdo em vários grupos de dispositivo, então um bloco "celular" escrito no tema também aparecia dentro de `@media all and (max-width: 1023px), print` no `styles-l.css`, que carrega **depois** do `styles-m.css`. O resultado era um hero com altura e tipografia de celular até 1023px. Aninhar `.media-width()` também não compõe intervalos: o LESS resolve só o breakpoint mais específico e descarta o resto sem avisar.

Por isso, todas as faixas do tema usam media queries explícitas sobre as variáveis do Luma, que compilam uma única vez e no lugar certo:

```less
@media all and (min-width: @screen__m) and (max-width: (@screen__l - 1)) {
  ...;
}
@media all and (max-width: (@screen__m - 1)) {
  ...;
}
```

| Largura        | Hero  | Título do hero | Card da PLP       |
| -------------- | ----- | -------------- | ----------------- |
| até 767px      | 300px | 3.4rem         | 14px, sem moldura |
| 768px a 1023px | 420px | 4.4rem         | 18px, com moldura |
| 1024px ou mais | 520px | 5.6rem         | 18px, com moldura |

### 3. Efeitos em módulos RequireJS, e não em `data-mage-init`

Cada efeito é um módulo AMD próprio e o `requirejs-config.js` empurra `init.js` como dependência de bootstrap. O caminho alternativo — `data-mage-init` em um template — amarraria os efeitos ao layout, exigiria um template só para isso e, quando um arquivo falha, derruba a cadeia inteira. Com o `paths` correto, uma falha de rede em um SVG degrada um efeito sem derrubar os outros.

### 4. Quem decide o que roda fica em um lugar só

`init.js` é o único que lê viewport, `prefers-reduced-motion` e rota. Os módulos de efeito recebem `enabled`/`motion` já resolvidos e não repetem a checagem. Assim a regra "checkout e conta não recebem efeitos de tema" está escrita uma vez, e não em três arquivos.

O critério não é só "tela pequena". Os morcegos são uma entrada única de ~900ms, sem listener, e valem no mobile. A teia e o cursor são CSS puro e não têm custo de JS, mas ficam **desktop-only por decisão visual**: 320×254 px de arte no canto superior esquerdo cobre boa parte do menu hamburger e da busca em telas estreitas, o que atrapalha mais do que decora.

### 5. Efeito estático é CSS, não é JavaScript

A teia nasceu como `webs.js`: quatro nós no DOM, `getBoundingClientRect()` em cada um e um `pointermove` coalescido em `requestAnimationFrame` só para trocar `is-swaying`. Quando passou a ser única e fixa, nenhum desses mecanismos produzia efeito visual — era custo sem retorno. Virou `background-image` no `body::before`: zero nó no DOM, zero JS, e impossível de interferir no clique porque herda `pointer-events: none`.

### 6. Arte do Illustrator: `viewBox` medido, e em arquivo

O `web-corner.svg` veio com o `viewBox` do **canvas do documento** (`0 0 450 450`), não o do conteúdo. Medindo o alpha no canvas, a arte ocupava só 10,6% do canvas — o resto era vazio, então o `contain` encolhia o desenho e o hitbox ficava maior que a arte. O `viewBox` foi ajustado ao bounding box real.

E a arte virou arquivo em `web/images/` em vez de string no JS: 141KB de path data que seriam reenviados e reparseados a cada carregamento, sem nenhum ganho em troca.

### 7. Logo centralizada por `transform`, não por layout

Centralizar a logo floated no meio do header tem três caminhos, e dois deles quebram o header:

- **`float: none`** tornaria a logo um bloco de largura total, e a busca e o minicarrinho — que são floated — cairiam para a linha de baixo;
- **`position: absolute`** tiraria a logo do fluxo, e o clearfix de `.header.content` passaria a medir só as floats (~40px), deixando a logo de 127px vazar sobre o conteúdo;
- **`left: 50%` + `translateX(-50%)`** desloca só a pintura: a logo continua no fluxo, o header continua com uma linha só, e o espaço que ela deixa livre à esquerda é exatamente onde a teia do `body::before` mora.

O `margin-left` zerado na mesma regra não é cosmético: `left` de um float é somado à posição estática, que já inclui a margem. Abaixo de 768px o Luma põe 40px de `margin-left` no `.logo`, e sem zerar a logo parava 40px à direita do centro (ver _Problema 12_).

---

## Problemas Encontrados e Correções

### 1. Rolagem horizontal na página de coleção

- **Sintoma:** em 375px, `women/tops-women.html` media `scrollWidth = 386` contra `clientWidth = 375`.
- **Causa:** `.products-grid .product-item-info` declara `padding: 12px` e `border: 1px`, mas a cadeia de ancestrais do Magento não herda `box-sizing: border-box`. O card (`width: 169px` + 26px de padding e borda) ocupava 195px e ultrapassava a coluna. O `.halloween-hero--no-image` (`width: 100%` + `padding`) tinha o mesmo erro de cálculo.
- **Correção:** `box-sizing: border-box` nas duas regras. No celular, os cards também perdem a moldura (`border`, `background` e `box-shadow` do hover), que competia por espaço em duas colunas estreitas.

### 2. Botão de newsletter cortado entre 768px e 1023px

- **Sintoma:** o botão transbordava a viewport em ~5px nessa faixa.
- **Causa:** a partir de 768px o Luma aplica `.block.newsletter { max-width: 44% }` e `.form.subscribe > .actions { float: left; width: 1% }`. A coluna do rodapé fica estreita demais para o campo de 220px somado ao botão, e a célula flutuante de 1% não contribui para a largura do bloco.
- **Correção:** campo e botão são empilhados na faixa de tablet. Os seletores são prefixados com `.page-footer` porque o `styles-l.css` carrega depois do `styles-m.css` e, em empate de especificidade, o Luma venceria.

### 3. Estático obsoleto em modo developer

- **Sintoma:** as alterações no LESS não surtiam efeito, mesmo depois de limpar `var/view_preprocessed`.
- **Causa:** `pub/static` é um volume do Docker e, em modo `developer`, os arquivos são materializados por cópia. Remover apenas `var/view_preprocessed/...` não regenera o CSS, e `pub/static` continua servindo a versão anterior.
- **Correção:** remover os **dois** caminhos (ou rodar o deploy completo) e limpar o cache. Vale acessar a página com `curl` antes de medir, pois a primeira requisição é a que dispara a recompilação e pode devolver o CSS ainda incompleto.

```bash
bin/cli sh -c "rm -rf var/view_preprocessed/pub/static/frontend/Webjump/halloween pub/static/frontend/Webjump/halloween"
bin/magento cache:flush
```

### 4. Ordem dos elementos no layout (XSD)

- **Sintoma:** a página inteira caía com HTTP 500. A única pista no log era `main.ERROR: Element 'move': This element is not expected.`
- **Causa:** o `bodyType` (`framework/View/Layout/etc/body.xsd`) é uma `xs:sequence` cujos elementos seguem a ordem `attribute` → `block` → `referenceBlock` → `referenceContainer` → `container` → `move` → `uiComponent`. O `<move>` precisa ser filho direto de `<body>`, e um `<move>` fora dessa posição (por exemplo, dentro de um `<referenceContainer>`) viola o schema.
- **Correção:** no `Magento_Theme/layout/default.xml`, o `<move>` do `copyright` é filho direto de `<body>`, declarado depois dos `referenceBlock` e `referenceContainer`.

### 5. `config:delete` não existe nesta versão do Magento

- **Sintoma:** `Command "config:delete" is not defined` ao tentar voltar uma chave ao padrão do `config.xml`.
- **Correção:** zerar a chave com string vazia, o que equivale ao padrão para fins de renderização:

```bash
bin/magento config:set webjump_gustavo/halloween_theme/footer_show_image ""
bin/magento cache:flush
```

Isso deixa uma linha vazia em `core_config_data`. Para um estado realmente limpo, a linha é removida direto no banco, dentro do container (o `bin/magento` local não tem cliente MySQL):

```bash
bin/cli sh -c "mysql -h <host> -u<user> -p<pass> <db> -e \
  \"DELETE FROM core_config_data WHERE path LIKE 'webjump_gustavo/halloween_theme/%'\""
```

### 6. Minicarrinho branco sobre cabeçalho escuro

- **Sintoma:** ao abrir o minicarrinho, o painel aparecia com fundo branco e setas brancas, ilegível sobre o header escuro. Os textos ficavam legíveis apenas porque o Luma já os pinta de escuro.
- **Causa:** `.minicart-wrapper .block-minicart` define `background: #fff` e bordas claras, e as setas usam `border-color: @color-white`.
- **Correção:** `Magento_Checkout/web/css/source/_extend.less` redefine a superfície com `@panel__background-color` e `@border-color__base`, troca as setas para a cor do painel, remove o `box-shadow` padrão e ajusta a cor do fechar, das divisórias e do nome do produto.

### 7. Toolbar do catálogo com campos claros e texto claro

- **Sintoma:** `#sorter`, o rótulo "Sort By" e a contagem de produtos apareciam em cinza claro (`#D4D4D0`) sobre um campo cinza claro (`#F0F0F0`).
- **Causa:** o Luma usa `@toolbar-element-background: @color-gray94` junto de `@toolbar-element__color: @color-gray37`, combinação só legível na paleta original.
- **Correção:** `Magento_Catalog/web/css/source/_extend.less` redefine as duas variáveis para `@form-element-input__background` e `@text__color`, remove o `box-shadow` do `select` e usa o prefixo `body` em `.modes-mode.active` porque o `styles-l.css` é carregado **depois** do `styles-m.css` no desktop e o Luma ainda declara `.modes-mode.active` com `color: @color-gray37`.

### 8. Card "salta" e a página rola ao passar o mouse no grid

- **Sintoma:** ao pairar o mouse sobre um produto, a página ganhava uma barra de rolagem e os cards abaixo se deslocavam.
- **Causa:** a partir de 640px o Luma aplica `margin: -10px` e `padding: 9px` em `.products-grid .product-item-info:hover`, o que altera a caixa do card em 20px e, com `overflow-x: hidden` no grid, desloca o documento inteiro.
- **Correção:** o parcial `extend/_product-cards.less` usa `margin: 0` e mantém o `padding: 12px` já declarado na regra base, de modo que a geometria do card é idêntica no hover. Todos os seletores do parcial foram prefixados com `body` para vencer o Luma sem analisar cada par de especificidade.

### 10. Cursor de clique voltando ao padrão em alguns elementos

- **Sintoma:** a vassoura funcionava no repouso, mas no clique alguns elementos — o logo, itens de menu, blocos de produto — mostravam a seta do sistema em vez da vassoura laranja.
- **Causa:** `cursor` é uma propriedade **herdada**, não uma que "cascade" contra uma regra mais específica. Regras como `.page-header .logo img { cursor: pointer }` do Luma vencem a herança por especificidade, então `body:active { cursor: ... }` nunca chegava nesses elementos.
- **Correção:** uma regra universal com `!important`, que vence qualquer seletor do Luma:
  ```less
  body:active,
  body:active *:not(input):not(textarea):not(select) {
    cursor:
      url("@{cursor__image-active}") @cursor__hotspot,
      pointer !important;
  }
  ```
  Os campos de texto ficam de fora de propósito, para manter o I-beam ao selecionar. Além disso, `body::after` pré-carrega `cursor-broom2.svg` como `background-image`, porque sem o arquivo no cache o primeiro clique mostrava a seta do sistema até o SVG terminar de baixar.
- **Auditoria:** contagem de elementos interativos sem cursor customizado na home, na PLP e na PDP — **0** nos três (12, 44 e 12 interativos respectivamente).

### 11. Teia com as cores originais ficava invisível

- **Sintoma:** a teia ocupava o canto superior esquerdo, respondia 200 no asset e desenhava... quase nada. Só se percebia um traço fantasma.
- **Causa:** a arte é quase toda preta sobre um `#0A0A0C`. Os `#231F20`, `#1A1A1A`, `#000000`, `#414042` e `#5C5C5C` são indistinguíveis do fundo, e a opacidade `0.45` ainda derruba mais o resto. Restavam só os brancos e cinzas claros da própria arte.
- **Como foi medido:** screenshot do canto com e sem a teia (`body::before` desligado via DevTools), diff pixel a pixel e contraste relativo WCAG por pixel. Com as cores originais, o delta mediano era **4/255** e apenas **0,9%** dos pixels alterados chegavam a contraste ≥ 3.
- **Correção:** as 159 ocorrências das cinco cores escuras do arquivo viraram `#D4D4D4` (o Branco Osso da paleta), preservando as 82 ocorrências de `#FFFFFF`, `#E6E7E8` e `#C6C6C6`, que já eram legíveis. Nenhuma cor escura sobrou no arquivo. Resultado medido: delta mediano **44/255** e **11,2%** dos pixels com contraste ≥ 3 — 11× mais diferença mediana e 14,6× mais pixels perceptíveis.

> Esse contraste foi medido por script porque a imagem não é inspecionável a olho nu no diff. É a mesma técnica usada para calibrar o `viewBox`: rasterizar, medir, decidir.

### 12. Logo 40px fora do centro abaixo de 768px

- **Sintoma:** com `left: 50%` + `translateX(-50%)` a logo ficava centralizada de 768px em diante, mas parava 40px à direita do centro em 320, 375, 480 e 640px.
- **Causa:** `left` de um elemento **relatively positioned** é somado à sua posição estática, e a posição estática de um float já inclui a margem. Abaixo de 768px o Luma declara `.logo { margin-left: 40px }`, então a regra somava 160px de deslocamento a um static de 40px.
- **Correção:** `margin-left: 0` na mesma regra do `left: 50%`. Como `.page-header .logo` tem especificidade maior que o `.logo` do Luma, vence inclusive dentro da media query dele. Medido depois da correção, o erro de centralização é de `0,0px` em 320, 480, 640, 768, 900, 1024, 1280 e 1440px, e de `0,5px` em 375 e 1023px — o resto é arredondamento de pixel. Em 320px a logo é medida com 145px em vez de 170px porque o `max-width: 50%` do Luma a aperta, e ela continua centrada nessa largura menor.

---

## Evidências

> Coletadas contra o ambiente local (docker compose, `https://magento.test`).

### 1. O tema aparece no Admin com preview e está aplicado na store view

O tema está registrado na tabela `theme` e configurado como ativo:

```bash
bin/mysql -e "SELECT theme_id, theme_path, theme_title FROM theme WHERE theme_path = 'Webjump/halloween';"
# theme_id = 4 | Webjump/halloween | Webjump Halloween

bin/mysql -e "SELECT scope, scope_id, value FROM core_config_data WHERE path = 'design/theme/theme_id';"
# scope = default | scope_id = 0 | value = 4
```

> <img width="1503" height="193" alt="image" src="https://github.com/user-attachments/assets/478f3ce2-701f-45b9-bbbb-af8816f1f28e" />
>
> <img width="1493" height="192" alt="image" src="https://github.com/user-attachments/assets/30a787e9-a979-49ac-8eec-7c1b606eaad6" />

- **Content > Design > Themes**: o tema **Webjump Halloween** listado com a imagem de preview (`media/preview.jpg`).

> <img width="1821" height="578" alt="themes" src="https://github.com/user-attachments/assets/67320477-9e0c-4275-acee-4c5535a5bd14" />

- **Detalhe do tema**: título, herança do Luma e preview.

> <img width="1820" height="741" alt="theme-halloween" src="https://github.com/user-attachments/assets/e738968e-4647-46f2-aefd-0a5bbcc951b1" />

- **Content > Design > Configuration**: a Store View Principal com o tema **Webjump Halloween** aplicado.

> <img width="1907" height="953" alt="content-design" src="https://github.com/user-attachments/assets/d2cbd54f-6d93-4248-b60c-7b2cf66f1cae" />

### 2. Paleta e tipografia em toda a loja, não só na home

Home, listagem de produtos, página de produto e rodapé com o fundo escuro, os links em laranja abóbora, os botões em vermelho sangue e os títulos em Cormorant Garamond:

- **Home**

> <img width="1917" height="958" alt="image" src="https://github.com/user-attachments/assets/b89cdf8f-571d-4740-9015-a9f3698d49df" />


- **Listagem de produtos (PLP)**

> <img width="1917" height="958" alt="image" src="https://github.com/user-attachments/assets/6ff0aca4-e038-480c-baf6-de8b82a08a6f" />
>
> <img width="1917" height="958" alt="image" src="https://github.com/user-attachments/assets/b3bce644-1339-413e-9a6c-ed16e635e6d0" />


- **Minicarrinho**

> <img width="343" height="339" alt="image" src="https://github.com/user-attachments/assets/7cae667a-c306-4486-8396-1bb6c376e856" />

- **Carrinho**

> <img width="1917" height="958" alt="image" src="https://github.com/user-attachments/assets/d0839c46-c485-46f3-99c9-532c83cd3aab" />

- **Página de produto (PDP)**

> <img width="1917" height="958" alt="image" src="https://github.com/user-attachments/assets/b2fbcd83-3181-4edf-90e4-59e7cd2ea4bc" />

- **Rodapé**: links legíveis, faixa de arte entre o conteúdo e o copyright.

> <img width="1917" height="958" alt="image" src="https://github.com/user-attachments/assets/220c282d-5293-44e6-904e-fc9e7c008b27" />

A ordem dos filhos de `.page-footer` no HTML renderizado é `footer content` → `halloween-footer-bg` → `copyright`.

### 3. Nenhum arquivo em `vendor/` ou no Luma foi alterado

O trabalho toca somente o tema, o módulo do projeto e a documentação:

```bash
git status --short
```

> <img width="1077" height="389" alt="image" src="https://github.com/user-attachments/assets/961602b1-1e70-47f2-a2c0-f09f49befd3f" />
> <img width="1188" height="259" alt="image" src="https://github.com/user-attachments/assets/1a80035d-a199-4fbf-839d-e4165c6fe626" />

Nenhuma linha aponta para `src/vendor/`, `src/lib/` ou `src/app/design/frontend/Magento/`. Como `vendor/` costuma estar no `.gitignore`, a conferência complementar compara a data dos arquivos do Luma com a do `theme.xml` do tema:

```bash
cd src
find vendor/magento/theme-frontend-luma -newer app/design/frontend/Webjump/halloween/theme.xml -type f
# sem saída: nenhum arquivo do Luma foi modificado depois da criação do tema
```
> <img width="1489" height="389" alt="image" src="https://github.com/user-attachments/assets/258d1684-a898-479b-8447-93b0979a2866" />

### 4. A fonte própria carrega pelo caminho do tema e o texto continua legível

O deploy compila o LESS e publica a fonte dentro do próprio tema:

```bash
bin/magento setup:static-content:deploy -f --theme=Webjump/halloween
```

O `styles-m.css` compilado resolve a fonte localmente, sem CDN:

```css
@font-face {
  font-family: "Cormorant Garamond";
  src: url("../fonts/cormorant-garamond-bold.woff2") format("woff2");
  font-weight: 700;
  font-style: normal;
  font-display: swap;
}
```

A requisição da fonte retorna 200 pelo caminho do tema:

```bash
curl -k -s -I "https://magento.test/static/version1790600955/frontend/Webjump/halloween/en_US/fonts/cormorant-garamond-bold.woff2"
# HTTP/2 200 - content-type: font/woff2 (22.340 bytes)
```

> <img width="1489" height="389" alt="image" src="https://github.com/user-attachments/assets/1df5cf8a-3d4b-4d3e-80dc-f79b641d910d" />

### 5. Configuração pelo Admin (Stores > Configuration > Webjump Gustavo)

Os três grupos da seção `webjump_gustavo` — "Bloco Home", "Avaliações" e "Tema Halloween" — e o menu lateral **Webjump > Avaliações**:

> <img width="1827" height="894" alt="admin-config1" src="https://github.com/user-attachments/assets/2ab73732-c385-410c-b96b-b9fdcc4ccbd0" />
>
> <img width="1827" height="894" alt="admin-config2" src="https://github.com/user-attachments/assets/80eea02e-01c0-4d0d-82ea-71d242dd741f" />

### 6. Responsividade

Chrome headless percorrendo **320, 375, 480, 640, 768, 900, 1023, 1024, 1280, 1440 e 1920px** na home e em `/women/tops-women.html`, comparando `scrollWidth` com `clientWidth` do `documentElement`: em todas as larguras e nas duas páginas os valores são iguais, ou seja, **sem rolagem horizontal**.

> <img width="337" height="720" alt="reponsividade" src="https://github.com/user-attachments/assets/d4176290-fc31-47a8-a40a-f5f4bff45b26" />
>
> <img width="337" height="720" alt="responsividade2" src="https://github.com/user-attachments/assets/20e6a6e0-2337-4f21-bce7-f42ab4f7be81" />
>
> <img width="334" height="719" alt="image" src="https://github.com/user-attachments/assets/bbcaf994-7d95-4e65-9080-b7befa27a0b1" />

Antes da correção, `women/tops-women.html` em 375px media `scrollWidth = 386` contra `clientWidth = 375`, corrigido com `box-sizing: border-box` no card. A altura da faixa do rodapé acompanha a proporção da arte (63px em 320px, 285px em 1440px, 320px a partir de 1920px, onde entra o `max-height`), então a imagem não é esticada nem cortada.

### 7. Efeitos de Halloween: RequireJS carregando, assets resolvendo e guardas respeitadas

O console abre **sem nenhum erro de JavaScript**, o que já provou que o alias `halloween-fx` resolveu (o sintoma anterior era `Uncaught Error: Script error for "halloween-fx"`). Os assets respondem 200 pelo caminho do tema:

```bash
for f in web-corner.svg cursor-broom.svg cursor-broom2.svg loader-pumpkin.svg; do
  curl -s --cacert rootCA.pem -o /dev/null -w "$f %{http_code} %{size_download}b\n" \
    "https://magento.test/static/frontend/Webjump/halloween/en_US/images/$f"
done
```

```text
web-corner.svg      200 141555b
cursor-broom.svg    200 7546b
cursor-broom2.svg   200 16811b
loader-pumpkin.svg  200 1132b
```

### 8. Vídeo do storefront

> [Gravação de tela de 2026-10-01 17-32-24.webm](https://github.com/user-attachments/assets/6bbe1a82-b51d-467e-bf90-65fac0ef4ca1)


---

### CRITÉRIOS DE ACEITE

- [x] O tema aparece no admin com preview e está aplicado na store view
- [x] A paleta e a tipografia mudaram em toda a loja, não só na home
- [x] Nenhum arquivo em vendor/ ou no tema Luma foi alterado
- [x] A fonte própria carrega pelo caminho do tema, e o corpo do texto continua legível
- [x] README explica quais variáveis da biblioteca foram sobrescritas e por quê
