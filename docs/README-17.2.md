# Desafio 17.2 - Modo Assombrado e Minicart

## Resumo

Implementação do "Modo Assombrado" no tema `Webjump/halloween`: um interruptor no cabeçalho liga/desliga uma variante escura da loja trocando apenas uma classe no `<html>`, sem recarregar a página e com a escolha persistida em `localStorage`. No modo assombrado o fundo recebe uma cena temática presa ao viewport, o header/rodapé ficam translúcidos e o minicart deixa de ser um dropdown para virar uma sidebar full-height com mensagem dinâmica conforme a quantidade de itens.

---

## Objetivo

Adicionar um modo alternativo de visual ao tema, acionável pelo usuário e persistente entre páginas, aplicado por classe no HTML (não por reload), e customizar o minicart via mixin do RequireJS preservando o componente original com `this._super()`, incluindo uma mensagem que reage à quantidade de itens do carrinho.

---

## Arquitetura

```text
Interruptor (Magento_Theme::html/haunted-mode-toggle.phtml)
  ├─ Aplica a classe antes do paint lendo localStorage (evita flash)
  ├─ data-haunted-assets (JSON) -> preload das 3 cenas
  └─ web/js/halloween-mode.js
        ├─ applyState()          -> classe `haunted-mode` no <html>
        └─ toggleState()         -> document.startViewTransition + guards

Estilos (web/css/source/_extend.less)
  ├─ extend/_haunted-mode.less        -> fundo no <html>, superfícies, hero
  └─ extend/_haunted-mode-toggle.less -> pill no header (desktop) / no drawer (mobile)

Minicart
  ├─ requirejs-config.js              -> config.mixins de Magento_Checkout/js/view/minicart
  ├─ web/js/minicart-mixin.js         -> this._super() + observable `hauntedMessage`
  ├─ Magento_Checkout/web/template/minicart/content.html -> bloco .haunted-cart-message
  └─ Magento_Checkout/.../_extend.less -> sidebar full-height + cena carrinho-assombrado.jpg

i18n (i18n/en_US.csv)
  └─ Rótulos do interruptor e as 4 variações de mensagem
```

---

## Estrutura de Arquivos

```text
app/design/frontend/Webjump/halloween/
├── Magento_Checkout/
│   └── web/template/minicart/content.html            # Cópia do Luma + .haunted-cart-message
├── Magento_Theme/
│   └── templates/html/haunted-mode-toggle.phtml      # Interruptor (role=switch) + preload
├── web/
│   ├── css/source/extend/
│   │   ├── _haunted-mode.less                         # Fundo, superfícies translúcidas, hero
│   │   └── _haunted-mode-toggle.less                  # Pill desktop / pill no drawer mobile
│   ├── images/
│   │   ├── back-assombrado.jpg                        # Cena de fundo da loja
│   │   ├── banner-assombrado.jpg                      # Cena do hero no modo assombrado
│   │   ├── carrinho-assombrado.jpg                    # Cena da sidebar do minicart
│   │   ├── modo-assombrado.svg                        # Ícone do pill ligado
│   │   └── modo-default.svg                           # Ícone do pill desligado
│   └── js/
│       ├── halloween-mode.js                          # Toggle, View Transition e preload
│       └── minicart-mixin.js                          # Mixin do minicart (mensagens)
```

```text
app/design/frontend/Webjump/halloween/
├── Magento_Checkout/web/css/source/_extend.less      # Sidebar full-height + cena do carrinho
├── Magento_Theme/layout/default.xml                  # Bloco do interruptor em header-wrapper
├── i18n/en_US.csv                                    # Termos do modo e das mensagens
├── requirejs-config.js                               # Paths + deps + mixin do minicart
└── web/css/source/_extend.less                       # Importa os dois LESS novos
```

Nenhum arquivo em `vendor/` ou do Luma foi alterado.

---

## Decisões de Implementação e Justificativas

### 1. Estado por classe no `<html>`, não por recarregar a página

A alternância troca apenas `document.documentElement.classList`, disparada pelo JS. Isso evita reload, mantém o estado visual imediato e permite o cross-fade nativo via View Transitions. A alternativa (param de URL ou re-render server-side) exigiria round-trip e perderia a fluidez.

### 2. Persistência em `localStorage` com aplicação antes do paint

