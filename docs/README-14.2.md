# Desafio 14.2 - Entidade própria, do banco ao repositório

## Resumo

Neste desafio, criei no módulo `Webjump_Gustavo` uma entidade própria completa para avaliações de produtos, cobrindo todo o caminho do banco até o contrato de serviço:

- Criei a tabela `webjump_gustavo_review` via **declarative schema** (`etc/db_schema.xml`), com colunas mínimas (`review_id`, `product_id`, `author`, `comment`, `rating`, `is_approved`, `created_at`) e FK para `catalog_product_entity` com `onDelete="CASCADE"`;
- Gerei a **whitelist** (`etc/db_schema_whitelist.json`) e a versionei junto do schema;
- Criei o trio **Model / ResourceModel / Collection** (`Model/Review.php`, `Model/ResourceModel/Review.php`, `Model/ResourceModel/Review/Collection.php`);
- Criei os **contratos de serviço** em `Api/` (`ReviewRepositoryInterface`) e `Api/Data/` (`ReviewInterface`, `ReviewSearchResultsInterface`), a implementação `Model/ReviewRepository.php` ligada por `<preference>` no `etc/di.xml` **global**;
- Criei o **data patch** `Setup/Patch/Data/AddSampleReviews.php`, que insere 5 avaliações de exemplo vinculadas aos produtos da categoria "Webjump" (fallback para IDs fixos), com distribuição cíclica e guarda de idempotência.

---

## Objetivo

Compreender na prática o ciclo completo de uma entidade plana no Magento 2: declarative schema (em vez de SQL imperativo), o trio Model/ResourceModel/Collection, Service Contracts (`Api/` + `preference` no di.xml), repositório com `SearchCriteria`/`CollectionProcessor` e data patches versionados.

---

## Arquitetura

```text
db_schema.xml (declara a tabela webjump_gustavo_review)
  └─ setup:upgrade cria a tabela (whitelist protege contra remoção acidental)
       ├─ ResourceModel: sabe falar com a tabela (_init('webjump_gustavo_review', 'review_id'))
       │    ├─ Model: 1 registro (getters/setters do contrato Api\Data\ReviewInterface)
       │    └─ Collection: vários registros (filtro/ordenação/paginação)
       │         └─ ReviewRepository (implementa Api\ReviewRepositoryInterface)
       │              ├─ save / getById / delete / deleteById → ResourceModel
       │              └─ getList(SearchCriteria) → CollectionProcessorInterface
       │                   (aplica filtros, ordenação e limite automaticamente)
       └─ Setup/Patch/Data/AddSampleReviews: insere 5 avaliações de exemplo
            (produtos da categoria "Webjump", fallback IDs [1, 2042])
```

### Fluxo de dados

1. `setup:upgrade` lê o `db_schema.xml`, compara com o banco e cria a tabela `webjump_gustavo_review`.
2. O data patch `AddSampleReviews` roda uma única vez (registrado em `patch_list`) e insere 5 linhas de exemplo.
3. Qualquer consumer usa `ReviewRepositoryInterface` (nunca o Model direto); o `di.xml` global resolve a preference para `Model\ReviewRepository`.
4. Em `getList`, o `SearchCriteria` do chamador (filtros + `pageSize`) é aplicado à Collection pelo `CollectionProcessorInterface` do framework — sem SQL na mão.

---

## Estrutura de Pastas Criada / Alterada

```text
app/code/Webjump/Gustavo/
├── Api/                                  # NOVO (14.2)
│   ├── ReviewRepositoryInterface.php     # Contrato do repositório (save/getById/delete/deleteById/getList)
│   └── Data/
│       ├── ReviewInterface.php           # Contrato dos dados da avaliação
│       └── ReviewSearchResultsInterface.php  # Contrato do resultado do getList
├── Model/                                # NOVO (14.2)
│   ├── Review.php                        # Model: 1 registro
│   ├── ReviewRepository.php              # Implementação do contrato Api/
│   ├── ReviewSearchResults.php           # Resultado do getList (extends framework)
│   └── ResourceModel/
│       ├── Review.php                    # ResourceModel: table + PK
│       └── Review/
│           └── Collection.php            # Collection
├── Setup/                                # NOVO (14.2)
│   └── Patch/
│       └── Data/
│           └── AddSampleReviews.php      # Data patch com 5 avaliações de exemplo
└── etc/
    ├── db_schema.xml                     # NOVO (14.2) — declarative schema
    ├── db_schema_whitelist.json          # NOVO (14.2) — gerado, versionado
    └── di.xml                            # NOVO (14.2) — preferences globais
```

### Explicação de cada componente

**`etc/db_schema.xml`** — declara a tabela final desejada; o Magento gera o DDL sozinho no `setup:upgrade`.

