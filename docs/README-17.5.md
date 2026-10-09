# Desafio 17.5 - Binding próprio do Knockout

## Resumo

Binding handler customizado `ko.bindingHandlers.shake` registrado no ecossistema Knockout do Magento via RequireJS, em duas vias: `deps` global e mixin no `Magento_Ui/js/lib/knockout/bindings/bootstrap`. Aceita parâmetros de intensidade (`low`/`medium`/`high` ou valor em px), duração (`ms`/`s`) e gatilho (`hover`, `click`, `loop`, `auto`), injetados como CSS custom properties e consumidos por `@keyframes` LESS com aceleração de GPU. Consumido em dois pontos distintos da loja sem duplicar lógica: a mensagem do contador de Halloween (vibração contínua sutil, `low`) e o botão de checkout do minicart (tremor intenso no hover, `high`).

## Objetivo

Criar uma diretiva de Knockout parametrizável por intensidade e duração, registrada e funcional quando declarada em template, aplicada em pelo menos dois lugares diferentes da loja, e documentar quando vale a pena criar um binding próprio em vez de um `uiComponent`.

## Quando vale um binding próprio em vez de um uiComponent

| Critério | Binding (`ko.bindingHandlers.shake`) | `uiComponent` |
| :--- | :--- | :--- |
| Responsabilidade | Efeito de DOM, sem estado | Estado, regras de negócio, dados |
| Markup | Decora um nó existente | Possui template próprio / `jsLayout` |
| Reuso | Transversal, agnóstico de módulo | Acoplado à funcionalidade |
| Ciclo de vida | `init`/`update`/`dispose` do elemento | `uiRegistry` (`initialize`/`destroy`) |
| Custo | Um arquivo + registro no RequireJS | XML de layout + registro + boilerplate |

**Vale criar um binding próprio quando a necessidade é:**

1. **Comportamento de DOM puro** — efeitos visuais (tremor, fade, slide), animações, tooltips, máscaras de input, auto-scroll ou integração com bibliotecas de DOM de terceiros (datepickers, sliders).
2. **Reuso transversal** — aplicar a qualquer tag (`<button>`, `<span>`, `<div>`) em templates de módulos diferentes, sem alterar a árvore de componentes.
3. **Ausência de estado de negócio** — o efeito reage a valores simples ou observables já existentes no template, sem manter estado próprio complexo ou sincronizar com APIs.
4. **KISS e YAGNI** — um `uiComponent` para uma animação adiciona registro no `uiRegistry`, hierarquia de layout e boilerplate sem nenhum ganho funcional.

**Deve-se optar por um `uiComponent` quando:**

1. A funcionalidade gerencia estado próprio e múltiplos observables reativos.
2. Há persistência ou comunicação de dados (`customerData`, AJAX, `localStorage`).
3. É preciso renderizar estrutura de templates dinâmica com regiões (`getRegion`) e filhos.
4. Outros componentes precisam interagir ou se inscrever diretamente via `uiRegistry`.

**Veredito deste desafio:** o shake é manipulação de DOM pura, sem estado de negócio e reusável em qualquer template — o perfil exato de um binding. Um componente aqui exigiria registro no `uiRegistry`, declaração em layout e template próprio, boilerplate injustificado para uma animação.

## Arquitetura

```text
Consumo (templates)
  ├─ Magento_Checkout/template/minicart/content.html (tema)   #top-cart-btn-checkout
  │    data-bind="shake: { intensity: 'high', duration: 500, trigger: 'hover' }"
  └─ Webjump_Gustavo/templates/halloween_countdown.phtml      .halloween-countdown__message
       data-bind="text: message, shake: { intensity: 'low', duration: 1000, trigger: 'loop' }"
                │
                ▼  RequireJS — Webjump_Gustavo/view/frontend/requirejs-config.js
  ├─ deps: carrega Webjump_Gustavo/js/bindings/shake em toda página
  └─ mixin em Magento_Ui/js/lib/knockout/bindings/bootstrap (shake-mixin.js)
       garante o handler registrado antes do applyBindings do bootstrap
                │
                ▼  Webjump_Gustavo/web/js/bindings/shake.js — ko.bindingHandlers.shake
  ├─ init     normaliza presets, injeta --ko-shake-* no element e liga listeners por trigger
  ├─ update   reage a mudanças do valueAccessor (observable) e reaplica as CSS vars
  └─ dispose  ko.utils.domNodeDisposal.removeNode → limpa listeners e timeouts (sem memory leak)
                │
                ▼  Webjump_Gustavo/web/css/source/_module.less
  ├─ @keyframes ko-shake: translate3d + rotate — composição na GPU, sem reflow de layout
  └─ .ko-shake-active: animation-duration/iteration-count lendo as CSS vars do element
```

