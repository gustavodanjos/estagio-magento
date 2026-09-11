# Desafio 13.3 - Campo de configuração no admin

## Resumo

Neste desafio, evoluí o módulo `Webjump_Gustavo` criado no 13.1 para que o texto exibido pelo bloco da home seja editável pelo lojista no admin, sem mexer em código:

- Criei uma seção própria `Webjump Gustavo` em Stores > Configuration via `etc/adminhtml/system.xml`, com grupo `Bloco Home` e dois campos (`title` como `text`, `welcome_message` como `textarea`);
- Registrei a permissão da seção em `etc/acl.xml` (`Webjump_Gustavo::config`);
- Defini os valores padrão em `etc/config.xml` (iguais aos textos hardcoded do 13.1);
- Refatorei o `ViewModel/HomeBlock.php` para ler os valores via `ScopeConfigInterface::getValue(..., SCOPE_STORE)` com `trim()` + fallback para constante default quando vazio;
- Mantive o template `.phtml` passivo, com toda saída via `$escaper->escapeHtml()`.

---

## Objetivo

Compreender na prática o sistema de configuração do Magento 2 (`system.xml` + `config.xml` + `ScopeConfig`), o controle de acesso do admin (`acl.xml`), os escopos Default / Website / Store e o padrão de fallback defensivo no ViewModel, mantendo a separação de responsabilidades (lógica no ViewModel, apresentação no `.phtml`).

---

## Arquitetura

```text
Stores > Configuration (seção Webjump Gustavo)
  └─ system.xml define section/group/fields + resource
       └─ valores salvos em core_config_data (escopo default/website/store)
            └─ config.xml fornece defaults quando nada foi salvo
                 └─ ViewModel lê via ScopeConfigInterface (SCOPE_STORE) + fallback
                      └─ .phtml exibe com escapeHtml()
```

### Fluxo de dados

1. O lojista edita `Título do bloco` / `Mensagem de boas-vindas` em Stores > Configuration > Webjump Gustavo > Bloco Home.
2. O Magento persiste em `core_config_data` nos paths `webjump_gustavo/general/title` e `webjump_gustavo/general/welcome_message`.
3. O `HomeBlock::getTitle()` / `getWelcomeMessage()` lê via `ScopeConfig` em `SCOPE_STORE`, aplica `trim()` e retorna a constante `DEFAULT_*` se vazio.
4. O template recupera o ViewModel via `$block->getData('view_model')`, valida a instância com early return e imprime com `$escaper->escapeHtml()`.
5. Após alterar no admin é necessário `bin/magento cache:clean config full_page` (critério de aceite).

---

## Estrutura de Pastas Criada / Alterada

```text
app/code/Webjump/Gustavo/
├── etc/
│   ├── acl.xml                      # NOVO (13.3)
│   ├── adminhtml/
│   │   └── system.xml               # NOVO (13.3)
│   ├── config.xml                   # NOVO (13.3)
│   └── module.xml                   # 13.1
└── ViewModel/
    └── HomeBlock.php                # ALTERADO (13.3): ScopeConfig + fallback

```

### Explicação de cada componente

**`etc/adminhtml/system.xml`** — declara a seção nova no admin.
- Seção `webjump_gustavo` (label "Webjump Gustavo", `tab="general"`, `resource="Webjump_Gustavo::config"`, visível em Default/Website/Store).
- Grupo `general` (label "Bloco Home") com dois campos: `title` (`text`) e `welcome_message` (`textarea`), ambos com `canRestore=1`.

**`etc/acl.xml`** — libera a permissão de acesso à seção.
- Registra `Webjump_Gustavo::config` sob `Magento_Backend::admin > stores > stores_settings > config`.
- Sem essa entrada, a seção retorna 403 (ou some do menu, dependendo do papel do usuário admin).

