# Desafio 15.1 Grid de administração completo

## Resumo

Neste desafio, a entidade de avaliações criada no 14.2 (`webjump_gustavo_review`) ganhou uma **interface completa no admin**, para que o time de atendimento encontre, filtre e aja sobre as avaliações **sem pedir nada ao time técnico**:

- **Rota admin própria** (`webjump_gustavo/review/index`) com controller que define `ADMIN_RESOURCE = Webjump_Gustavo::review`;
- **ACL granular** (`etc/acl.xml`): um recurso para a tela/moderação (`::review`) e outro **separado para exportação** (`::review_export`) — já preparado para o 15.2;
- **Menu no admin**: `Webjump > Avaliações`;
- **Grid em UI Component** com filtros por texto, faixa numérica, data e select; ordenação; paginação; bookmarks; coluna **"Produto"** com o nome vindo de join; três ações em massa (Aprovar, Reprovar, Excluir — com confirmação na destrutiva) e coluna de ações por linha;
- **Teste de integração automatizado** cobrindo a árvore de ACL (`OK (3 tests, 7 assertions)`).

---

## Objetivo

Praticar, de ponta a ponta, o caminho clássico de um CRUD admin no Magento 2: **rota → controller → ACL → menu → layout → UI Component → Collection**, entendendo como cada camada se liga à próxima e onde a permissão é verificada (no controller, não no menu — o menu só esconde o link).

---

## Arquitetura

```text
Menu "Webjump > Avaliações" (menu.xml, resource = ::review)
  └─ action: webjump_gustavo/review/index
       └─ routes.xml (frontName webjump_gustavo → Webjump_Gustavo/Controller/Adminhtml)
            └─ Controller\Adminhtml\Review\Index
                 │  ADMIN_RESOURCE = Webjump_Gustavo::review   ← a proteção real vive AQUI
                 │  (Backend App\Action::_isAllowed() verifica o recurso; menu só esconde o link)
                 └─ PageFactory → layout webjump_gustavo_review_index.xml
                      └─ <uiComponent name="webjump_gustavo_review_listing"/>
                           └─ webjump_gustavo_review_listing.xml
                                ├─ dataSource → DataProvider (framework)
                                │     └─ CollectionFactory resolve -> Grid\Collection (di.xml adminhtml)
                                │           └─ tabela webjump_gustavo_review
                                ├─ listingToolbar: bookmark, columnsControls, filters, paging, massaction
                                └─ columns: ids, review_id, product_id, product_name (join), author,
                                            comment, rating, is_approved, created_at, actions

Mass actions (controllers MassApprove/MassDisapprove/MassDelete) e ação por linha (Delete)
  └─ recebem os IDs via Filter + CollectionFactory (ou review_id por param), executam
     e redirecionam ao grid com mensagem — todos sob o mesmo ADMIN_RESOURCE
```

### Fluxo de uma requisição

1. O admin clica no menu: o Magento checa `Webjump_Gustavo::review` — sem permissão, o item nem aparece.
2. O router admin resolve `webjump_gustavo/review/index` → `Controller/Adminhtml/Review/Index::execute()`.
3. **Antes** do execute, o `Backend\App\Action` verifica `ADMIN_RESOURCE` no ACL — sem o recurso, responde "Access Denied" **mesmo que o usuário digite a URL na mão**.
4. O layout injeta o UI Component; no carregamento, o JS do grid chama `mui/index/render`, que aciona o `DataProvider`, que busca dados na `Grid\Collection` registrada no `di.xml` adminhtml.
5. Filtros/ordenação/paginação/mass actions são aplicados sobre essa mesma collection.

---

## Estrutura de Arquivos

```text
app/code/Webjump/Gustavo/
├── Controller/Adminhtml/Review/
│   ├── Index.php                  # Página do grid (PageFactory + setActiveMenu + título)
│   ├── Delete.php                 # Exclusão por linha (chamada pela coluna Ações)
│   ├── MassApprove.php            # Aprovar vários de uma vez (is_approved = 1)
│   ├── MassDisapprove.php         # Reprovar vários de uma vez (is_approved = 0)
│   └── MassDelete.php             # Excluir vários (ação destrutiva)
├── Model/ResourceModel/Review/Grid/
│   └── Collection.php             # Collection dedicada ao grid (SearchResultInterface + join do nome do produto)
├── Ui/Component/Listing/Column/
│   └── ReviewActions.php          # Coluna "Ações" (link Excluir + confirmação)
├── Test/Integration/Acl/
│   └── ReviewAclTest.php          # Testes de ACL (3 testes, 7 asserções)
├── etc/
│   ├── acl.xml                    # ::webjump → ::review / ::review_export
│   └── adminhtml/
│       ├── routes.xml             # frontName webjump_gustavo
│       ├── menu.xml               # Webjump > Avaliações
│       └── di.xml                 # Registra o data source no CollectionFactory
└── view/adminhtml/
    ├── layout/webjump_gustavo_review_index.xml    # Injeta o uiComponent na página
    └── ui_component/webjump_gustavo_review_listing.xml  # Descreve o grid inteiro
```

