# Desafio 15.2 — Formulário, configuração e exportação

## Resumo

Fechando o ciclo do módulo de avaliações (`Webjump_Gustavo`): criação/edição pelo admin com **form em UI Component**, comportamento **configurável** em Stores > Configuration e **exportação CSV/Excel XML** no grid.

- **Form em UI Component** (`webjump_gustavo_review_form`) com validação de campos obrigatórios (`author`, `rating`, produto) e seleção de produto por **modal com grid de catálogo** (busca por nome/SKU/ID, filtros e paginação);
- **Controllers** `NewAction`, `Edit`, `Save` usando sempre o **`ReviewRepositoryInterface`** (edição carrega via `getById`, persistência via `save` em try/catch com mensagem ao usuário e dados retidos via `DataPersistor`); `Delete` por linha **refatorado** de Model/Resource direto para `repository->deleteById()`;
- **Seção de configuração** `Webjump Gustavo > Avaliações` em Stores > Configuration (`enabled`, `title`, `items_per_page`, `default_sort`) com **defaults via `config.xml`**;
- **PDP mostra as avaliações aprovadas do produto** (bloco em `product.info.main` via ViewModel) e **some quando o módulo está desabilitado** na config;
- **`exportButton` nativo** no grid (CSV + Excel XML), com **ACL dedicada** `Webjump_Gustavo::review_export` aplicada em controllers próprios de export e **respeitando os filtros aplicados no grid** (o controller de export reaplica o estado do data provider);
- **Testes de integração** ampliados (`ReviewRepositoryTest` com 5 cenários + asserção do recurso `::config` no `ReviewAclTest`).

---

## Objetivo

Praticar o ciclo clássico completo do CRUD admin no Magento 2 (form UI Component + repository + config + export) e ligar a entidade de avaliações ao frontend da loja respeitando configuração — o padrão esperado de um módulo de reviews de mercado.

---

## Arquitetura

```text
Grid (15.1)                          Form (15.2)
webjump_gustavo/review/index ──► [Nova avaliação] ──► webjump_gustavo/review/newAction
└─ coluna Ações ─► [Editar] ──► webjump_gustavo/review/edit ──► webjump_gustavo/review/save (POST)
                  [Excluir] ──► webjump_gustavo/review/delete      │  ReviewRepository::save()
                                                                  │  erro → message + DataPersistor
                                                                  ▼
                                            Form: botão "Selecionar produto" abre modal com o
                                            webjump_gustavo_product_listing → ação "Selecionar" por linha

Grid toolbar: exportButton ──► webjump_gustavo/reviewExport/gridToCsv|gridToXml
                               ACL: Webjump_Gustavo::review_export (controllers estendem os do Magento_Ui
                               e sobrescrevem _isAllowed) — filtros do grid reaplicados pelo Filter do UI

Stores > Configuration > Webjump Gustavo > Avaliações (system.xml + config.xml defaults)
└─ webjump_gustavo/reviews/enabled|title|items_per_page|default_sort
   └─ Model/Config/ReviewsConfig ──► ViewModel/ProductReviews ──► PDP (product.info.main)
       se enabled = 0 → bloco não renderiza nada na loja
```

### Fluxo de uma salvar uma avaliação

1. No grid, o admin clica em **Nova avaliação** (botão em `page.actions.toolbar`) ou **Editar** na coluna Ações — ambos sob `ADMIN_RESOURCE = ::review`.
2. O layout do handle injeta `webjump_gustavo_review_form`; o `DataProvider` carrega a avaliação via collection e enriquece com o `product_label` (via `ProductRepository`) — e, em erro anterior, prefere os dados do `DataPersistor`.
3. O campo Produto (readonly) é preenchido só pelo chooser: o botão abre o modal com o `webjump_gustavo_product_listing`; ao clicar em "Selecionar", o JS (`product-select-handler` + campo `product-picker`) grava o `product_id` oculto e o rótulo no provider do form e fecha o modal.
4. No **Salvar**, o `Save` valida (produto selecionado e existente, autor preenchido, nota 1–5) e persiste via `ReviewRepositoryInterface::save()`; qualquer exceção vira mensagem amigável + dados retidos, sem vazar SQL na tela.
5. Sucesso: redirect para o grid (ou permanece no form no *Salvar e continuar editando*) com mensagem de confirmação.