**`etc/config.xml`** — define os valores padrão.
- Preenche `<default><webjump_gustavo><general>` com os mesmos textos hardcoded do 13.1.
- Garante que o admin já carregue com um valor sensato na primeira vez, antes de qualquer edição do lojista.

**`ViewModel/HomeBlock.php`** — lê a configuração e aplica o fallback.
- Injeta `ScopeConfigInterface` junto do `TimezoneInterface` já existente.
- Constantes `XML_PATH_TITLE`, `XML_PATH_WELCOME_MESSAGE`, `DEFAULT_TITLE`, `DEFAULT_WELCOME_MESSAGE` centralizam os paths e os defaults.
- Leitura via `getValue(..., ScopeInterface::SCOPE_STORE)`, com `(string)` + `trim()` e fallback para a constante default quando o resultado vier vazio.
- Assinaturas públicas `getTitle(): string` e `getWelcomeMessage(): string` não mudam — o `.phtml` continua sem saber de onde vem o dado.

---

## Passo a Passo da Construção

1. **Definição da seção (`etc/adminhtml/system.xml`)**:
   - Espelhado no `Magento_Contact` (`section contact`) como referência de `system_file.xsd`.
   - Escolhidos ids `webjump_gustavo/general/title` e `webjump_gustavo/general/welcome_message`.

2. **Permissão (`etc/acl.xml`)**:
   - Espelhado no `Magento_Contact/etc/acl.xml`, trocando o resource final por `Webjump_Gustavo::config`.

3. **Defaults (`etc/config.xml`)**:
   - Schema `Magento_Store:etc/config.xsd`, bloco `<default>` com os dois valores do 13.1.

4. **Refatoração do ViewModel (`ViewModel/HomeBlock.php`)**:
   - Adicionado `use ScopeConfigInterface` + `ScopeInterface`, construtor `promoted readonly` com as duas dependências.
   - Leitura defensiva: `trim((string) getValue(...))`, fallback para constante — cobre `null`, `''` e `'   '`.

5. **Ativação e Validação no Magento**:

   ```bash
   bin/magento module:status Webjump_Gustavo
   # Webjump_Gustavo : Module is enabled

   bin/magento setup:upgrade --keep-generated
   bin/magento cache:clean config full_page
   bin/magento dev:di:info "Webjump\Gustavo\ViewModel\HomeBlock"
   # timezone + scopeConfig resolvidos
   ```
   - Validação XML/PHP:
     ```bash
     ./bin/cli php -l app/code/Webjump/Gustavo/ViewModel/HomeBlock.php
     # No syntax errors detected
     ```

---

## Decisões Tomadas, Alternativas e Justificativas

### 1. Dois campos (`title` + `welcome_message`) vs. um campo único

- **Alternativa Considerada**: só `welcome_message`, literal ao enunciado ("um campo de texto").
- **Por que a escolha adotada é melhor**: decidido como "título + mensagem de boas-vindas" — custo marginal que não demandaria tanto tempo, cobre o título que também era hardcoded e melhora a demo para o lojista, sem sair do espírito do desafio (sem catálogo/promo) e deixando a implementação mais flexível tornando possível evoluções futuras.

### 2. `title type=text` + `welcome_message type=textarea` vs. ambos `text`

- **Alternativa Considerada**: ambos `text`.
- **Por que a escolha adotada é melhor**: título é single-line, mensagem é multi-line; `textarea` dá UX melhor no admin e continua sendo "campo de texto". Ambos com `showInDefault/Website/Store=1` para multiloja.

### 3. `config.xml` + fallback no código vs. só um dos dois

- **Alternativa Considerada**: só `config.xml` ou só fallback hardcoded.
- **Por que a escolha adotada é melhor**: dupla proteção — `config.xml` preenche o admin na primeira carga; fallback no ViewModel cobre lojista que limpa o campo (critério "não quebra se vazio"). Custo zero, robustez máxima.

### 4. `ScopeConfigInterface` em `SCOPE_STORE` vs. outros escopos