- `review_id` como PK identity; `product_id` com FK → `catalog_product_entity.entity_id` `onDelete="CASCADE"` (avaliações morrem com o produto); `rating` e `is_approved` como `smallint`; `created_at` com `default=CURRENT_TIMESTAMP` e `on_update=false`.

**`etc/db_schema_whitelist.json`** — trava de segurança gerada por `setup:db-declaration:generate-whitelist`. Sem ela, uma remoção acidental de coluna no XML apagaria o dado do banco. Versionada junto do schema.

**`Model/Review.php`** — estende `AbstractModel` e implementa `Api\Data\ReviewInterface`. Getters/setters via `getData`/`setData` com casts explícitos, centralizados em constantes de campo.

**`Model/ResourceModel/Review.php`** — `_init('webjump_gustavo_review', 'review_id')`. Responsável exclusivo por falar com o banco.

**`Model/ResourceModel/Review/Collection.php`** — `_init(Model, ResourceModel)`. Suporta filtros/ordenação/paginação.

**`Api/Data/ReviewInterface.php` + `ReviewSearchResultsInterface.php` + `Api/ReviewRepositoryInterface.php`** — os contratos públicos do módulo (`@api`). Terceiros, WebApi REST e outros módulos dependem só disso, nunca da implementação.

**`Model/ReviewRepository.php`** — implementa o contrato:

- `save`: exige instância de `Model\Review`, delega ao ResourceModel, envolve falhas em `CouldNotSaveException`;
- `getById`: `factory->create()` + `load()`, lança `NoSuchEntityException` se vazio;
- `delete`/`deleteById`: análogos com `CouldNotDeleteException`;
- `getList`: cria a Collection e deixa o `CollectionProcessorInterface` do framework aplicar filtros, ordenações e paginação do `SearchCriteria`; retorna `ReviewSearchResults` com items, total e criteria.

**`etc/di.xml` (global)** — preferences ligando as três interfaces às implementações (`Model\Review`, `Model\ReviewSearchResults` — que estende `Magento\Framework\Api\SearchResults` e implementa o nosso contrato —, `Model\ReviewRepository`). Criado em `etc/` (e não `etc/frontend/`) porque o repositório pode ser usado em qualquer área, inclusive WebApi futura.

**`Setup/Patch/Data/AddSampleReviews.php`** — insere as 5 avaliações:

1. Guarda de idempotência (`SELECT COUNT(*)` — se já há registros, sai);
2. Resolve produtos da categoria com `name = 'Webjump'` via `CategoryCollection`;
3. Fallback para os IDs `[1, 2042]` se a categoria não existir ou estiver vazia; se nada resolver, falha com `LocalizedException` clara;
4. Distribui as 5 avaliações ciclicamente entre os produtos (`i % count`);
5. `INSERT` múltiplo via `ResourceConnection` (patch usa SQL puro — tabela acabou de nascer e queremos determinismo).

---

## Por que tabela própria e não EAV neste caso

1. **Schema fixo e homogêneo**: toda avaliação tem exatamente as mesmas colunas (autor, comentário, nota, aprovado, data). EAV existe para quando o lojista precisa **criar atributos novos sem mexer em código** (é o caso de produtos, com cores/tamanhos variáveis entre lojas). Aqui não há nada para extender — o schema é do domínio, não do lojista.
2. **Custo de leitura/escrita**: no EAV, cada atributo vira uma linha em uma tabela `_varchar/_int/_text/...` separada, e qualquer leitura exige múltiplos JOINs com `eav_attribute`. Avaliação é dado de **alto volume e alta frequência** (toda página de produto lista avaliações aprovadas; toda compra pode gerar uma). Uma tabela plana atende com `SELECT` de uma linha por avaliação, sem join.
3. **Consultas triviais ficam triviais**: "listar avaliações aprovadas do produto X, ordenadas por data, páginas de 10" vira um `WHERE product_id = ? AND is_approved = 1 ORDER BY created_at LIMIT 10`. Em EAV, seria uma query por atributo.
4. **Integridade relacional direta**: a FK `product_id → catalog_product_entity` com `CASCADE` só existe de forma limpa em tabela própria — deletar o produto limpa as avaliações automaticamente. EAV não oferece esse vínculo nativo.
5. **Regra de bolso do Magento**: EAV para entidades com schema extensível pelo lojista (produto, cliente); tabela plana (flat) para dados transacionais/relacionais com schema fixo (pedidos, cotações, avaliações deste módulo).

---

## Passo a Passo da Construção

1. **Schema (`etc/db_schema.xml`)**: declarada a tabela com as 7 colunas, PK e FK CASCADE seguindo o `schema.xsd` do framework.
2. **Whitelist**: gerada com
   ```bash
   ./bin/magento setup:db-declaration:generate-whitelist --module-name=Webjump_Gustavo
   ```