---

## Explicação de cada componente

**`etc/acl.xml`** — a árvore de permissões que aparece em *System > Permissions > User Roles*.
Cria `Webjump_Gustavo::webjump` (agrupador), `::review` (ver/grid/moderar) e `::review_export` (exportar, isolado de propósito). Sem esses nós, nenhum botão do controller importa — a autenticação do admin usa essa árvore.

**`etc/adminhtml/routes.xml`** — registra o frontName `webjump_gustavo` na área admin.
É o que faz `admin/webjump_gustavo/review/index` chegar em `Controller/Adminhtml/Review/Index.php`.

**`etc/adminhtml/menu.xml`** — os dois nós do menu.
O pai `Webjump` cria a seção; o filho `Avaliações` aponta a `action` e declara `resource="Webjump_Gustavo::review"` — é **esse** atributo que esconde o item para usuários sem a permissão.

**`Controller/Adminhtml/Review/Index.php`** — a porta de entrada e a proteção real.
Herda de `Backend\App\Action`; a constante `ADMIN_RESOURCE` é verificada automaticamente pelo `_isAllowed()` do backend **antes** de qualquer lógica. O `execute()` só monta a página com `PageFactory`, marca o menu ativo (espelha o item no menu lateral) e define o título.

**`view/adminhtml/layout/webjump_gustavo_review_index.xml`** — liga a rota ao grid.
O handle segue a convenção `{routerId}_{controller}_{action}`; dentro dele, `<uiComponent name="..."/>` injeta o grid na página.

**`view/adminhtml/ui_component/webjump_gustavo_review_listing.xml`** — o grid inteiro, descrito em XML.
- `<dataSource>` declara o provider JS, a URL de atualização (`mui/index/render`) e o `aclResource` (tripla proteção: o endpoint de dados também exige a permissão);
- `<listingToolbar>` traz bookmark (filtros salvos por usuário), controle de colunas, filtros, paginação e as **três mass actions** — só a `delete` tem `<confirm>`, porque é destrutiva;
- `<columns>` define cada coluna com seu filtro: `textRange` (ID, ID do Produto, nota), `text` (Produto — nome vindo do join — e autor), `select` com `Yesno` (aprovado), `dateRange` (criado em) e a coluna de ações. `comment` fica sem filtro de propósito. O `sorting>desc` em `created_at` define a ordenação padrão.

**`Model/ResourceModel/Review/Grid/Collection.php`** — a collection vista pelo grid.
Estende a collection do 14.2 e **implementa `SearchResultInterface`**, exigência do `DataProvider` do UI Component (sem isso, o grid quebra com `TypeError`). Usa `Document` como model dos itens — padrão idêntico ao `Magento\Cms\...\Page\Grid\Collection`.

Em `_initSelect()`, além do `from` na tabela principal, é feito um `joinLeft` em `catalog_product_entity_varchar` (alias `product_name`) para trazer o **nome do produto** (`value AS product_name`). O join casa `product_name.entity_id = main_table.product_id` com `store_id = 0` (loja default — regra dos grids admin, 1:1 sem multiplicar linhas) e resolve o `attribute_id` do atributo `name` por **subquery** em `eav_entity_type`/`eav_attribute` — sem hard-code de ID, então vale em qualquer instalação. Por fim, `addFilterToMap('product_name', 'product_name.value')` mapeia o campo para o core aplicar o filtro `text` e a ordenação na coluna do join.

**`etc/adminhtml/di.xml`** — o "plug" entre o grid e a collection.
Registra `webjump_gustavo_review_listing_data_source` no `CollectionFactory` do framework apontando para a `Grid\Collection` — é assim que o DataProvider sabe de onde ler.

**`Controller/Adminhtml/Review/MassApprove|MassDisapprove|MassDelete.php`** — as ações em massa.
Cada uma recebe a seleção via `Magento\Ui\Component\MassAction\Filter` (que transforma os checkboxes em filtro de collection), itera os modelos e grava/remove. Terminam com mensagem de sucesso e redirect para o grid. Todas declaram `ADMIN_RESOURCE = ::review` e `HttpPostActionInterface`.

