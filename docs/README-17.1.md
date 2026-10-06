# Desafio 17.1 - Contagem regressiva e selo assombrado

## Resumo

Contador regressivo da campanha "Noite Assombrada" na home e na PDP, atualizado a cada segundo sem recarregar a página, com a configuração entregue por `x-magento-init`. O selo de produto deixa de ser boolean e vira um seletor de três opções (`Nenhum` / `Selo Sustentável` / `Produto Assombrado`), preservando os produtos que já tinham o selo sustentável. Depois de 31/10 o contador cede lugar à mensagem de encerramento, e um toggle no admin remove o bloco.

---

## Objetivo

Entregar a contagem regressiva como elemento vivo — e não como texto estático gerado no servidor — e ampliar o selo de produto existente para suportar a marca "Produto Assombrado", sem quebrar nenhum ponto onde o selo já aparecia e sem deixar marcação para produtos sem atributo.

---

## Arquitetura

```text
Atributo de produto
  ├─ ProductSelo (Source Model)   0 = Nenhum | 1 = Sustentável | 2 = Assombrado
  └─ ConvertSeloSustentavelToProductSelo (patch de dados, reversível)

Selo (badge)  ── mesmo par de templates do desafio 14.1
  ├─ PDP .............. product/view/selo.phtml
  └─ Card ............. product/badge/selo_sustentavel.phtml
       └─ AddProductSeloToListCollection (observer)
            evento catalog_block_product_list_collection → addAttributeToSelect()

Contador
  ├─ system.xml + config.xml   webjump_gustavo/halloween_theme/countdown_enabled (default 1)
  ├─ HalloweenCountdown (ViewModel)   isEnabled() · getTargetTimestamp() · textos
  ├─ halloween_countdown.phtml        return antes de tudo se isEnabled() == false
  │     <span data-bind="text: message"> + <script type="text/x-magento-init">
  └─ countdown.js (RequireJS)         now (observable)
                                     → remainingSeconds / isEnded / message (ko.computed)
                                       setInterval(_tick.bind(this), 1000)
                                       registry.set('halloweenCountdown', this)

Posicionamento
  ├─ Home ... Webjump_Gustavo/layout/cms_index_index.xml  (após halloween.hero.banner)
  └─ PDP  ... catalog_product_view.xml                    (product.info.main, antes do preço)
```

---

## Estrutura de Arquivos

Novos:

```text
app/code/Webjump/Gustavo/
├── Model/Attribute/Source/ProductSelo.php                    # 0 Nenhum | 1 Sustentável | 2 Assombrado
├── Observer/AddProductSeloToListCollection.php                # selo entra no SELECT das listagens
├── Setup/Patch/Data/ConvertSeloSustentavelToProductSelo.php   # Boolean -> select, com revert()
├── ViewModel/HalloweenCountdown.php                           # toggle, data-alvo e textos
├── view/frontend/templates/halloween_countdown.phtml          # markup + x-magento-init
└── view/frontend/web/js/countdown.js                         # componente Knockout

app/design/frontend/Webjump/halloween/web/css/source/extend/_campaign-countdown.less
```

Alterados:

```text
app/code/Webjump/Gustavo/
├── ViewModel/ProductSeloSustentavel.php            # getSeloValue() + normalize()
├── etc/adminhtml/system.xml                        # campo countdown_enabled
├── etc/config.xml                                  # default do contador
├── etc/events.xml                                  # observer da coleção de listagem
├── view/frontend/layout/catalog_product_view.xml   # contador na PDP
├── view/frontend/templates/product/badge/selo_sustentavel.phtml
├── view/frontend/templates/product/view/selo.phtml # dois selos; return se vazio
└── view/frontend/web/css/source/_module.less       # estilo do selo Assombrado

app/design/frontend/Webjump/halloween/
├── Webjump_Gustavo/layout/cms_index_index.xml      # contador após o hero
├── i18n/en_US.csv                                 # traduções do contador e do selo
└── web/css/source/_extend.less                    # importa o parcial do contador
```

Nenhum arquivo em `vendor/` ou do tema Luma foi alterado.

---

## Decisões de Implementação e Justificativas

### 1. Boolean -> select de opções, sem migração de dados

Um boolean não comporta uma terceira marca, e um atributo novo exigiria um segundo badge na PDP e no card. Converter o atributo in place preserva o ID (`157`), o histórico e as regras de exibição, e o `1` continua significando "Sustentável". `EavSetup::addAttribute` é idempotente: atualiza o atributo em vez de recriá-lo, então os valores em `catalog_product_entity_int` sobreviveram intactos.

### 2. Data-alva em horário da loja, sem rollover