3. **Contratos (`Api/` e `Api/Data/`)**: definidos antes da implementação, com `@api`.
4. **Trio Model/ResourceModel/Collection**: boa parte é boilerplate de `_construct`/`_init`; o Model implementa o contrato de dados.
5. **Repositório (`Model/ReviewRepository.php`)**: `CollectionProcessorInterface` para o `getList`, exceções específicas do framework por operação.
6. **DI (`etc/di.xml`)**: três preferences; `ReviewSearchResultsInterface → Model\ReviewSearchResults` (classe fina que estende a genérica do framework e implementa o nosso contrato).
7. **Data patch**: SQL puro via `ResourceConnection`, resolução de produtos por categoria com fallback e idempotência.
8. **Validação estática**: `php -l` em todos os arquivos + `bin/magento dev:di:info "Webjump\Gustavo\Api\ReviewRepositoryInterface"` confirmando a preference resolvida na área GLOBAL.

---

## Decisões Tomadas, Alternativas e Justificativas

### 1. Tudo no módulo existente `Webjump_Gustavo` vs. módulo novo

- **Alternativa Considerada**: módulo separado (ex.: `Webjump_ProductReview`).
- **Por que a escolha adotada é melhor**: o desafio faz parte de uma trilha sequencial que já vive no módulo; um módulo novo seria overhead sem ganho. Se um dia precisar isolar, a extração é trivial porque tudo está concentrado em Api/Model/Setup.

### 2. Nome `Review`/`webjump_gustavo_review` vs. `Avaliacao`

- **Alternativa Considerada**: nomes em português (o handoff citava `AvaliacaoRepositoryInterface`).
- **Por que a escolha adotada é melhor**: o código do módulo e as convenções do projeto são em inglês; `Review` evita colisão conceitual com `Magento_Review` ao ficar sob o namespace `Webjump\Gustavo`.

### 3. FK com `onDelete="CASCADE"` vs. RESTRICT / sem FK

- **Alternativa Considerada**: sem FK ou com RESTRICT.
- **Por que a escolha adotada é melhor**: avaliação órfã é lixo; deletar o produto deve levar as avaliações junto. CASCADE garante isso no nível do banco, e o declarative schema cria o índice da FK automaticamente.

### 4. `getList` com `CollectionProcessorInterface` vs. aplicar SearchCriteria manualmente

- **Alternativa Considerada**: iterar filtros/ordens/paginação na mão.
- **Por que a escolha adotada é melhor**: o processor do framework aplica filtro + ordenação + `pageSize` de forma testada e idiomática (é o que `Magento_Cms` e cia. fazem), e atende literalmente o critério "getList aceita SearchCriteria com filtro e limite".

### 5. Implementação de `ReviewSearchResultsInterface` → classe própria `Model\ReviewSearchResults`

- **Alternativa Considerada**: apontar a preference direto para `Magento\Framework\Api\SearchResults` (genérica do framework).
- **Por que a escolha adotada é melhor**: a classe genérica só implementa `SearchResultsInterface`, **não** a nossa interface filha — usar ela na preference estoura `TypeError` no retorno tipado do `getList`. A classe própria (`extends SearchResults implements ReviewSearchResultsInterface`) é o padrão usado pelo core (ex.: `Magento\Cms\Model\BlockSearchResults`) e é uma linha de herança, sem lógica duplicada.

### 6. Patch vinculando à categoria "Webjump" com fallback cíclico vs. IDs fixos / produtos aleatórios

- **Alternativa Considerada**: hardcode direto nos SKUs/IDs, ou primeiros produtos da loja.
- **Por que a escolha adotada é melhor**: conecta com o desafio anterior da trilha (a categoria "Webjump" existe e tem 2 produtos), mas não quebra em outro ambiente: fallback para IDs conhecidos + falha explícita se nada resolver. A distribuição cíclica garante sempre 5 avaliações mesmo com menos de 5 produtos (aqui: 3 no produto 1, 2 no 2042).

### 7. Patch com `INSERT` via `ResourceConnection` vs. usar o próprio repositório

- **Alternativa Considerada**: salvar via `ReviewRepositoryInterface` dentro do patch.
- **Por que a escolha adotada é melhor**: data patch roda no momento em que a tabela acabou de nascer; SQL puro via `ResourceConnection` é determinístico, rápido (um `INSERT` múltiplo) e não depende de nada além da própria tabela.

### 8. Sem validação de `rating` 1–5 na camada de repositório

- **Alternativa Considerada**: validar faixa no `save()`.
- **Por que a escolha adotada é melhor**: o desafio não pede validação e o repositório deve ficar enxuto (YAGNI). Se surgir a necessidade, um validator dedicado é o lugar certo — não o repositório.

---

## Evidências

### 1. A tabela é criada sozinha ao rodar `setup:upgrade`