**`Ui/Component/Listing/Column/ReviewActions.php`** — a coluna "Ações".
Em `prepareDataSource()`, injeta em cada linha o link "Excluir" com método POST e modal de confirmação (`confirm.title/message`) — mesmo padrão da `PageActions` do CMS.

**`Test/Integration/Acl/ReviewAclTest.php`** — a prova automatizada de ACL.
Garante: (1) os três recursos existem na árvore; (2) uma role **sem** o recurso não é autorizada; (3) uma role **com** ele é autorizada — e continua **sem** `::review_export`, provando a separação.

---

## Decisões de Implementação e Justificativas

### 1. Menu próprio "Webjump > Avaliações"
Em vez de pendurar em Marketing/Stores: cria namespace próprio, bate com a hierarquia do ACL e escala com a trilha (selo, avaliações, configuração no 15.2).

### 2. Coluna "Produto" com o nome via join (mantendo `product_id`)
O grid mostra o **ID do Produto** (filtro `textRange`) e o **nome do produto** (filtro `text`). O nome chega por um `joinLeft` em `catalog_product_entity_varchar` na `Grid\Collection`, olhando a **loja default** (`store_id = 0`) — 1:1, sem multiplicar linhas. O `attribute_id` do atributo `name` é resolvido por subquery em `eav_entity_type`/`eav_attribute`, então não fica hard-coded para a instalação. O custo do join é mínimo e o ganho é legibilidade: o time de atendimento reconhece o produto pelo nome. O `product_id` continua exposto porque é a referência estável para suporte — e a coluna já deixa pronto o caminho para o export (15.2) e o desafio 15.3, que exigem o nome do produto.

### 3. Três mass actions, mas só Excluir com confirmação
Aprovar/Reprovar cobrem o fluxo real de moderação (inclusive desfazer). Só a exclusão é destrutiva, então só ela ganha `<confirm>` — como pede o critério.

### 4. `Grid\Collection` dedicada com `SearchResultInterface`
O DataProvider **exige** essa interface; a collection "crua" quebra o grid. A subclasse dedicada também isola o grid de filtros/bookmarks e segue o padrão do core.

### 5. Permissão única para moderar, separada para exportar
Grid e moderação sob `::review`; exportação sob `::review_export` desde já (implementada no 15.2). Em projeto real, ver dados ≠ levar dados para fora da empresa.

### 6. Correção de robustez no patch `AddSampleReviews` (encontrada no caminho)
O patch do 14.2 quebrava `setup:install` em base limpa (fallback para IDs de produto inexistentes violava a FK). Agora ele filtra os IDs existentes e pula a inserção silenciosamente quando não há produtos — comportamento na loja real inalterado, e o ambiente de testes de integração instala sem erro.

---

## Evidências

### 1. Menu "Webjump > Avaliações" no admin

> **Menu lateral do admin com a seção "Webjump" e o item "Avaliações", levando ao grid**
>
> <img width="1099" height="741" alt="admin" src="https://github.com/user-attachments/assets/2b0bbab9-d0e4-4dcc-ba5a-94531ef55857" />


### 2. Grid populado

> **Grid "Avaliações" listando os registros da collection, com as colunas (ID, ID do Produto, Autor, Comentário, Nota, Aprovado, Criado em, Ações, Produtos)**
>
>
> <img width="1775" height="664" alt="Captura de tela de 2026-09-25 02-46-38" src="https://github.com/user-attachments/assets/62f615b4-8920-4d81-b50d-6208a3c3cdfa" />



### 3. Filtros funcionando

> <img width="1757" height="473" alt="image" src="https://github.com/user-attachments/assets/8c7726db-1b85-4187-9e4f-1a54b6f7e774" />


> **Filtro de texto aplicado na coluna "Autor" (ex.: buscando "juliana") com resultado filtrado**
>
> <img width="1757" height="473" alt="image" src="https://github.com/user-attachments/assets/ac3d23e0-5492-40a9-b4c4-38bfe1683560" />

> **Filtro de faixa numérica aplicado na coluna "Nota" (de/para preenchidos) com resultado filtrado**
>
> <img width="1757" height="473" alt="image" src="https://github.com/user-attachments/assets/3c38866d-204e-445c-bd8a-8099ad5b1e75" />

> **Filtro de data aplicado na coluna "Criado em" (intervalo de datas) com resultado filtrado**
>
> <img width="1757" height="473" alt="image" src="https://github.com/user-attachments/assets/bb0ef255-c227-443d-916e-8d32b12edc0f" />

