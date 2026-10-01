# Tema Noite Assombrada (Webjump Halloween)

Tema customizado de Halloween para Magento 2, com herança direta de `Magento/luma` (`Webjump/halloween`). Nenhum arquivo em `vendor/` ou no tema Luma foi alterado.


## Organização

- `web/css/source/_theme.less`: **apenas** variáveis da Magento UI Library (tabela abaixo);
- `web/css/source/_extend.less`: índice que importa os parciais de `web/css/source/extend/` (fontes, tipografia, header, cards, PDP, botões, rodapé, breadcrumbs, hero e efeitos de Halloween);
- `Magento_Checkout/web/css/source/_extend.less`: superfície escura e contraste dos textos do minicarrinho;
- `Magento_Catalog/web/css/source/_extend.less`: fundo e cores da toolbar do catálogo;
- `web/fonts/cormorant-garamond-bold.woff2`: fonte local carregada com `@font-face` e `@{baseDir}`.

## Campanha Noite Assombrada

- `Magento_Theme/layout/default.xml`: faixa `halloween.campaign.bar` em `page.top`, remoção de `catalog.compare.sidebar` e `copyright`, e `top.search` movido para `header.panel`;
- `Magento_Theme/templates/html/halloween-campaign-bar.phtml`: markup da faixa, único ponto do tema que poderia quebrar o cabeçalho do Luma;
- `web/css/source/extend/_campaign-bar.less`: estilo responsivo da faixa;
- `Magento_Search/templates/form.mini.phtml`: cópia integral do original com a marcação da busca ajustada;
- `i18n/en_US.csv`: vocabulário gótico-vitoriano da loja, textos da faixa e assunto do e-mail;
- `Magento_Sales/email/order_new.html` e `order_new_guest.html`: cópias integrais com a faixa de campanha e a identidade escura;
- `web/css/source/_email-variables.less` e `_email-extend.less`: e-mail dark completo.

Os dois `_extend.less` de módulo existem porque `Magento_Checkout` e `Magento_Catalog` não têm parciais próprios no tema. Como parciais de módulo são compiladas depois de `web/css/source/_extend.less` dentro do mesmo `styles-m.css`, elas vencem o Luma em empate de especificidade. Já as regras que precisam ganhar do Luma carregado no `styles-l.css` (desktop) recebem o prefixo `body`, como em `body .modes-mode.active`.

## Variáveis da Biblioteca Sobrescritas e Justificativa