> **`setup:upgrade` em execução:**
>
> ```bash
> bin/magento setup:upgrade
> ```
>
> <img width="1270" height="540" alt="image" src="https://github.com/user-attachments/assets/51909e31-dc65-4aae-a6b0-ca6cd5fce003" />
> <img width="1270" height="540" alt="image" src="https://github.com/user-attachments/assets/87e9dba8-f484-4271-8aec-96401b7fcde1" />

> **DEPOIS - tabela criada com colunas, PK e FK CASCADE:**
>
> ```bash
> docker exec magento-db-1 mariadb -umagento -pmagento magento -e "SHOW CREATE TABLE webjump_gustavo_review\G"
> ```
>
> <img width="1622" height="370" alt="image" src="https://github.com/user-attachments/assets/be5a019b-f206-4895-9a58-5782d7b2aba0" />

---

### 2. A whitelist foi gerada e versionada

> **`etc/db_schema_whitelist.json` aberto no editor:**
>
> <img width="1211" height="566" alt="image" src="https://github.com/user-attachments/assets/6b496de7-036d-4d6c-9960-1b9651bd2629" />

---

### 3. Existe interface de repositório em Api/, ligada por preference

> **`Api/ReviewRepositoryInterface.php` aberto no editor:**
>
> <img width="1211" height="964" alt="image" src="https://github.com/user-attachments/assets/24b23886-fc0c-4ddc-93bb-2b321201942f" />

> **Preference resolvida na área GLOBAL:**
>
> ```bash
> bin/magento dev:di:info "Webjump\Gustavo\Api\ReviewRepositoryInterface"
> ```
>
> ```text
> DI configuration for the class Webjump\Gustavo\Api\ReviewRepositoryInterface in the GLOBAL area
> Preference: Webjump\Gustavo\Api\ReviewRepositoryInterface
> ```
>
> <img width="1377" height="631" alt="image" src="https://github.com/user-attachments/assets/96fff605-0d98-4722-b46d-6f1512237f17" />

---

### 4. O repositório tem save, getById, delete e getList

> **`Api/ReviewRepositoryInterface.php` com as assinaturas dos métodos em destaque:**
>
> <img width="1598" height="909" alt="image" src="https://github.com/user-attachments/assets/fc0ab179-d250-4488-b12f-7f3b87c032da" />

> **`Model/ReviewRepository.php` com as implementações:**
>
> <img width="1395" height="685" alt="image" src="https://github.com/user-attachments/assets/ee7f4098-85e2-4989-a7ed-7c31e32bc239" />
> <img width="1395" height="376" alt="image" src="https://github.com/user-attachments/assets/5358e9d2-7a38-4e41-b14a-7eb7edb74116" />

---

### 5. O getList aceita SearchCriteria com filtro e limite

> **`Model/ReviewRepository.php` aberto no método `getList` (assinatura + `collectionProcessor->process(...)`):**
>
> <img width="1395" height="361" alt="image" src="https://github.com/user-attachments/assets/9f6b26c5-2d13-4bd7-8b74-7d7ecd02f3bb" />

> **Script de validação do getList (filtro `is_approved = 1` + `pageSize = 2`):**
>
> ```bash
> docker exec magento-phpfpm-1 php /var/www/html/var/validate-getlist.php
> ```
>
> <img width="1240" height="429" alt="image" src="https://github.com/user-attachments/assets/9add22a7-3857-4a9d-a8d4-9aa0bfe74595" />

---

### 6. As 5 avaliações de exemplo são inseridas por patch

> **As 5 linhas na tabela (product_id 1 e 2042):**
>
> ```bash
> docker exec magento-db-1 mariadb -umagento -pmagento magento -e "SELECT * FROM webjump_gustavo_review;"
> ```
>
> <img width="1328" height="184" alt="image" src="https://github.com/user-attachments/assets/c0c447f9-59ba-4a68-b69a-7a817776cb3f" />

> **Patch registrado em `patch_list`:**
>
> ```bash
> docker exec magento-db-1 mariadb -umagento -pmagento magento -e "SELECT patch_name FROM patch_list WHERE patch_name LIKE '%AddSampleReviews%';"
> ```
>
> <img width="1328" height="184" alt="image" src="https://github.com/user-attachments/assets/c3045b7c-7704-4376-b16f-4c7186c01d97" />

---

## Checklist de Critérios de Aceite

- [x] A tabela é criada sozinha ao rodar setup:upgrade
- [x] A whitelist foi gerada e versionada
- [x] Existe interface de repositório em Api/, e a implementação está ligada por preference
- [x] O repositório tem save, getById, delete e getList
- [x] O getList aceita SearchCriteria com filtro e limite
- [x] As 5 avaliações de exemplo são inseridas por patch
- [x] README explica por que tabela própria e não EAV neste caso