---

## Estrutura de Arquivos Criados / Alterados

```text
app/code/Webjump/Gustavo/
├── Block/Adminhtml/
│   ├── AddNewReviewButton.php                 # Botão "Nova avaliação" no grid
│   └── Review/Edit/                           # Botões do form (ButtonProviderInterface)
│       ├── GenericButton.php                  # Base: getReviewId/getUrl
│       ├── BackButton.php / ResetButton.php
│       ├── DeleteButton.php                   # Só no edit, com confirm
│       ├── SaveButton.php / SaveAndContinueButton.php
├── Controller/Adminhtml/
│   ├── Review/
│   │   ├── Index.php                          # (inalterado)
│   │   ├── NewAction.php                      # Form vazio
│   │   ├── Edit.php                           # Carrega via repository; 404 com mensagem se inexistente
│   │   ├── Save.php                           # Repository::save() + DataPersistor em erro + save&continue + extractRequestData()
│   │   └── Delete.php                         # Refatorado p/ ReviewRepositoryInterface::deleteById()
│   └── ReviewExport/
│       ├── GridToCsv.php / GridToXml.php      # Estendem Magento_Ui e travam em ::review_export
├── Ui/Component/Listing/Column/
│   ├── ReviewActions.php                      # + ação Editar
│   └── ProductSelectActions.php               # Modal de produtos: ação "Selecionar" por linha
├── Model/
│   ├── Review/DataProvider.php                # DataProvider do form (collection + DataPersistor + label do produto)
│   ├── Config/ReviewsConfig.php               # Leitor da config webjump_gustavo/reviews/*
│   ├── Config/Source/Rating.php               # Opções 1..5
│   ├── Config/Source/ReviewSort.php           # created_at_desc | rating_desc
│   └── ResourceModel/Product/Grid/Collection.php  # Collection EAV (Document) p/ o listing de produtos
├── ViewModel/ProductReviews.php               # PDP: enabled/title + reviews aprovados (sort/limite da config)
├── view/adminhtml/
│   ├── layout/
│   │   ├── webjump_gustavo_review_index.xml   # + botão "Nova avaliação"
│   │   ├── webjump_gustavo_review_newaction.xml
│   │   └── webjump_gustavo_review_edit.xml
│   ├── ui_component/
│   │   ├── webjump_gustavo_review_listing.xml # + exportButton
│   │   ├── webjump_gustavo_review_form.xml    # Form (fieldset, botões, modal, campos)
│   │   └── webjump_gustavo_product_listing.xml# Grid de produtos do modal
│   └── web/
│       ├── js/form/element/product-picker.js  + template/form/element/product-picker.html
│       └── js/grid/columns/product-select-handler.js
├── etc/adminhtml/{system.xml, di.xml}, etc/config.xml
└── Test/Integration/
    ├── Acl/ReviewAclTest.php                  # + asserção do Webjump_Gustavo::config
    └── Model/ReviewRepositoryTest.php         # save/getById/deleteById/getList (5 testes)
```

### Explicação de cada componente

**Controllers (`Controller/Adminhtml/Review/`)**
- **`NewAction.php` / `Edit.php`** — páginas do form (novo/editar). O `Edit` carrega a entidade via `reviewRepository->getById()`: se não existe, mensagem "Esta avaliação não existe mais." e redirect ao grid (nada de 500).
- **`Save.php`** — o coração do requisito: monta/atualiza a entidade e persiste apenas via `ReviewRepositoryInterface` (nunca o Model/ResourceModel direto). Valida produto selecionado e existente, autor e nota antes de persistir; `LocalizedException` vira mensagem amigável; erro inesperado é logado e vira mensagem genérica; `DataPersistor` retém o que foi digitado em caso de erro. O `extractRequestData()` resolve os params: usa o array `data` quando o POST traz aninhado e, senão, filtra os params planos pelos campos da entidade (`review_id`, `product_id`, `author`, `comment`, `rating`, `is_approved`) — o form sem `dataScope` de fieldset posta tudo planado (ver Decisão 6).
- **`Delete.php`** — refatorado de `ReviewFactory` + `ResourceModel` direto para `ReviewRepositoryInterface::deleteById()`, mantendo mensagens e redirects.