`DateTimeImmutable` no fuso de `TimezoneInterface::getConfigTimezone()` garante que "31/10 23:59:59" seja o fim da campanha **para o cliente**, e não para o UTC do servidor. O ano é o corrente de propósito: em dezembro a campanha já aparece como encerrada, sem lógica de virada.

### 3. Contagem no navegador, dados no servidor

O PHP entrega o instante final e os textos; a contagem acontece em `setInterval` no cliente. Um texto gerado no servidor ("faltam 26 dias") ficaria congelado entre F5s. A configuração viaja pelo `x-magento-init`, que é o ponto de integração nativo entre template e componente RequireJS — nada de atributos `data-*` lidos à mão.

### 4. `uiRegistry` + `scope`, e não `data-mage-init`

O componente se registra com `registry.set('halloweenCountdown', this)` e o markup aponta para o mesmo nome. Com `data-mage-init`, o componente não teria um lugar canônico no registry, o que impede reuso e inspeção.

### 5. `json_encode` com `JSON_HEX_*` no `x-magento-init`

O payload é montado como array e serializado com `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`. As traduções contêm acentos e o bloco é um `<script>`: sem essas flags, uma aspa no texto traduzido quebraria o JSON.

### 6. Toggle com `return` antes de qualquer markup

O bloco só existe se `countdown_enabled` estiver ligado. Renderizar o container e esconder com CSS deixaria marcação morta na página e manteria o componente instanciado à toa; com o `return`, desligar o toggle **remove** o elemento.

### 7. Estilo próprio para o Assombrado, sem tocar no Sustentável

Card e PDP compartilham a estrutura `__icon` + `__label`, mas cada estado tem sua classe e suas regras. O estilo do Sustentável não foi alterado, então a marca existente não regride. O selo Assombrado é persistente — é marca de produto, não aviso de campanha.

### 8. Observer da coleção em vez de plugin no bloco

`ListProduct::initializeProductCollection()` dispara `catalog_block_product_list_collection` **antes** do `load()`, e esse é o ponto de extensão oficial do core para ampliar o `SELECT` de uma listagem. O observer é inerte nos blocos que já carregam o produto inteiro (related, upsell, cross-sell, widget).

---

## Problemas Encontrados e Correções

### 1. Contador travado: `this.now is not a function`

- **Sintoma:** a faixa aparecia correta na primeira renderização e ficava congelada em `9 segundos`, com `pageerror` a cada segundo.
- **Causa:** `setInterval(this._tick, …)` passa a função desvinculada; o timer a chama com `this` = `window`, e não ao componente. Os `ko.computed` não sofriam do mesmo mal porque recebem o contexto no segundo argumento.
- **Correção:** `setInterval(this._tick.bind(this), …)`.

### 2. O selo nunca aparecia nas listagens (bug pré-existente)

- **Sintoma:** zero ocorrência de `selo` no HTML da PLP, mesmo em produtos com `selo = 1`. A PDP funcionava normalmente.
- **Causa:** a coleção de listagem não carregava o atributo. Um probe no template mostrou o produto chegando, mas sem a chave: `sku=WS09 | tem_chave=NAO | valor=NULL`. `Layer\Category::getProductCollection()` faz `SELECT` apenas dos atributos da listagem, e `selo_sustentavel` não está entre eles — o `return` do template abortava para **todos** os produtos.
- **Correção:** observer em `catalog_block_product_list_collection` com `addAttributeToSelect`.


### 3. `x-magento-init` renderizando objeto vazio

- **Sintoma:** o bloco existia no HTML, mas o componente recebia `{}` e a faixa ficava em branco.
- **Causa:** o template chamava `$block->getJsonData()`, que não existe em `Magento\Framework\View\Element\Template`.
- **Correção:** `json_encode` com `JSON_HEX_*`.

### 4. HTTP 500 na home após criar a ViewModel

- **Sintoma:** a home caía com 500 assim que o contador entrava no layout.
- **Causa:** `DateTimeImmutable::__construct()` do PHP 8 exige `?DateTimeZone`, e recebia a **string** de `getConfigTimezone()`.
- **Correção:** `new \DateTimeZone($this->timezone->getConfigTimezone())` antes de construir a data.

### 5. Estático obsoleto em modo developer

- **Sintoma:** após remover `pub/static/frontend/Webjump/halloween`, o `requirejs-config.js` do tema respondeu 404 e a página ficou sem RequireJS — nenhum componente KO inicializou.
- **Causa:** em modo developer os arquivos são materializados por cópia no `pub/static`, que é volume do Docker.
- **Correção:** `setup:static-content:deploy -f --theme=Webjump/halloween` e `cache:flush`. Acessar a página com `curl` antes de medir: a primeira requisição é a que dispara a recompilação.

### 6. Falso negativo ao religar o toggle