> **Filtro select "Aprovado" = Sim/Não aplicado com resultado filtrado**
>
> <img width="1757" height="473" alt="image" src="https://github.com/user-attachments/assets/a4fb9775-c09d-4219-944a-cf2987a4fb7e" />
> <img width="1757" height="473" alt="image" src="https://github.com/user-attachments/assets/433e2bbd-a642-4efb-8e6d-e4164994c25b" />


### 4. Ações em massa

> **Várias avaliações selecionadas + dropdown de ações em massa aberto exibindo Aprovar / Reprovar / Excluir**
>
> <img width="635" height="554" alt="image" src="https://github.com/user-attachments/assets/d3d33945-1314-4a0f-924c-6ffcf954658e" />

> **Mass "Aprovar" executada: mensagem de sucesso e coluna "Aprovado" atualizada para Sim**
>
> <img width="1410" height="617" alt="image" src="https://github.com/user-attachments/assets/e5e1052f-0180-4c92-a52e-5f42738ae3e2" />

> **Mass "Excluir" com modal de confirmação aberto antes de deletar (ação destrutiva)**
>
* **Antes**
> <img width="1468" height="434" alt="image" src="https://github.com/user-attachments/assets/8714fa2d-697f-4bff-afcd-22e4204316c7" />

* **Depois**
> <img width="1468" height="496" alt="image" src="https://github.com/user-attachments/assets/4c2f5e4a-563d-4a5b-9fbf-a7022cfdb30c" />


### 5. Ação por linha (coluna Ações)

> **Coluna "Ações" aberta em uma linha exibindo o link "Excluir" e Modal de confirmação para a exclusão**
>
> <img width="1764" height="550" alt="image" src="https://github.com/user-attachments/assets/048ee4d7-5de3-4cfa-aaeb-d2f1ef83e087" />

### 6. Criação de usuário com perfi restrito

> <img width="1857" height="925" alt="new-user" src="https://github.com/user-attachments/assets/fb71de4f-358f-43ea-b82e-c6ca88f9c3d9" />

> <img width="1759" height="279" alt="gusta-admin-save" src="https://github.com/user-attachments/assets/5f824174-87a4-42eb-b37e-5e0efc18a914" />


### 7. Teste de permissão com perfil restrito

> **Role "Sem Avaliações" em System > Permissions > User Roles, com nenhum recurso do Webjump marcado**
>
> <img width="1768" height="677" alt="image" src="https://github.com/user-attachments/assets/8815b32c-f4d5-4a47-9a21-23319e5e6f42" />
> <img width="1857" height="925" alt="new-user-role" src="https://github.com/user-attachments/assets/71bf395b-e94e-4ab9-ad32-3a121f4ebcbf" />


> **Usuário restrito logado: menu "Webjump" ausente na lateral**
>
> <img width="1831" height="827" alt="image" src="https://github.com/user-attachments/assets/552f8c5f-2655-44fd-9890-a3c5a365887a" />


> **Contraprova: após marcar "Avaliações" na role, o usuário restrito passa a ver o menu e abrir o grid normalmente**
>
> <img width="1207" height="740" alt="image" src="https://github.com/user-attachments/assets/3c041173-2a3e-4dd6-b237-4c0bb17aa7bd" />

> <img width="1836" height="733" alt="image" src="https://github.com/user-attachments/assets/c0c19422-2f24-498a-8fed-083efad44e09" />
> <img width="1836" height="733" alt="image" src="https://github.com/user-attachments/assets/e0460263-3bb3-419b-a32a-99bf1c2841d7" />


### 8. Teste automatizado de ACL

> **Execução do ReviewAclTest retornando `OK (3 tests, 7 assertions)`**
>
> ```bash
> docker compose exec -T phpfpm bash -c 'cd /var/www/html/dev/tests/integration && ../../../vendor/bin/phpunit -c phpunit.xml.dist /var/www/html/app/code/Webjump/Gustavo/Test/Integration/Acl/ReviewAclTest.php'
> ```
>
> <img width="1437" height="382" alt="teste-int" src="https://github.com/user-attachments/assets/c3dacbcc-0701-4820-823c-ea342cee1504" />

---

## CRITÉRIO DE ACEITE

- [x] O menu aparece no admin e leva ao grid
- [x] O grid lista os dados vindos da minha collection
- [x] Filtro por texto, por faixa numérica e por data funcionando
- [x] Ação em massa funcionando, com confirmação quando for destrutiva
- [x] Um usuário sem a permissão não consegue abrir a tela
- [x] README mostra o teste de permissão feito com um perfil restrito