**Exportação (`Controller/Adminhtml/ReviewExport/`)**
- **`GridToCsv.php` / `GridToXml.php`** — estendem os controllers do `Magento_Ui` (que reaplicam filtros/search/paginação do grid via `Filter`) e sobrescrevem apenas o `_isAllowed()` para exigir `Webjump_Gustavo::review_export` — a ACL separada desenhada no 15.1.

**Botões do form (`Block/Adminhtml/Review/Edit/`)**
- **`GenericButton.php`** — base com `getReviewId()` (param da request) e `getUrl()` (urlBuilder do admin).
- **`BackButton` / `ResetButton` / `DeleteButton` / `SaveButton` / `SaveAndContinueButton.php`** — implementam `ButtonProviderInterface` e são registrados em `<settings><buttons>` do form; o Delete só aparece em edição e dispara `deleteConfirm` (ação destrutiva pede confirmação); o Save usa `form-role: save` (botão primário) e o outro dispara o evento `saveAndContinueEdit`.

**Form e componentes UI**
- **`webjump_gustavo_review_form.xml`** — o form inteiro em XML: dataSource com `submitUrl`, fieldset (product_id hidden, product_label custom, author, comment, rating select, is_approved toggle), modal com `insertListing` do grid de produtos e o componente `product_select_handler`.
- **`Model/Review/DataProvider.php`** — alimenta o form (collection da review + enriquecimento com o label do produto) e prioriza dados do `DataPersistor` quando houve erro no save.
- **`webjump_gustavo_product_listing.xml`** — grid de catálogo do modal: colunas ID/Nome/SKU com filtros e paginação + coluna de ação "Selecionar".
- **`Model/ResourceModel/Product/Grid/Collection.php`** — collection EAV de produtos com `SearchResultInterface` e `_init(Document)` (padrão do grid do CMS), selecionando `name`/`sku`.
- **`Ui/Component/Listing/Column/ProductSelectActions.php`** — injeta em cada linha do grid do modal a ação "Selecionar" com `callback` via `uiRegistry` para o `product_select_handler`.
- **`view/adminhtml/web/js/form/element/product-picker.js` + template `product-picker.html`** — campo Produto readonly + botão "Selecionar produto" (`action-default`, abre o modal); a validação custom `validate()` exige um produto de fato selecionado (corrige o bug em que texto livre passava e estourava FK). O `modalTarget` default é o nome completo do modal e o `getModal()` tem fallback via `uiRegistry` + `console.warn`.
- **`view/adminhtml/web/js/grid/columns/product-select-handler.js`** — recebe o produto escolhido no modal com a assinatura correta `selectProduct(actionIndex, recordId, action)` (o `actions.js` chama `component[target](index, id, action)`), grava `product_id`/`product_label` no provider do form e fecha o modal.
- **`Ui/Component/Listing/Column/ReviewActions.php`** — a coluna Ações do grid agora tem **Editar** além de Excluir.
- **`Block/Adminhtml/AddNewReviewButton.php`** — o botão primário "Nova avaliação" do grid, filho de `page.actions.toolbar`.