| Variável                                  | Valor Definido                                | Por que foi sobrescrita?                                                                                                                                              |
| ------------------------------------------ | --------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `@page__background-color`                | `#0A0A0C`                                   | Substitui o fundo branco `#ffffff` padrão do Luma pelo preto meia-noite, definindo o tom escuro de toda a loja.                                                    |
| `@panel__background-color`               | `#141416`                                   | Garante que o painel superior de boas-vindas/login do cabeçalho harmonize com o fundo escuro, com contraste refinado.                                                |
| `@sidebar__background-color`             | `#141416`                                   | Alinha a cor dos blocos laterais de carrinho, checkout e conta com a superfície escura.                                                                              |
| `@border-color__base`                    | `#26262B`                                   | Troca as bordas cinza claro do Luma por um grafite escuro sutil, evitando linhas brancas ou estouradas no tema noturno.                                               |
| `@primary__color`                        | `#D84B20`                                   | Substitui a cor de destaque do Luma pelo laranja abóbora de Halloween, alcançando ícones de navegação e acentos globais.                                         |
| `@primary__color__dark`                  | `#5C0000`                                   | Tom da escala da cor primária fixado na paleta do tema, em vez do valor derivado automaticamente.                                                                    |
| `@primary__color__darker`                | `#141416`                                   | Tom mais escuro da escala primária, alinhado à superfície escura.                                                                                                     |
| `@primary__color__lighter`               | `#8E8E93`                                   | Tom claro da escala primária fixado no cinza médio da paleta.                                                                                                         |
| `@primary__color__light`                 | `#D4D4D0`                                   | Tom claro da escala primária, igual ao branco osso do texto.                                                                                                          |
| `@secondary__color`                      | `#5C0000`                                   | Define a cor de apoio profunda (vermelho sangue) utilizada como base dos botões de ação.                                                                           |
| `@text__color`                           | `#D4D4D0`                                   | Define o tom "Branco Osso" para o corpo do texto em toda a loja. Garante contraste WCAG AAA contra o fundo `#0A0A0C`, mantendo alta legibilidade sem cansar a vista. |
| `@text__color__intense`                  | `#FFFFFF`                                   | Branco puro para textos com ênfase alta e labels críticas.                                                                                                          |
| `@text__color__muted`                    | `#8E8E93`                                   | Cinza médio para textos secundários, como SKUs, breadcrumbs e notas de rodapé.                                                                                     |
| `@heading__color__base`                  | `#F0EDE6`                                   | Define cor clara e uniforme para todos os cabeçalhos (`h1` a `h6`), mantendo harmonia com o texto base.                                                          |
| `@heading__font-family__base`            | `'Cormorant Garamond', Georgia, serif`      | Associa a família tipográfica gótica vitoriana a todos os títulos do sistema de forma declarativa.                                                                |
| `@heading__font-weight__base`            | `700`                                       | Assegura que os títulos sejam renderizados no peso negrito nativo do arquivo de fonte carregado.                                                                     |
| `@h1__font-color` a `@h6__font-color`  | `@heading__color__base`                     | Garante que nenhuma regra específica de título do Luma reverta as cores de cabeçalho.                                                                              |
| `@link__color`                           | `#D84B20`                                   | Links em toda a loja assumem o tom laranja abóbora.                                                                                                                  |
| `@link__hover__color`                    | `#FF7043`                                   | Efeito hover com tom de chama acesa, indicando interatividade claramente ao usuário.                                                                                 |
| `@link__visited__color`                  | `#D84B20`                                   | Links visitados mantêm o laranja abóbora, sem voltar à cor padrão do Luma.                                                                                          |
| `@link__active__color`                   | `#FF5722`                                   | Estado ativo (clique) do link em laranja mais vivo.                                                                                                                   |
| `@button__color`                         | `#D4D4D0`                                   | Texto legível para botões secundários.                                                                                                                             |
| `@button__background`                    | `#1E1E24`                                   | Botões secundários ganham superfície cinza carvão metálica em vez do cinza claro do Luma.                                                                        |
| `@button__border`                        | `1px solid #3A3A42`                         | Borda refinada para botões secundários.                                                                                                                             |
| `@button__hover__color`                  | `#FFFFFF`                                   | Texto branco no hover dos botões secundários.                                                                                                                        |
| `@button__hover__background`             | `#2A2A33`                                   | Superfície um pouco mais clara no hover, como feedback de interação.                                                                                                |
| `@button__hover__border`                 | `1px solid #D84B20`                         | Borda laranja no hover dos botões secundários.                                                                                                                      |
| `@button__active__background`            | `#141416`                                   | Fundo escuro ao clicar no botão secundário.                                                                                                                          |
| `@button__active__border`                | `1px solid #D84B20`                         | Borda laranja no estado ativo, coerente com o hover.                                                                                                                 |
| `@button-primary__color`                 | `#FFFFFF`                                   | Texto branco sobre o vermelho sangue, garantindo contraste no CTA.                                                                                                   |
| `@button-primary__background`            | `#5C0000`                                   | Botões de conversão ("Adicionar ao Carrinho", "Finalizar Compra") ganham o tom dramático de vermelho sangue seco.                                                  |
| `@button-primary__border`                | `1px solid #7A0000`                         | Borda do botão de ação primária em tom vermelho rubi.                                                                                                             |
| `@button-primary__hover__color`          | `#FFFFFF`                                   | Mantém o texto branco no hover do CTA.                                                                                                                               |
| `@button-primary__hover__background`     | `#7A0000`                                   | Clareamento controlado no hover do botão primário para feedback tátil de clique.                                                                                   |
| `@button-primary__hover__border`         | `1px solid #D84B20`                         | Borda laranja no hover do CTA, ligando o botão à cor de destaque do tema.                                                                                           |
| `@button-primary__active__background`    | `#4A0000`                                   | Vermelho ainda mais escuro ao pressionar o CTA.                                                                                                                      |
| `@button-primary__active__border`        | `1px solid #4A0000`                         | Borda acompanhando o fundo no estado ativo do CTA.                                                                                                                   |
| `@header__background-color`              | `#141416`                                   | Modifica o fundo do cabeçalho principal (`.page-header`) através da variável do módulo `Magento_Theme`.                                                       |
| `@header-panel__background-color`        | `#101012`                                   | Escurece o topo da página para criar hierarquia visual com o cabeçalho principal.                                                                                   |
| `@header-panel__text-color`              | `#D4D4D0`                                   | Texto claro no painel superior (boas-vindas/login) sobre o fundo escuro.                                                                                             |
| `@header-icons-color`                    | `#D4D4D0`                                   | Ícones de busca, menu mobile e conta passam a ser claros.                                                                                                            |
| `@header-icons-color-hover`              | `#D84B20`                                   | Efeito hover dos ícones de cabeçalho em laranja.                                                                                                                    |
| `@minicart-icons-color`                  | `#D4D4D0`                                   | Ícone do minicarrinho claro, como os demais ícones do cabeçalho.                                                                                                    |
| `@minicart-icons-color-hover`            | `#D84B20`                                   | Hover do ícone do minicarrinho em laranja.                                                                                                                           |
| `@navigation__background`                | `#141416`                                   | Fundo da barra de categorias principal (`.nav-sections`).                                                                                                           |
| `@navigation-level0-item__active__color` | `#D84B20`                                   | Categoria ativa na navegação destacada com a cor tema.                                                                                                              |
| `@footer__background-color`              | `#141416`                                   | Define a cor de fundo do rodapé através da variável da biblioteca.                                                                                                 |
| `@footer-links-color`                    | `#F0EDE6`                                   | Links do rodapé em branco osso. O cinza médio anterior (`#A0A09C`) ficava invisível sobre o fundo escuro, o principal problema de legibilidade relatado.           |
| `@footer-links-color-hover`              | `#FF7043`                                   | Hover dos links do rodapé em tom de chama acesa, sinalizando interatividade.                                                                                         |
| `@footer-links-color-current`            | `#FFFFFF`                                   | Títulos dos grupos de links (elementos `<strong>`) em branco puro, para separar a heading do restante da lista.                                                     |
| `@footer-links-separator-border-color`   | `#26262B`                                   | Divisórias entre os grupos de links discretas e compatíveis com o tema noturno.                                                                                     |
| `@copyright__background-color`           | `#0E0E10`                                   | Faixa inferior de direitos autorais no tom mais escuro do rodapé.                                                                                                    |
| `@form-element-input__background`        | `#141416`                                   | Campos de formulário (inputs de texto, busca, campos de endereço) ganham fundo escuro.                                                                              |
| `@form-element-input__border-color`      | `#3A3A42`                                   | Contorno nítido e discreto para os campos de input.                                                                                                                  |
| `@form-element-input__border`            | `1px solid @form-element-input__border-color` | Monta o contorno do campo a partir da cor definida acima.                                                                                                          |
| `@form-element-input__color`             | `#D4D4D0`                                   | Texto digitado nos campos com alta legibilidade em branco osso.                                                                                                       |
| `@form-element-input-placeholder__color` | `#6E6E73`                                   | Placeholder discreto, distinguível do texto digitado.                                                                                                                |
| `@price-color` / `@product-info-price` | `#D84B20`                                   | Preços dos produtos na listagem (catálogo) e na página interna de produto em destaque laranja.                                                                     |
| `@product-price__muted__color`           | `#8E8E93`                                   | Preço secundário (por exemplo, o preço antigo) em cinza médio, sem competir com o preço em laranja.                                                                 |