`webjump-haunted-mode` guarda a escolha e é lido por um script inline no template do interruptor, que adiciona a classe antes do primeiro paint. Sem isso, o usuário veria o tema claro por um instante a cada navegação (FOUC). O componente JS continua sendo a fonte de verdade e trata falhas de storage.

### 3. Fundo pintado no elemento raiz, com `background-attachment: fixed`

A imagem de fundo (com o gradiente de escurecimento) fica em `html.haunted-mode`. Assim a cena cobre o canvas inteiro e permanece presa ao viewport enquanto o conteúdo rola, inclusive em listagens longas — o que não acontecia quando a imagem era pintada no `body` (ver Problemas). O `body` passa a transparente.

### 4. Superfícies translúcidas sem `backdrop-filter`

Header, painel superior, barra de navegação e rodapé usam apenas `background-color` com alpha. O `backdrop-filter` foi descartado porque o fundo assombrado já é opaco o bastante e o filtro cria containing block para descendentes `position: fixed` (quebrava a sidebar do minicart).

### 5. Minicart customizado por mixin, preservando `this._super()`

O minicart é estendido por `config.mixins`, não copiado. A justificativa completa está na seção "Por que mixin e não map". Em resumo: adiciona comportamento sem duplicar o componente e sem congelar uma cópia do Magento no tema.

### 6. Minicart como sidebar full-height no modo assombrado

No modo padrão o minicart continua sendo o dropdown do Luma (sem alteração de comportamento). No modo assombrado ele vira um painel fixo à direita (380px no desktop, 100% no mobile), com a cena `carrinho-assombrado.jpg` em `contain` ancorada no rodapé e o topo no preto sólido do painel. A escolha isola a mudança ao estado assombrado e mantém o carrinho intacto no restante.

### 7. Troca com View Transition e preload das cenas

`toggleState()` usa `document.startViewTransition` quando disponível, com fallback instantâneo e guarda de `prefers-reduced-motion`. O template expõe as 3 cenas em `data-haunted-assets` e o JS as pré-carrega em `requestIdleCallback` (com fallback para `setTimeout`), evitando o "pop" das imagens no momento do clique.

### 8. Interruptor reposicionado por breakpoint, com rótulo de ação

No desktop o pill flutua à direita no `.header.content`, imediatamente à esquerda do carrinho (o `margin-left` do `.minicart-wrapper` dá o espaçamento). No mobile ele fica oculto com o drawer fechado e aparece como um pill fixo dentro do drawer aberto, onde tem espaço e visibilidade. Ao lado do ícone, o rótulo textual anuncia a próxima ação ("Ativar modo assombrado" quando desligado, "Desativar modo assombrado" quando ligado), para que a função do botão fique evidente nos dois contextos. A troca do texto aproveita o mesmo mecanismo do ícone — dois `<span>` no markup, alternados por CSS sob `html.haunted-mode`, sem custo de JS.

---

## Por que mixin e não map

O requisito era alterar `Magento_Checkout/js/view/minicart` para exibir a mensagem dinâmica, sem quebrar o componente original. O RequireJS oferece dois mecanismos e a escolha entre eles é o ponto central da solução:

- **`map`** só troca o arquivo que um identificador resolve. Para customizar o minicart, seria preciso copiar todo o `view/minicart.js` do Magento para o tema e editar a cópia. Isso gera duplicação, congela o componente na versão copiada (quebrando silenciosamente em upgrades) e afeta globalmente qualquer outro módulo que dependa do alvo.
- **`mixins`** (via `config.mixins`) intercepta o módulo: o arquivo do tema recebe o componente original como argumento e devolve um `extend`. O mínimo necessário para cumprir o requisito fica pequeno e isolado:

```js
return function (Minicart) {
  return Minicart.extend({
    initialize: function () {
      this._super(); // roda o initialize original
      this.hauntedMessage = ko.observable(/* ... */);
    },
  });
};
```

Consequências práticas dessa escolha: `this._super()` garante que todo o comportamento nativo (observables, totais, regiões) continua rodando antes de adicionarmos a mensagem; nenhum arquivo do `vendor/` é copiado; o mixin afeta somente o alvo declarado; e um upgrade do Magento atualiza a base automaticamente, mantendo a customização viva. Ou seja: **`map` substitui/duplica, `mixin` estende** — como o objetivo era adicionar comportamento preservando o original, o mixin é a ferramenta correta. O vínculo é declarado em `requirejs-config.js`:

```js
config: {
    mixins: {
        'Magento_Checkout/js/view/minicart': { 'minicart-mixin': true }
    }
}
```

---

## Problemas Encontrados e Correções

- **Sintoma:** em listagens longas o fundo assombrado desaparecia ao rolar; a cena só aparecia na primeira tela.
  - **Causa:** a imagem estava pintada no `body`, mas o Luma aplica `html, body { height: 100% }`, deixando a caixa do `body` com a altura do viewport. O documento era bem mais alto (ex.: 3346px contra 900px da caixa), então o fundo ficava recortado a esses 900px.
  - **Correção:** mover imagem e gradiente para `html.haunted-mode` (o fundo da raiz cobre o canvas inteiro) e deixar o `body` transparente/sem imagem. Medido: com a correção a cena permanece visível ao fim da rolagem.

- **Sintoma:** no modo assombrado a sidebar do minicart (e o pill no mobile) não se prendia à viewport, ficando limitada à caixa do cabeçalho.
  - **Causa:** o `backdrop-filter: blur()` do `.page-header` faz do header um containing block para descendentes `position: fixed`.
  - **Correção:** `backdrop-filter: none` em `html.haunted-mode .page-header` (e em `html.nav-open` no mobile), já que o fundo escuro dispensa o blur.

- **Sintoma:** a cena do minicart (`carrinho-assombrado.jpg`) parou de aparecer; a sidebar ficava só preta, com a mensagem de itens.
  - **Causa:** a cena foi movida para `.block-minicart::after`, mas o Luma já usa esse mesmo pseudo para a seta do dropdown e o define com `width: 0; height: 0` (a seta é desenhada por bordas). O `inset: 0` não vence um `width`/`height` explícitos, então o pseudo colapsava em 0x0 e o `background-image` nunca era pintado — o CSS estava "certo" no papel, mas invisível.
  - **Correção:** no modo assombrado o pseudo declara `width: 100%; height: 100%; border: 0`, ocupando a barra inteira, e `.action.close` sobe para `z-index: 2` para o botão fechar não ficar atrás da cena. O verificador foi endurecido para medir o **tamanho renderizado** do pseudo (380x900) em vez de só ler `background-image`/`opacity`, que passavam mesmo com o elemento invisível.

- **Sintoma:** o `dropdownDialog` do jQuery UI ignorava a geometria de sidebar e reabria o minicart no canto/flutuante.
  - **Causa:** o componente nativo escreve `top`/`left` inline no `.mage-dropdown-dialog` a cada abertura.
  - **Correção:** no modo assombrado a geometria (`position`, `top`, `right`, `width`, `height`) é declarada com `!important` para vencer o inline.

- **Sintoma:** no mobile com o drawer aberto, o scrim do `.nav-toggle` cobria área além da faixa reservada.
  - **Causa:** sem o `backdrop-filter`, o `:after` do toggle passa a se posicionar pela viewport e a regra herdada do Luma deixava a largura errada.
  - **Correção:** restringir `.nav-toggle:after` a `width: @active-nav-indent` (54px) sob `html.nav-open`.

---

## Onde o Modo Assombrado aparece

| Superfície            | Desktop                                                 | Mobile                            |
| --------------------- | ------------------------------------------------------- | --------------------------------- |
| Interruptor           | Pill no `.header.content`, à esquerda do carrinho       | Pill fixo dentro do drawer aberto |
| Fundo da loja         | Cena `back-assombrado.jpg` presa ao viewport            | Idem (sem `fixed` no iOS Safari)  |
| Header / nav / rodapé | Superfícies translúcidas sem blur                       | Idem                              |
| Hero (home)           | `banner-assombrado.jpg`                                 | Idem                              |
| Minicart              | Sidebar full-height 380px com `carrinho-assombrado.jpg` | Sidebar 100% da largura           |

---

## Evidências

### 1. Interruptor ligado/desligado no cabeçalho

> Pill padrão com o rótulo "Ativar modo assombrado" e, após o clique, "Desativar modo assombrado", ao lado do carrinho. Evidência: `17.2-toggle-off.png` e `17.2-toggle-on.png`.

- **Modo Assombrado Desativado:**

  > <img width="1919" height="957" alt="desativado" src="https://github.com/user-attachments/assets/ad1dffbd-4f20-4178-9561-5364786671a0" />

