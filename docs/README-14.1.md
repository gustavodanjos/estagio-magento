# Desafio 14.1 - Atributo de Produto por Código

## Resumo

Neste desafio, estendi o módulo `Webjump_Gustavo` para que o lojista consiga **marcar produtos como sustentáveis** sem criar nada manualmente no admin. Tudo acontece via código, de forma repetível entre ambientes:

- **Camada de dados:** um **Data Patch** (`AddSeloSustentavelAttribute`) cria o atributo `selo_sustentavel` como **Sim/Não (Boolean nativo)** — `type=int`, `input=select`, `source=Boolean` — com rótulo **“Selo Sustentável”**, no grupo **“Selos”** do formulário de produto, escopo **Global** e habilitado para aparecer na listagem e no grid do admin.
- **Camada de exibição:** o selo aparece na **página do produto (PDP)** via bloco próprio com **ViewModel**, com saída segura (early return se o produto não tem o selo, sem erro e sem badge).
- **Reuso:** a mesma marca visual foi estendida para **vitrines** — listagem de categoria, busca, blocos de relacionados/upsell/cross-sell e widgets de vitrine — reaproveitando um **partial único** (`selo_sustentavel.phtml`) para não duplicar HTML.
- **Validação:** patch aparece em `patch_list`, atributo é editável/filtrável no admin e todas as superfícies foram testadas com produto com e sem selo.

---

## Objetivo

Praticar, de ponta a ponta, a criação de **atributos EAV de produto via Data Patch** — entendendo o que cada opção realmente muda no banco e no storefront (tipo de campo, escopo, `used_in_product_listing`, `visible_on_front`, grid) — e aplicar **exibição condicional no frontend** que respeite o caso de o produto não ter o atributo preenchido, sem quebrar layout nem gerar log de erro.

---

## Arquitetura

```text
Data Patch (AddSeloSustentavelAttribute)
  └─ EavSetup cria atributo EAV (eav_attribute + catalog_eav_attribute)
       └─ patch_list registra que o patch já rodou (imutável)
            └─ Admin (Catalog > Products > aba "Selos") edita Sim/Não (salvo em catalog_product_entity_int)
                 └─ PDP: ViewModel lê current_product do Registry e decide se renderiza selo
                 └─ Listagens: collection já traz selo_sustentavel (used_in_product_listing=1) -> partial badge
```

### Fluxo de dados

1. Ao rodar `bin/magento setup:upgrade` em base limpa, o patch cria o grupo **Selos** em `Default` e o atributo `selo_sustentavel`.
2. O lojista abre qualquer produto no admin, vai na aba **Selos** e marca **Sim/Não**.
3. Na **PDP**, o ViewModel `ProductSeloSustentavel::hasSeloSustentavel()` lê `current_product` do `Registry` e retorna `true` só quando o valor é `1`.
4. Nas **listagens**, o Magento já traz `selo_sustentavel` na collection (porque o atributo está marcado como `used_in_product_listing`), então o partial `badge/selo_sustentavel.phtml` decide por produto se imprime o badge.
5. Se o produto não tem selo, ambos os templates fazem **early return** — nenhum HTML é emitido e nenhum erro é logado.

---

## Estrutura de Arquivos

```
src/app/code/Webjump/Gustavo/
├── Setup/Patch/Data/
│   └── AddSeloSustentavelAttribute.php      # Data Patch: cria grupo + atributo
├── ViewModel/
│   └── ProductSeloSustentavel.php            # ViewModel da PDP (lê current_product)
├── etc/
│   └── di.xml                                # Plugin que troca template da listagem de categoria
├── Plugin/Catalog/Block/Product/
│   └── ListProductTemplate.php               # afterGetTemplate: category.products.list -> product/list.phtml
├── view/frontend/
│   ├── layout/
│   │   ├── catalog_product_view.xml          # PDP + related/upsell/cross-sell
│   │   ├── catalog_category_view.xml         # Categoria
│   │   ├── catalogsearch_result_index.xml    # Busca
│   │   └── default.xml                       # Widget de vitrine
│   ├── templates/
│   │   ├── product/view/selo.phtml           # Badge da PDP (usa ViewModel)
│   │   ├── product/badge/selo_sustentavel.phtml # Partial reutilizável (todas as listagens)
│   │   ├── product/list.phtml                # Override da listagem categoria/busca
│   │   ├── product/list/items.phtml          # Override related/upsell/cross-sell
│   │   └── product/widget/content/grid.phtml # Override do widget Catalog Products List
│   └── web/css/source/_module.less           # Estilos do badge (BEM)
```