**Configuração e frontend**
- **`etc/adminhtml/system.xml` + `etc/config.xml`** — novo grupo `reviews` (`enabled`, `title`, `items_per_page`, `default_sort`) com defaults versionados.
- **`Model/Config/ReviewsConfig.php`** — leitor tipado da config (`isEnabled`, `getTitle`, `getItemsPerPage`, `getSortField`), único ponto de leitura dos paths `webjump_gustavo/reviews/*`.
- **`Model/Config/Source/Rating.php` / `ReviewSort.php`** — option sources (nota 1–5; ordenação mais recentes/maior nota).
- **`ViewModel/ProductReviews.php` + `catalog_product_view.xml` + `product/review/list.phtml`** — bloco da PDP: está em `product.info.main`, lista só avaliações **aprovadas** com sort/limite da config e não renderiza nada com `enabled = 0`.

**Testes**
- **`Test/Integration/Model/ReviewRepositoryTest.php`** — 5 cenários cobrindo o contrato usado pelos controllers (create/update, exceção de inexistente, deleteById, getList filtrado).
- **`Test/Integration/Acl/ReviewAclTest.php`** — estendido com a asserção do recurso `Webjump_Gustavo::config`.

---

## Decisões de Implementação e Justificativas

### 1. Seleção de produto: chooser readonly + grid em modal
Primeira versão teve autocomplete AJAX por digitação; o plugin de input custom no UI form se mostrou instável (layout quebrado com o wrapper padrão, sugestões não exibidas). Migrado para o padrão clássico do admin: campo **readonly** mostrando o produto escolhido + botão "Selecionar produto" abrindo modal com o `webjump_gustavo_product_listing` (busca/filtros por nome/SKU/ID e paginação). A coluna "Selecionar" escreve `product_id`/`product_label` no form via `uiRegistry` e fecha o modal. O campo (`product_label`) tem validação **client-side custom** que exige um produto de fato selecionado.

No caminho, foram pescados dois bugs que impediam a seleção de aparecer funcional no form:
- **Classe de botão inexistente no admin** — o template usava `action-basic`, que não existe no tema admin do Magento 2.4.8: o "botão" renderizava como texto solto. Corrigido para `action-default`, padrão do backend.
- **Assinatura do callback desalinhada** — o `actions.js` do grid invoca o callback de objeto como `component[target](actionIndex, recordId, action)`. O handler declarava `(target, actionIndex, recordId)`, então `findItem(recordId)` recebia o índice da ação (ou o próprio objeto `action`) em vez do `entity_id` da linha — a seleção nunca achava o item. Corrigido para `(actionIndex, recordId, action)`.
- Aproveitou-se para trocar os lookups de `registry` por **nomes completos confirmados** no layout renderizado (`webjump_gustavo_review_form.webjump_gustavo_review_form.product_select_modal` etc.) nos defaults JS, no `modalTarget` do XML e no `provider` do callback em `ProductSelectActions.php`, com fallback no `getModal()` do picker (`console.warn` se nada resolver).

### 2. Save via `ReviewRepositoryInterface` com DataPersistor e validação amigável
`Save` carrega por `getById` (edição) ou cria via `ReviewInterfaceFactory` (novo) e chama `repository->save()`. Antes de persistir, valida no servidor: produto selecionado **e existente**, autor preenchido e nota entre 1–5 — mensagens amigáveis em português (nunca SQL cru na tela; erros inesperados são logados e viram mensagem genérica). `LocalizedException` vira `addErrorMessage` + `DataPersistor->set()` e redirect de volta ao form **sem perder o que foi digitado**; sucesso limpa o persistor. Delete por linha foi migrado do ResourceModel direto para `deleteById()` por consistência.

### 3. Config: grupo `reviews` na seção `webjump_gustavo`
Quatro campos (`enabled`, `title`, `items_per_page`, `default_sort`), todos com default no `config.xml` e `canRestore`. O consumo fica centralizado em `Model/Config/ReviewsConfig`, lido pelo `ViewModel/ProductReviews` da PDP — se `enabled = 0`, o bloco não renderiza nada na loja (ver evidência).