Parâmetros aceitos no `data-bind`:

| Parâmetro | Valores | Padrão | Efeito |
| :--- | :--- | :--- | :--- |
| `intensity` | `'low'`, `'medium'`, `'high'`, número (`8`) ou string com unidade (`'6px'`) | `'medium'` | Amplitude do deslocamento e da rotação |
| `duration` | número em ms (`500`) ou string (`'800ms'`, `'1s'`) | `500ms` | Tempo de um ciclo da animação |
| `trigger` | `'hover'`, `'click'`, `'loop'`, `'auto'` | `'auto'` | Modo de disparo; `'loop'` vibra continuamente |
| `enabled` | booleano ou observable booleano | `true` | Liga/desliga o efeito reativamente |

Presets de intensidade: `low` = 2px / 0.5deg, `medium` = 5px / 1.5deg, `high` = 10px / 3deg; valor numérico vira `Npx` com rotação proporcional.

## Estrutura de Arquivos

Novos:

```text
src/app/code/Webjump/Gustavo/view/frontend/
├── requirejs-config.js                         # deps global + mixin do bootstrap
└── web/js/bindings/
    ├── shake.js                                 # Binding handler (init, update, dispose)
    └── shake-mixin.js                           # Interceptor do bindings/bootstrap
docs/
└── README-17.5.md                               # Esta documentação
```

Alterados:

```text
src/app/code/Webjump/Gustavo/view/frontend/
├── templates/halloween_countdown.phtml         # shake: low/1000ms/loop na mensagem do contador
└── web/css/source/_module.less                  # @keyframes ko-shake + .ko-shake-active
src/app/design/frontend/Webjump/halloween/
└── Magento_Checkout/web/template/minicart/content.html  # shake: high/500ms/hover no botão de checkout
```

Nenhum arquivo em `vendor/` ou do Luma foi alterado.

## Decisões de Implementação e Justificativas

1. **Binding handler em vez de `uiComponent`** — análise completa na seção "Quando vale um binding próprio em vez de um uiComponent". Resumo da escolha: comportamento de DOM puro e reuso transversal descartam o overhead de componente (registro, layout, template próprio).
2. **Registro em duas vias (`deps` + mixin no bootstrap)** — o bootstrap do `Magento_Ui` é quem dispara o `applyBindings` inicial do documento. Confiar apenas no `deps` global cria corrida de carga: o bootstrap pode aplicar bindings antes do módulo do handler resolver. O mixin intercepta o próprio bootstrap e carrega o `shake.js` antes da execução dele; o `deps` foi mantido para páginas que avaliam `data-bind` fora do bootstrap. Alternativa descartada: depender só do `deps`.
3. **Animação em CSS keyframes + CSS custom properties, controladas por JS** — o JS só injeta `--ko-shake-*` e alterna a classe; o browser faz a animação na GPU (`translate3d` + `rotate`, `will-change: transform`), sem reflow por frame. Alternativa descartada: animar por `setInterval`/`requestAnimationFrame` — consome CPU, exige gerência manual de ciclo e o efeito `loop` viraria um timer infinito. As variáveis negativas (`--ko-shake-distance-neg`) são injetadas pelo JS em vez de `calc()` nos keyframes, por compatibilidade com o compilador LESS.
4. **Presets normalizados no JS em vez de uma classe CSS por intensidade** — `low`/`medium`/`high`/px são resolvidos para CSS vars no `init`, permitindo valor arbitrário (`shake: { intensity: 7 }`) sem multiplicar classes. Alternativa descartada: `.shake-low`, `.shake-medium`… — explosão de classes e impossível combinar com duração custom.
5. **`loop` via `animation-iteration-count: infinite`** — o browser sustenta a vibração contínua sem timers do nosso lado; os outros triggers rodam 1 iteração e a classe é removida por um `setTimeout` de `duration + 50ms` (com reflow forçado via `offsetWidth` para reiniciar a animação em disparos repetidos).
6. **Ciclo de vida com `domNodeDisposal` e promoção `inline` → `inline-block`** — listeners (`mouseenter`/`click`), timeouts e classe são limpos quando o Knockout remove o nó. Elementos `display: inline` (o `<span>` do contador) não recebem `transform`, então o binding os promove a `inline-block` automaticamente.