### Explicação de cada componente

**`Setup/Patch/Data/AddSeloSustentavelAttribute.php`** — coração do desafio.
- Implementa `DataPatchInterface` + `PatchRevertableInterface` (método `revert()` remove o atributo).
- Cria o grupo `Selos` em `Default` (`addAttributeGroup`) e o atributo com `addAttribute` — todas as decisões de tipo/escopo/flags estão ali.
- Patches são **imutáveis**: se precisar mudar algo depois, cria-se um novo patch.

**`ViewModel/ProductSeloSustentavel.php`** — lógica da PDP, sem depender de Block.
- Implementa `ArgumentInterface` para ser injetado via layout.
- Usa `Registry::registry('current_product')` e método `hasSeloSustentavel(): bool` com comparação estrita (`'1' / 1 / true`) — fallback seguro quando não há produto ou o valor é `0/null`.

**`view/frontend/templates/product/view/selo.phtml`** — badge da PDP.
- Recebe o ViewModel via `$block->getData('view_model')`, valida instância e chama `hasSeloSustentavel()` — se `false`, faz `return` imediato.

**`view/frontend/templates/product/badge/selo_sustentavel.phtml`** — partial DRY para vitrines.
- Recebe `$_product` via `$block->setData('_product', $_product)` injetado pelos templates de lista; decide sozinho se deve imprimir. Evita duplicar HTML em 4 superfícies.

**`view/frontend/templates/product/list.phtml`, `list/items.phtml`, `widget/content/grid.phtml`** — overrides mínimos do core.
- Copiados do tema base e alterados apenas para injetar o bloco filho `selo_sustentavel` logo após a imagem. Mantêm todo o resto idêntico ao Magento para reduzir risco em upgrades.

**`view/frontend/layout/*.xml`** — ligam cada superfície ao seu template e ao partial.
- `catalog_product_view.xml` injeta o bloco da PDP em `product.info.main` e troca o template dos blocos `catalog.product.related`, `product.info.upsell` e `checkout.cart.crosssell`.
- `catalog_category_view.xml` / `catalogsearch_result_index.xml` trocam o template da listagem (`category.products.list` / `search_result_list`).
- `default.xml` troca o template do widget `catalogwidget.product.list`.

**`etc/di.xml` + `Plugin/Catalog/Block/Product/ListProductTemplate.php`** — garantia extra para categoria.
- O plugin `afterGetTemplate` garante que `category.products.list` use `product/list.phtml` mesmo quando o layout é montado por outro handle — cobertura defensiva além do XML.

**`view/frontend/web/css/source/_module.less`** — visual do selo.
- Classe `.selo-sustentavel` com variações `--pdp` (inline, maior) e `--card` (absoluto no canto da foto, com sombra). Usa BEM e ícone em SVG inline (data URI) para não depender de asset externo.

---

## Passo a Passo da Construção

1. **Data Patch (`AddSeloSustentavelAttribute.php`)**:
   - Espelhado no exemplo do curso (`AddSelamAttribute`) e na API `EavSetup`.
   - Definidos `ATTRIBUTE_CODE = selo_sustentavel` e `GROUP_NAME = Selos` como constantes.

2. **ViewModel da PDP (`ProductSeloSustentavel.php`)**:
   - Criado para manter o `.phtml` passivo — toda a decisão booleana fica no PHP testável.

3. **Partial reutilizável (`badge/selo_sustentavel.phtml`)**:
   - Criado primeiro, para que todos os templates de lista pudessem simplesmente fazer `getChildBlock()->setData('_product', $_product)->toHtml()`.

4. **PDP (`selo.phtml` + `catalog_product_view.xml`)**:
   - Bloco injetado em `product.info.main` após o preço, com ViewModel como argumento.