### 4. Exportação nativa do `Magento_Ui` com ACL própria
O `<exportButton>` nativo oferece CSV e Excel XML e reaplica **filtros/search/markbook** do grid (o controller `Filter` do UI reconstitui o data provider com o estado da URL). Como o `_isAllowed()` do core só conhece o `aclResource` do dataSource (`::review`), criei `ReviewExport\GridToCsv|GridToXml` estendendo os do core e sobrescrevendo `_isAllowed()` para checar `::review_export` — a ACL separada desenhada no 15.1 agora é de fato exigida.

### 5. Tudo visível no admin dentro do mesmo ACL `::review`
`NewAction`, `Edit`, `Save`, `Delete` e o grid de produtos do modal declaram `ADMIN_RESOURCE = ::review` (só export usa `::review_export`) — ver dados na empresa ≠ levar dados para fora.

### 6. Robustez: bugs de UX/validação encontrados e corrigidos no caminho
Na primeira versão do form, era possível digitar um texto qualquer no campo Produto e salvar: o `product_id` oculto ficava vazio e o INSERT estourava FK, com a mensagem de SQL aparecendo crua ao usuário. Corrigido em duas camadas: (1) no JS, digitar algo diferente da opção escolhida limpa a seleção oculta e o `validate()` custom exige produto selecionado; (2) no `Save`, validação prévia de produto existente + exceções genéricas logadas (só a mensagem amigável chega à tela). Vale registrar duas regressões também pescadas no mesmo ciclo: uma subscrição Knockout que **apagava o texto** do campo ao digitar (fluxo que gerou a migração do autocomplete para o chooser modal) e os **params planos do form**.

O bug dos params era sutil e o diagnóstico inicial (corrigido neste documento) estava invertido: como o fieldset `general` não declara `dataScope`, cada campo exporta direto para `data.<nome>` do provider (`author`, `rating`, `product_id`...) e o `mageUtils.serialize` (`lib/web/mage/utils/objects.js`) gera **inputs de nome top-level** no POST — não `data[...]` aninhado. O `Save` lia apenas `getParam('data')`, que vinha sempre vazio, caía no `if (!$data)` e redirecionava **silenciosamente** para o grid: a avaliação nunca era persistida e nenhuma mensagem chegava à tela. Corrigido com `extractRequestData()` no `Save`, que usa o array `data` quando presente e, senão, faz `array_intersect_key` dos params com os campos da entidade (`review_id`, `product_id`, `author`, `comment`, `rating`, `is_approved`) — cobrindo ambos os formatos de POST, validado com POST real (3 tentativas no browser retornando 302 + grid sem gravar).

---

## Evidências

> Coletadas contra o ambiente local (docker compose, `https://magento.test`).

### 1. Form: criação, edição e validação

- `webjump_gustavo/review/newAction/` renderiza o form com os campos, botões (Voltar / Redefinir / Salvar e continuar / Salvar; Excluir só no edit) e o seletor de produto (botão do modal);
- `Save` sem produto/autor/nota é barrado pela validação client-side (`required-entry`);
- `Save` com dados válidos conecta ao repositório e persiste: o POST `/review/save/` devolve 302 para o index e a linha aparece no grid com os dados digitados — **verificado de ponta a ponta no browser** após o fix dos params planos (antes, o save redirecionava silenciosamente sem gravar nada);
- Erros do repository (ex.: produto inexistente violando FK) voltam como mensagem de erro e o form retém os dados (DataPersistor);
- `Edit` com `review_id` inexistente redireciona ao grid com "Esta avaliação não existe mais.".

> <img width="1777" height="858" alt="image" src="https://github.com/user-attachments/assets/2c492e02-16a8-43b8-a6de-a88c6c393e20" />
> <img width="1777" height="858" alt="image" src="https://github.com/user-attachments/assets/16aac2c4-2cce-4540-bc0a-cbeb38c8251f" />

### 2. Seleção de produto

Modal com o grid de produtos (`webjump_gustavo_product_listing`: ID, Nome, SKU, ação "Selecionar", busca e paginação) — dados validados via `mui/index/render`: itens trazem `name`/`sku` e filtro por nome (`joust`) e SKU (`CHAP`) funcionando, com o callback "Selecionar" preenchendo `id/name/sku` no form.