- **Alternativa Considerada**: `SCOPE_WEBSITE` ou leitura sem escopo.
- **Por que a escolha adotada é melhor**: `SCOPE_STORE` respeita a hierarquia Default → Website → Store View (fallback nativo do Magento) e é o padrão para conteúdo de storefront.

### 5. `acl.xml` próprio vs. reusar `Magento_Config::config`

- **Alternativa Considerada**: sem ACL dedicada.
- **Por que a escolha adotada é melhor**: seção com `resource` inexistente dá 403 / some do admin dependendo do papel do usuário. Recurso próprio segue o padrão core (`Magento_Contact::contact`).

### 6. Sem `cacheable=false` / sem JS

- **Alternativa Considerada**: bloco não-cacheável para ver mudança instantânea.
- **Por que a escolha adotada é melhor**: `cacheable=false` derruba o full-page cache da home (anti-pattern). O critério já prevê "após limpar o cache" — `cache:clean` é o fluxo correto.

---

## Evidências

> **Terminal com `module:status` + `setup:upgrade` + `cache:clean`:**
>
> ```text
> Webjump_Gustavo : Module is enabled
> Nothing to import.
> Cleaned cache types: config, full_page
> ```
>
> <img width="1081" height="431" alt="image" src="https://github.com/user-attachments/assets/85874012-3522-4814-940d-ed579c686e4c" />
> <img width="1081" height="350" alt="image" src="https://github.com/user-attachments/assets/d9884294-e5fa-4061-83af-0ce0a1b386a2" />

---

> **Seção nova em Stores > Configuration (Webjump Gustavo > Bloco Home):**
> <img width="1836" height="624" alt="image" src="https://github.com/user-attachments/assets/0e475139-b6b3-4d5f-b30b-e58911c6dcac" />

---

> **Alterar valor no admin muda a home (após `cache:clean`):**

- **ANTES**

  > <img width="1151" height="391" alt="image" src="https://github.com/user-attachments/assets/b52cc838-0ac1-41c9-b501-2b74cf190c7c" />

- **DEPOIS (fluxo completo)**
  > <img width="2392" height="1043" alt="13 3" src="https://github.com/user-attachments/assets/4a7061f7-0ebc-4e35-b251-b3b2548ddf54" />

---

> **Campo vazio usa fallback sem quebrar + escape ativo:**

- **Campos limpos e cache flush**

  > <img width="1752" height="577" alt="image" src="https://github.com/user-attachments/assets/8beb507b-742c-4141-9136-a4a5cbbf6c6c" />
  > <img width="800" height="403" alt="image" src="https://github.com/user-attachments/assets/328670c2-714e-4fdc-8bdd-6e76b9034fd5" />

- **Refletindo no Storefront**

> <img width="1740" height="922" alt="image" src="https://github.com/user-attachments/assets/7f232cd6-5762-47fd-991c-2e929c416d1a" />

---

> **DI do ViewModel (`timezone` + `scopeConfig` resolvidos):**
>
> ```text
> DI configuration for the class Webjump\Gustavo\ViewModel\HomeBlock in the GLOBAL area
> timezone    | TimezoneInterface       | Magento\Framework\Stdlib\DateTime\Timezone
> scopeConfig | ScopeConfigInterface    |
> ```
>
> Ou seja tudo continua funcional, o home block continua instanciando corretamente com as 2 dependências após o refactor, e DI continua de pé funcionando como deveria.

> <img width="1161" height="577" alt="image" src="https://github.com/user-attachments/assets/5d264766-65d4-48fb-a01b-f4fbee0e3a37" />

---

## Checklist de Critérios de Aceite

- [x] **Existe uma seção nova em Stores > Configuration com o meu campo**
- [x] **Alterar o valor no admin muda o texto exibido na loja (após limpar o cache)**
- [x] **O valor tem um padrão definido, e o bloco não quebra se o campo estiver vazio**