## Efeitos Visuais Interativos (Halloween FX)

Efeitos de personalidade adicionados ao tema por `web/js/halloween-fx/` e `web/css/source/extend/_halloween-fx.less`. Só o que é entrada única e barata roda no mobile; o resto fica em `min-width: @screen__m` (768px). `prefers-reduced-motion: reduce` desliga os morcegos e o glow do loader.

| Efeito | Arquivo(s) | Abordagem | Mobile | reduced-motion |
|---|---|---|---|---|
| 🧹 **Cursor vassoura** | `_halloween-fx.less` + `cursor-broom.svg` + `cursor-broom2.svg` | CSS puro (`cursor: url(...)`), troca para a vassoura laranja no `:active` via regra universal `body:active *`; `!important` para vencer os seletores específicos do Luma; `body::after` pré-carrega o SVG ativo para não piscar no primeiro clique | Não exibido em < 768px | Sem animação, inalterado |
| 🕸️ **Teia no canto** | `_halloween-fx.less` + `web-corner.svg` | CSS puro no `body::before`: sem nó no DOM e sem JS, `position: fixed` no canto superior esquerdo, `pointer-events: none`, `z-index: 250` | Não exibido em < 768px | Estática, inalterada |
| 🎃 **Loader abóbora** | `_halloween-fx.less` + `loader-pumpkin.svg` | CSS: `::before` com o SVG como `background-image` + `@keyframes pumpkin-glow`; loader centralizado com flex | CSS pronto, sem gatilho | Glow desligado |
| 🦇 **Morcegos de entrada** | `halloween-fx/bats.js` + `_halloween-fx.less` | 5 SVGs inline cruzam a tela em ~900ms e são removidos do DOM; não intercepta clique, não bloqueia hover | Ativo (900ms, sem listener) | Não injetado |