- **Sintoma:** com `countdown_enabled = 1` restaurado, a home continuava sem contador.
- **Causa:** `cache:flush config` limpa só o cache de configuração; a página era servida pelo FPC com o HTML anterior.
- **Correção:** `cache:flush` completo antes de medir.

---

## Onde o selo aparece

| # | Superfície | Template | Layout que ativa |
|---|---|---|---|
| 1 | **PDP** | `product/view/selo.phtml` | `catalog_product_view.xml` (`product.info.main`) |
| 2 | **Categoria e busca** | `product/list.phtml` | `catalog_category_view.xml` / `catalogsearch_result_index.xml` |
| 3 | **Related / Upsell / Cross-sell** | `product/list/items.phtml` | `catalog_product_view.xml` (3 blocos) |
| 4 | **Widgets de vitrine** | `product/widget/content/grid.phtml` | `default.xml` (`catalogwidget.product.list`) |

**Fora do escopo (documentado):** minicart, carrinho, checkout e e-mails. Nesses pontos o produto já foi escolhido; selo é ferramenta de descoberta, não de confirmação.

**Riscos conhecidos:** (a) os três templates sobrescritos do core são sensíveis a upgrade — a alteração é só a injeção do badge, já existente no 14.1; (b) cada listagem passa a carregar uma coluna `int` a mais; (c) os rótulos do seletor estão em português dentro de `__()`, coerente com o rótulo do atributo ("Selo do Produto"), mas o admin está em `en_US`.

---

## Evidências

### 1. Contador na home

> Atualiza sozinho a cada segundo, sem F5: ex.:`...21 minutos e 19 segundos`.

> [Gravação de tela de 2026-10-06 09-44-14.webm](https://github.com/user-attachments/assets/67b44f0c-aa8b-4ae7-b687-4af34638aa24)

### 2. Contador também na PDP

> No `product.info.main`, depois do nome e antes do preço.

> <img width="1319" height="553" alt="image" src="https://github.com/user-attachments/assets/de8eae54-edc6-4897-9dd1-f3737e463b6a" />


### 3. Selo Assombrado na PDP

> Produto com `selo = 2` exibe "Produto Assombrado".

> <img width="1231" height="640" alt="image" src="https://github.com/user-attachments/assets/54c6d07b-a96e-4ff1-9427-c98ba7b7b5c2" />

### 4. Selo Sustentável preservado na PDP

> Produto antigo, com `selo = 1`, continua exibindo "Produto Sustentável" no estilo de antes.

> <img width="1319" height="553" alt="image" src="https://github.com/user-attachments/assets/de8eae54-edc6-4897-9dd1-f3737e463b6a" />

### 5. Selo na listagem

> Categoria `gear/bags.html`: 2 cards Assombrado dentro da área da foto, o resto do cards sem selo algum.

> <img width="1231" height="699" alt="image" src="https://github.com/user-attachments/assets/75a477f0-d18c-482e-9399-82de96f93cde" />

### 6. Produto sem o atributo preenchido

> Zero elementos de selo no DOM — não é badge escondido por CSS, é ausência de marcação.

> <img width="703" height="205" alt="image" src="https://github.com/user-attachments/assets/aa10b423-75f4-47d7-bbde-397ec02eee78" />
> <img width="1078" height="701" alt="image" src="https://github.com/user-attachments/assets/a8798e78-1185-4181-9bda-86248e09ccc7" />


### 7. Depois da data final

> A faixa troca a contagem pela mensagem de encerramento, em vez de mostrar números negativos.

> <img width="1240" height="48" alt="17 1-08-campanha-encerrada" src="https://github.com/user-attachments/assets/0c3b8acc-6321-443c-adf6-00ce77ae3f1e" />

### 8. Toggle do admin desligado

> <img width="997" height="144" alt="image" src="https://github.com/user-attachments/assets/3ae36598-a539-4329-aacb-f99a23277bd8" />


> `countdown_enabled = 0` → a faixa não existe na home e na PDP; o selo continua renderizando.

> <img width="1802" height="877" alt="image" src="https://github.com/user-attachments/assets/55721dfc-719c-45b2-bea9-d1fc231fd501" />

### 9. Git status evidenciando nenhuma modificação na vendor

> <img width="1208" height="679" alt="image" src="https://github.com/user-attachments/assets/a1cf7893-ec23-401c-8f1c-693b416da703" />


---

### CRITÉRIO DE ACEITE

- [x] O contador atualiza sozinho, sem recarregar a página
- [x] Depois da data final, ele mostra a mensagem de encerramento em vez de números negativos
- [x] O componente foi inicializado por x-magento-init ou data-mage-init
- [x] O selo aparece nos produtos marcados, na listagem e no detalhe
- [x] Produto sem o atributo preenchido não gera erro nem espaço vazio
- [x] Os textos do contador e do selo passam pelo CSV de tradução