## Problemas Encontrados e Correções

1. - **Sintoma:** binding não registrava — `ko.bindingHandlers.shake` ficava `undefined` e o template não tremia.
   - **Causa:** o `shake.js` importava `Magento_Ui/js/lib/knockout/template/renderer` para suportar a sintaxe virtual `shake="..."`; o `renderer` pertence ao mesmo grafo de `bindings/bootstrap`, que é exatamente o módulo interceptado pelo nosso mixin — dependência circular no RequireJS.
   - **Correção:** removido o import do `renderer`. A sintaxe `data-bind="shake: ..."` não precisa dele; o `renderer.addAttribute` só seria necessário para o atributo virtual em templates de UI Components, que não é o caso de uso.
2. - **Sintoma:** depois do deploy, "o tremor ainda não estava funcional": o DOM recebia `ko-shake-active` e as CSS vars, mas nada se movia.
   - **Causa:** o deploy publicado estava uma iteração atrás dos fontes — o `styles-l.css`/`styles-m.css` foi compilado às 11:37, antes de o `_module.less` ganhar os keyframes (11:39); o `content.html` do minicart foi publicado sem o binding; o `shake.js` publicado ainda tinha o import do `renderer` (md5 divergente do fonte). Ou seja: JS registrado, mas sem keyframes no CSS servido e sem binding no template publicado.
   - **Correção:** `rm -rf var/view_preprocessed pub/static/frontend/Webjump/halloween`, `setup:static-content:deploy en_US -f` e `cache:flush`. Verificado por md5/grep nos arquivos publicados, por `curl` no CSS servido (7 ocorrências de `ko-shake`) e por E2E no navegador (animação computada).
3. - **Sintoma:** o `setup:static-content:deploy` aborta com erro de leitura em `Webjump_OfferBox/.../preview.html` (adminhtml).
   - **Causa:** arquivo de preview do módulo OfferBox inexistente no `pub/static` adminhtml — problema pré-existente do ambiente, não relacionado a este desafio.
   - **Correção:** nenhuma necessária para o desafio — o erro ocorre depois de o tema frontend concluir 100%, então os artefatos da loja são publicados. Vale consertar o OfferBox em separado para o deploy terminar limpo.

## Onde o shake aparece

| Superfície | Elemento | Configuração | Comportamento |
| :--- | :--- | :--- | :--- |
| Home — contador da campanha | `.halloween-countdown__message` | `low` (2px), `1000ms`, `loop` | Vibra sozinho em loop infinito, sutil |
| Minicart — botão de checkout | `#top-cart-btn-checkout` | `high` (10px), `500ms`, `hover` | Treme a cada passagem do mouse |


## Evidências

### 1. Contador da Home em loop

> Home com o contador vibrando; no DevTools, `.halloween-countdown__message` com a classe `ko-shake-active`, as CSS vars `--ko-shake-*` inline e `animation: ko-shake 1s infinite` na aba Computed.

> <img width="1853" height="927" alt="image" src="https://github.com/user-attachments/assets/74b5a74d-8f34-443b-b47e-58e9be3c4930" />

### 2. Botão do minicart no hover

> Minicart com o botão tremendo durante o hover (`ko-shake-active`, `animation: ko-shake 0.5s`, 10px/3deg); após ~550ms a classe sai e `animation-name` volta a `none`, provando a limpeza pós-duração.

> <img width="1853" height="927" alt="image" src="https://github.com/user-attachments/assets/bb454007-34d1-476c-9267-81c1bf36f131" />

### 3. Registro do binding sem erros

> Console do DevTools com `require(['ko'], function(ko) { console.log(typeof ko.bindingHandlers.shake) })` devolvendo `"object"`, sem nenhum erro de JS na página.

<img width="1853" height="927" alt="image" src="https://github.com/user-attachments/assets/6edbe7e0-d36d-41e7-922e-90edfdef3993" />

### 4. Demonstração do shake em vídeo

> [17-6.webm](https://github.com/user-attachments/assets/ee4184c7-721b-4dbb-af4c-cf13a7e4cadf)


### CRITÉRIO DE ACEITE

- [x] O binding está registrado e funciona ao ser declarado no template
- [x] Aceita parâmetros e o comportamento muda conforme eles
- [x] Foi usado em dois lugares distintos, sem duplicar código
- [x] README explica quando vale criar binding próprio em vez de um componente