5. **Listagens e widget**:
   - `list.phtml` / `items.phtml` / `grid.phtml` receberam a chamada ao partial; cada layout correspondente passou a apontar para o override e declarar o bloco filho `selo_sustentavel`.

6. **Plugin de listagem (`di.xml` + `ListProductTemplate.php`)**:
   - Adicionado por último, como camada de compatibilidade para a categoria.

7. **Estilos (`_module.less`)** e validação:
   ```bash
   docker compose exec -T phpfpm php -l app/code/Webjump/Gustavo/Setup/Patch/Data/AddSeloSustentavelAttribute.php
   # No syntax errors detected
   docker compose exec -T phpfpm bin/magento setup:di:compile
   # Generated code and dependency injection configuration successfully.
   bin/magento setup:upgrade
   bin/magento indexer:reindex && bin/magento cache:flush
   ```

---

## Decisões de Implementação e Justificativas

### 1. Tipo e entrada: Sim/Não (Boolean) vs. texto/select customizado

- **Alternativa considerada:** `type=varchar` + `input=text` ou `select` com `SourceModel` próprio.
- **Por que a escolha adotada é melhor:** o requisito é binário (tem selo / não tem). O Boolean nativo (`type=int`, `input=select`, `source=Boolean`) já entrega `0/1` no banco, opções “Não/Sim” traduzíveis e zero código extra (YAGNI). Se surgir um segundo selo (ex.: `selo_organico`), a regra do Data Patch manda criar **outro patch/ outro atributo**, mantendo histórico limpo.

### 2. Escopo: Global vs. Store

- **Alternativa considerada:** `SCOPE_STORE` (como no exemplo didático do curso, que ensina a sintaxe).
- **Por que a escolha adotada é melhor:** “ser sustentável” é característica intrínseca do produto, não escolha de merchandising por loja. `SCOPE_GLOBAL` evita coluna `store_id` extra em `catalog_product_entity_int`, evita fallback `store → default` e custo de índice, sem ganho numa instância single-store. Se um dia precisar divergir por store (ex.: certificação válida só no BR), faz-se um **novo patch** alterando o escopo de forma versionada.

### 3. Grupo próprio “Selos” no admin

- **Alternativa considerada:** jogar no grupo nativo `General` ou `Product Details`.
- **Por que a escolha adotada é melhor:** isola campos customizados, não polui UX nativa, demonstra domínio de `EavSetup::addAttributeGroup`.

### 4. Rótulo em português fixo

- **Alternativa considerada:** array de labels por store view para i18n completo.
- **Por que a escolha adotada é melhor:** a instância não tem store views PT/EN configuradas; i18n real seria overengineering fora do escopo. O rótulo fixo `Selo Sustentável` atende o critério “rótulo em português”.

### 5. `visible_on_front = true` e `used_in_product_listing = true`

- **Alternativa considerada:** deixar ambos `false`.
- **Por que a escolha adotada é melhor:** `visible_on_front` faz o atributo aparecer de graça na aba “More Information” da PDP (fallback nativo via `Magento\Catalog\Block\Product\View\Attributes`) — camada extra de informação sem código. `used_in_product_listing` é obrigatório para o valor chegar nas collections de categoria/busca/related/widgets; sem ele, `$_product->getData('selo_sustentavel')` viria `null` nessas vitrines. O enunciado pede “escolher com cuidado se entra na listagem” — aqui a resposta consciente foi **sim**.

### 6. Flags de grid do admin

- **Alternativa considerada:** deixar `is_used_in_grid / is_visible_in_grid / is_filterable_in_grid` como `false`.
- **Por que a escolha adotada é melhor:** com `true`, o lojista consegue filtrar e ordenar por “Selo Sustentável” em **Catalog > Products** — utilidade operacional real sem custo.

---

## Onde o selo aparece

| # | Superfície | Template usado | Layout que ativa | Partial |
|---|---|---|---|---|
| 1 | **PDP** | `product/view/selo.phtml` (com ViewModel) | `catalog_product_view.xml` (`product.info.main`) | próprio |
| 2 | **Categoria + Busca** | `product/list.phtml` | `catalog_category_view.xml` / `catalogsearch_result_index.xml` | `product/badge/selo_sustentavel.phtml` |
| 3 | **Related / Upsell / Cross-sell** | `product/list/items.phtml` | `catalog_product_view.xml` (3 blocos) | `product/badge/selo_sustentavel.phtml` |
| 4 | **Widgets de vitrine** | `product/widget/content/grid.phtml` | `default.xml` (`catalogwidget.product.list`) | `product/badge/selo_sustentavel.phtml` |