- **Modo Assombrado Ativado:**
  > <img width="1919" height="957" alt="ativado" src="https://github.com/user-attachments/assets/ac9e83fd-0eaa-4d20-8199-b28e3d8802e1" />

### 2. Fundo, hero e superfícies translúcidas

> Hero com `banner-assombrado.jpg` e cena de fundo presa ao viewport.

> <img width="920" height="398" alt="banner" src="https://github.com/user-attachments/assets/6a4d6e18-33cb-47b3-951d-b18eec17e4da" />
>
>  <img width="1919" height="957" alt="ativado" src="https://github.com/user-attachments/assets/ac9e83fd-0eaa-4d20-8199-b28e3d8802e1" />

### 3. Fundo em listagem longa

> Topo e fim da rolagem de uma listagem com varios produtos: a cena continua visível.

> <img width="1923" height="959" alt="gridzao-1" src="https://github.com/user-attachments/assets/2f9d7e8d-afa4-4bf8-a556-6380828460c5" />
>
> <img width="1923" height="959" alt="gridzao-2" src="https://github.com/user-attachments/assets/86340dcd-9968-4972-a55a-d8e3b3bfa996" />

### 4. Minicart como sidebar com mensagem dinâmica

> Sidebar full-height com `carrinho-assombrado.jpg` e mensagem de itens.

- **Cheio:**

  > <img width="430" height="953" alt="carrin" src="https://github.com/user-attachments/assets/99fce0b9-94ed-404d-a749-88572630c8bd" />

- **Vazio:**

  > <img width="430" height="953" alt="carrin-seco" src="https://github.com/user-attachments/assets/c3bad868-c445-43ea-9d97-49c7efe6664a" />

- **Mobile:**
  > <img width="334" height="718" alt="image" src="https://github.com/user-attachments/assets/a75d9fde-2178-4dd7-bd1b-184a79abd576" />

### 5. Interruptor no drawer (mobile)

> Pill fixo dentro do drawer aberto, com o mesmo rótulo de ação do desktop.

- **Desativado:**

  > <img width="335" height="722" alt="moba-desat" src="https://github.com/user-attachments/assets/1b1f105d-8962-4023-9b1f-913e546ddaa9" />

- **Ativado:**
  > <img width="335" height="722" alt="moba-ativ" src="https://github.com/user-attachments/assets/c912e4f4-af68-4d2c-942e-e5b51fa4578d" />
  > <img width="332" height="718" alt="image" src="https://github.com/user-attachments/assets/83efe7ca-cd29-446e-9e70-a2da77ae7fff" />

### 6. Mensagem dinâmica funcional em ambos modos tanto no mobile quanto no Desktop

- **Carrinho modo assombrado "Desativado" mobile**

  > <img width="354" height="210" alt="carrin-moba-desat" src="https://github.com/user-attachments/assets/3f901ef5-154d-4732-82a0-dff230ce5bd7" />

- **Carrinho modo assombrado "Desativado" Desktop**
  > <img width="561" height="190" alt="image" src="https://github.com/user-attachments/assets/335f9e97-8461-4755-b9c7-80150dc0abc1" />

### 7. Demonstração das funcionalidades do desafio em vídeo

- **Mostra os modos alternados o mini-carrinho no modo-assombrado e as funcionalidade de background, banner, entre outros.**

> [17.2.webm](https://github.com/user-attachments/assets/40aa0d15-6b5b-4135-9418-5f364f95f28f)

- **Evidencia que mostra que: "A mensagem do minicart muda conforme a quantidade de itens" e "O carrinho continua funcionando normalmente"**

> [Gravação de tela de 2026-10-07 00-55-31.webm](https://github.com/user-attachments/assets/bdb30f13-c252-4ea3-88d0-ad5b92f52e6b)

---

## CRITÉRIO DE ACEITE

- [x] O modo liga e desliga, e a escolha continua valendo ao navegar para outra página
- [x] A troca é por classe no HTML, não por recarregar a página
- [x] O minicart foi alterado por mixin, com `this._super()` preservado
- [x] A mensagem do minicart muda conforme a quantidade de itens
- [x] O carrinho continua funcionando normalmente: adicionar, remover e atualizar quantidade
- [x] README explica por que mixin e não map