> <img width="1367" height="167" alt="image" src="https://github.com/user-attachments/assets/25763ba7-b9d1-408e-8adc-67b6b3c85cdc" />
> <img width="1722" height="858" alt="image" src="https://github.com/user-attachments/assets/bd24b8f7-17b5-42a4-a575-50de07e2ce40" />
> <img width="1367" height="167" alt="image" src="https://github.com/user-attachments/assets/4cffec97-5be8-4c80-905a-6310c7cfc58f" />

### 3. Configuração em Stores > Configuration

Grupo `Webjump Gustavo > Avaliações` com `enabled`, `title`, `items_per_page`, `default_sort`; defaults aplicados via `config.xml`:

```bash
$ bin/magento config:show webjump_gustavo/reviews/enabled   # 1
$ bin/magento config:show webjump_gustavo/reviews/title     # Avaliações dos clientes
$ bin/magento config:show webjump_gustavo/reviews/items_per_page  # 10
$ bin/magento config:show webjump_gustavo/reviews/default_sort    # created_at_desc
```

> <img width="2509" height="945" alt="avaliacoes" src="https://github.com/user-attachments/assets/e5e36e2b-4592-467d-bf78-1c2ac8867ad4" />

### 4. Módulo desabilitado não exibe na loja

PDP `/chapeu-de-couro.html` (produto com avaliação aprovada):

```text
enabled=1  -> bloco "Avaliações dos clientes" presente (True)
enabled=0  -> bloco ausente (False)   [config:set webjump_gustavo/reviews/enabled 0 + cache:clean]
enabled=1  -> bloco de volta (True)   [restaurado]
```
> * **Habilitado**
> <img width="1589" height="1084" alt="chapeu" src="https://github.com/user-attachments/assets/53198261-fd4e-4488-af08-c0df19c0a20b" />

> * **Desabilitado**
> <img width="1191" height="808" alt="image" src="https://github.com/user-attachments/assets/e3d66f20-87d8-4953-a302-8247ba34c206" />


### 5. Exportação CSV e Excel XML + respeito a filtro

Botão Exportar no grid chama `reviewExport/gridToCsv|gridToXml` (URLs extraídas da página do grid no smoke test):

```text
csv: 200 application/octet-stream attachment; filename="export.csv"
xml: 200 application/octet-stream attachment; filename="export.xml"
```

Conteúdo CSV **com filtro `author=juliana` aplicado** (apenas a linha filtrada):

```csv
ID,"ID do Produto",Autor,Comentário,Nota,Aprovado,"Criado em"
13,1,"Juliana Castro","Péssima experiência, veio com risco no couro.",1,No,"2026-09-23 13:46:29"
```

> <img width="2595" height="1447" alt="EXPORT" src="https://github.com/user-attachments/assets/9262ac74-889c-4d68-a815-4272b50837df" />


### 6. Testes automatizados

```bash
docker compose exec -T phpfpm bash -c 'cd /var/www/html/dev/tests/integration && ../../../vendor/bin/phpunit -c phpunit.xml.dist /var/www/html/app/code/Webjump/Gustavo/Test/Integration/'
```

```text
OK (8 tests, 20 assertions)
```

> <img width="1432" height="423" alt="Captura de tela de 2026-09-24 20-46-54" src="https://github.com/user-attachments/assets/309a4094-593d-4e12-9da1-48c92370b83c" />


---

## CRITÉRIO DE ACEITE

- [x] Criar e editar pelo admin funciona, com validação de campo obrigatório
- [x] O Save usa o repository e trata erro devolvendo mensagem ao usuário
- [x] Existe seção em Stores > Configuration, com valores padrão funcionando
- [x] O módulo respeita a configuração (se desabilitado, não exibe na loja)
- [x] A exportação em CSV e Excel XML funciona
- [x] A exportação respeita os filtros aplicados no grid