**Fora do escopo (documentado):** minicart, carrinho, checkout e e-mails. Nesses pontos o produto já foi escolhido; selo é ferramenta de **descoberta**, não de confirmação.

---


### Riscos conhecidos

1. **Overrides de template do core** — sensíveis a upgrades do Magento. Mitigação: alteração mínima (só injeção do badge), documentada acima.
2. **Widget via `default.xml`** — o bloco `catalogwidget.product.list` é específico do widget de produtos; não afeta outros widgets.

---


## Evidências

### 1. Atributo no Admin — grupo "Selos" e rótulo em português

> **Aba "Selos" no formulário de edição do produto, com o campo "Selo Sustentável" (Sim/Não)**
> <img width="100%" src="https://github.com/user-attachments/assets/5616e0c7-6037-4cd0-a12a-be9329b138d8" />

> **Stores → Attributes → Product: atributo `selo_sustentavel` listado com escopo Global**
> <img width="100%" src="https://github.com/user-attachments/assets/6ef083ee-1c34-42b9-a7cb-2c193884bfc3" />

> **Grid de produtos (Catalog → Products) com a coluna "Selo Sustentável" disponível e filtro aplicado**
> <img width="100%" src="https://github.com/user-attachments/assets/9977de8e-d1b9-4c75-9c0c-aff067787eda" />

### 2. PDP — selo exibido quando marcado, nada quebra quando vazio

> **PDP de produto com selo marcado ("Sim") — badge "Produto Sustentável" visível abaixo do preço**
> <img width="100%" src="https://github.com/user-attachments/assets/7c9078da-fc3a-482d-b66a-5528497d402e" />

> **PDP de produto SEM o atributo preenchido — página renderiza normalmente, sem badge e sem erro**
> <img width="100%" src="https://github.com/user-attachments/assets/681c915c-3f3e-4348-a467-a32cb7bfe7b0" />
> <img width="1756" height="145" alt="image" src="https://github.com/user-attachments/assets/696be001-f721-435a-ba31-724dd2320d52" />

> **Aba "More Information" da PDP exibindo o atributo nativamente (fallback via `visible_on_front`)**
> <img width="100%" src="https://github.com/user-attachments/assets/cabd590c-5bc0-4cd8-bcae-b13888895edb" />
> <img width="1478" height="822" alt="image" src="https://github.com/user-attachments/assets/0e31b624-30e5-484c-809d-41c45501685c" />


### 3. Listagens — categoria, busca, related/upsell/cross-sell e widget

> **Listagem de categoria com produto(s) marcados exibindo o badge no card e Contraprova: produto sem selo na mesma listagem de categoria, sem badge**
> <img width="100%" src="https://github.com/user-attachments/assets/cdf7b68d-9601-4a84-92f9-0c5668d974b1" />

> **Resultado de busca (catalogsearch) exibindo o badge no produto marcado**
> <img width="100%" src="https://github.com/user-attachments/assets/bf2e9062-d5fa-4b00-8d93-cbd2f5c8a7e3" />


### 4. Validação técnica

> **`bin/magento setup:di:compile` concluído sem erros**
> <img width="100%" src="https://github.com/user-attachments/assets/9a591e74-77c8-46aa-8331-9e4a060c78c5" />


---

## Checklist de Critérios de Aceite

- [x] O atributo é criado ao rodar setup:upgrade em uma base limpa
- [x] Aparece no admin, no grupo correto, com o rótulo em português
- [x] O patch está registrado na tabela patch_list
- [x] O selo aparece na página do produto quando marcado, e nada quebra quando não está
- [x] README explica por que você escolheu aquele escopo

### Cobertura adicional

- [x] Selo aparece na listagem de categoria
- [x] Selo aparece nos resultados de busca
- [x] Selo aparece em widgets de vitrine (Catalog Products List)
- [x] Atributo disponível e filtrável no grid de produtos do Admin