> O CSS do loader está pronto e correto (64×64, `contain`, `pumpkin-glow`), mas a loja não exibe a máscara: `.loading-mask` só é renderizado pelo bloco `main_css_preloader`, condicionado a `dev/css/use_css_critical_path` (desligado), e o `mage/loader` que criava a máscara por AJAX não é mais disparado por nenhum componente no Magento 2.4.

### Arquitetura de carregamento

Os efeitos vivem em módulos RequireJS que `init.js` orquestra. O `requirejs-config.js` do tema mapeia o path e empurra o entry point como dependência bootstrap, sem `data-mage-init` em nenhum template:

```js
// requirejs-config.js
var config = {
    paths: {
        'halloween-fx': 'js/halloween-fx'
    },
    deps: [
        'halloween-fx/init'
    ]
};
```

Sem o `paths`, o RequireJS resolve `halloween-fx` para `<baseUrl>/halloween-fx.js` e recebe HTTP 404 (MIME `text/plain`), o que aborta o módulo inteiro com `Uncaught Error: Script error for "halloween-fx"`. O arquivo real vive em `web/js/halloween-fx/`.

`init.js` decide o que roda: `desktop` (`min-width: 768px`), `motion` (`prefers-reduced-motion`) e `transactional` (os morcegos não tocam `/checkout` nem `/customer`), e repassa essas decisões a `bats.js`. A teia e o cursor são CSS puro e não passam pelo `init.js`; os morcegos nascem sem emoji, já que a renderização de emoji depende de fonte (`Noto Color Emoji`) que não é garantida.

### Assets e proporção das artes

A teia é arquivo estático em `web/images/`, e não SVG inline no JS: são 140KB de path data, que no JS custariam bundle e cache a cada tema sem ganho nenhum.

O `viewBox` do arquivo é o recorte da arte real, medido no canvas (alpha > 8) e não o do documento do Illustrator. Sem esse recorte a caixa fica com metade do espaço vazio:

| Arquivo | `viewBox` original | `viewBox` em uso | Proporção |
|---|---|---|---|
| `web-corner.svg` | `0 0 450 450` | `0 0 450 357` | 1.26 |

A caixa do CSS (`@halloween-web__width/height`) segue essa proporção.
