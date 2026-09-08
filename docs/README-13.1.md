# Desafio 13.1 - Primeiro módulo com bloco na home

## Resumo

Neste desafio, desenvolvi um módulo Magento 2 completo criado do zero, denominado `Webjump_Gustavo`. O módulo injeta um bloco customizado na página inicial da loja (`cms_index_index`), estruturado de forma desacoplada segundo as melhores práticas do Magento 2.4+:
- Toda a lógica de apresentação e formatação de dados foi concentrada em um **ViewModel** dedicado (`ArgumentInterface`);
- O template `.phtml` atua de forma estritamente passiva (sem regras de negócio), com HTML semântico e proteção rigorosa contra vulnerabilidades via `$escaper->escapeHtml()`;
- O layout XML faz uso da classe genérica nativa `Magento\Framework\View\Element\Template`, eliminando a necessidade de heranças ou Blocks customizados desnecessários;
- Foi criado e associado um arquivo CSS modular próprio em `view/frontend/web/css/home-block.css`, carregado no `<head>` da página inicial e com estilos aplicados ao bloco.

---

## Objetivo

Compreender na prática a anatomia de um módulo Magento 2, o funcionamento do Layout XML na injeção de blocos no storefront, e dominar a separação de responsabilidades utilizando **ViewModels** (em detrimento da abordagem legada de blocos monolíticos). Além disso, praticar princípios de Clean Code, SOLID, HTML semântico, segurança com escape de dados e estilização modular em CSS.

---

## Estrutura de Pastas Criada

```text
app/code/Webjump/Gustavo/
├── etc/
│   └── module.xml
├── ViewModel/
│   └── HomeBlock.php
├── view/
│   └── frontend/
│       ├── layout/
│       │   └── cms_index_index.xml
│       ├── templates/
│       │   └── home_block.phtml
│       └── web/
│           └── css/
│               └── home-block.css
└── registration.php
```

### Explicação de cada componente:

- **`registration.php`**: Arquivo na raiz do módulo responsável por registrar o componente `Webjump_Gustavo` no pool de módulos conhecidos pelo Magento via `\Magento\Framework\Component\ComponentRegistrar`.
- **`etc/module.xml`**: Declara a existência do módulo, seu nome formal (`Webjump_Gustavo`) e sua versão de esquema (`setup_version="1.0.0"`).
- **`ViewModel/HomeBlock.php`**: Classe PHP que implementa `\Magento\Framework\View\Element\Block\ArgumentInterface`. É onde reside toda a lógica de negócio e preparação de dados para a camada visual (injeção de fuso horário da loja, títulos, mensagens formatadas e lista de destaques).
- **`view/frontend/layout/cms_index_index.xml`**: Instrução de layout aplicada exclusivamente à página inicial da loja. Adiciona a folha de estilos no `<head>`, referencia o container `content`, instancia o bloco nativo `Template` e injeta o `HomeBlock` ViewModel como argumento.
- **`view/frontend/templates/home_block.phtml`**: Template de visualização puro. Não realiza consultas, cálculos ou regras de negócio; apenas recebe o ViewModel, itera sobre os dados e renderiza as tags semânticas, garantindo escape em cada saída.
- **`view/frontend/web/css/home-block.css`**: Folha de estilos própria do componente, com classes no padrão BEM (`.webjump-home-block*`) para evitar vazamento de estilos para outros elementos do tema.

---

## Passo a Passo da Construção

1. **Definição e Registro do Módulo**:
   - Criação de `registration.php` invocando `ComponentRegistrar::register`.
   - Criação de `etc/module.xml` declarando `Webjump_Gustavo` compatível com o schema `module.xsd`.

2. **Criação do ViewModel (`ViewModel/HomeBlock.php`)**:
   - Implementação de `ArgumentInterface` para permitir injeção nativa via Layout XML.
   - Injeção da dependência `TimezoneInterface` para buscar a hora do escopo da loja e formatá-la via `formatDateTime()`.
   - Criação de métodos com responsabilidade única (`getTitle`, `getWelcomeMessage`, `getFormattedCurrentDate`, `getHighlights`).

3. **Configuração do Layout XML (`view/frontend/layout/cms_index_index.xml`)**:
   - Declaração do CSS modular via `<css src="Webjump_Gustavo::css/home-block.css"/>` dentro do nó `<head>`.
   - Referenciamento do container `<referenceContainer name="content">`.
   - Declaração do bloco com a classe padrão `Magento\Framework\View\Element\Template` e injeção do ViewModel via nó `<arguments>`.

4. **Elaboração do Template (`view/frontend/templates/home_block.phtml`)**:
   - Recuperação do ViewModel via `$block->getData('view_model')`.
   - Validação defensiva de instância com retorno antecipado (*early return*).
   - Marcação com HTML semântico (`<section>`, `<header>`, `<h2>`, `<p>`, `<time>`, `<ul>`, `<li>`).
   - Escapamento de todas as variáveis dinâmicas através de `$escaper->escapeHtml()`.

5. **Estilização Modular em CSS (`view/frontend/web/css/home-block.css`)**:
   - Definição de layout com borda lateral de destaque, cantos arredondados, fundo suave, badge estilizada e tipografia harmônica.
   - Sem uso de CSS inline.

6. **Ativação e Validação no Magento**:
   - Habilitação do módulo: `bin/magento module:enable Webjump_Gustavo`.
   - Atualização do banco e registro: `bin/magento setup:upgrade`.
   - Limpeza de cache: `bin/magento cache:flush`.
   - Verificações de sintaxe com `php -l` e validação XML.
   - Validação via cURL e inspeção visual no navegador.

---

## Decisões Tomadas, Alternativas e Justificativas

### 1. Uso de ViewModel (`ArgumentInterface`) vs. Block Customizado (Herança)
- **Alternativa Considerada**: Criar uma classe `Webjump\Gustavo\Block\HomeBlock` estendendo `\Magento\Framework\View\Element\Template`.
- **Por que a escolha adotada é melhor**:
  - Estender classes de Block traz uma hierarquia de herança pesada e desnecessária (`AbstractBlock`, `Template`), acoplando o código a métodos de renderização e ciclo de vida do Magento.
  - O **ViewModel** segue os princípios de responsabilidade única (SRP) e composição sobre herança. Ele atua como um POJO/serviço leve, tornando a classe mais fácil de testar, manter e reutilizar. Essa é a abordagem recomendada oficialmente pelo Magento desde a versão 2.2.

### 2. Uso do Bloco Nativo `Template` vs. Bloco Dedicado Vazio
- **Alternativa Considerada**: Criar um arquivo PHP de Block apenas para configurar o template.
- **Por que a escolha adotada é melhor**:
  - Aplica o princípio **YAGNI** (*You Aren't Gonna Need It*). O bloco nativo `Magento\Framework\View\Element\Template` já possui todos os recursos necessários para renderizar o `.phtml` e repassar os argumentos (ViewModels). Criar um Block customizado vazio geraria código morto.

### 3. Injeção de CSS via Layout XML `<head>` vs. `.less` ou CSS Inline
- **Alternativas Consideradas**:
  - *CSS Inline*: Estilos direto na tag `style` do `.phtml`. (Péssima prática: viola separação de camadas e políticas de Content Security Policy - CSP).
  - *Arquivo `_module.less`*: Utilizar pré-processador LESS do Magento. (Mais burocrático, exige compilação estática de temas e acopla a compilação ao Grunt/deploy estático global).
- **Por que a escolha adotada é melhor**:
  - Declarar `<css src="Webjump_Gustavo::css/home-block.css"/>` dentro do `<head>` do arquivo `cms_index_index.xml` carrega o arquivo de forma declarativa e sob demanda **apenas** na página inicial, evitando desperdício de banda em outras páginas e mantendo o módulo 100% autocontido.

### 4. Escapamento Rigoroso com `$escaper->escapeHtml()`
- **Alternativa Considerada**: Impressão direta `<?= $viewModel->getTitle() ?>`.
- **Por que a escolha adotada é melhor**:
  - Em aplicações de comércio eletrônico, a proteção contra ataques XSS (Cross-Site Scripting) é mandatória. O uso explícito do `$escaper->escapeHtml()` garante conformidade com o Magento Coding Standard e assegura que qualquer caractere especial seja sanitizado antes de chegar ao navegador.

### 5. HTML Semântico e Nomenclatura BEM
- **Alternativa Considerada**: Estruturar tudo com tags `<div>` genéricas e IDs soltos.
- **Por que a escolha adotada é melhor**:
  - O uso de `<section>`, `<time>`, `<header>`, `<ul>` enriquece a acessibilidade para leitores de tela e melhora o SEO.
  - A convenção BEM (`.webjump-home-block__title`, etc.) garante que as regras CSS fiquem isoladas, sem riscos de sobrescrever acidentalmente estilos de outros módulos ou do tema Luma.

---

## Problemas Encontrados e Soluções Tomadas

1. **Garantir a publicação correta do asset CSS em ambiente de desenvolvimento**:
   - *Cenário*: Ao adicionar um arquivo CSS novo em `view/frontend/web/css/`, o servidor pode demorar a gerar o symlink em `pub/static` dependendo do cache.
   - *Solução*: Executou-se `bin/magento cache:flush` e verificou-se via cURL a resposta HTTP 200 direta do asset estático (`https://magento.test/static/version.../Webjump_Gustavo/css/home-block.css`), confirmando a publicação imediata pelo Nginx/Magento.

2. **Evitar lógica residual no template `.phtml`**:
   - *Cenário*: Havia a tentação de formatar a data diretamente no template usando funções nativas do PHP como `date()`.
   - *Solução*: Foi injetado o `TimezoneInterface` no construtor do `HomeBlock` ViewModel, transferindo 100% da responsabilidade de cálculo, timezone e formatação para o PHP no ViewModel. O template apenas exibe o valor formatado.

---

## Evidências

> **Terminal com a saída de `bin/magento module:status Webjump_Gustavo` confirmando o módulo como ativo:**
> *Terminal exibindo `Webjump_Gustavo : Module is enabled`*
> <img width="100%" src="https://github.com/user-attachments/assets/1fc1e9a4-e162-44db-a02b-2e56ee2c0e0e" />


---

> **Bloco visível e estilizado na Home da loja (storefront em `https://magento.test/`):**
> <img width="100%" src="https://github.com/user-attachments/assets/94f1eb09-c0bb-4ccb-9f6f-19a34122a1cf" />

---

> **Terminal comprovando o carregamento do arquivo CSS com status HTTP 200:**
> <img width="995" height="349" alt="image" src="https://github.com/user-attachments/assets/8caa9a75-9f87-4281-b904-e1ad5395a0e8" />



> **Código do ViewModel e do Template evidenciando separação de camadas e uso de `escapeHtml()`:**
> <img width="1816" height="980" alt="image" src="https://github.com/user-attachments/assets/dff9f20a-d88a-4bde-a5cd-6e0e3dde7eed" />


> **Para testar a funcionalidade do Timezone modifiquei para o Timezone de America/Sao_Paulo**
> *Modificando do padrão `America/New_York` para `America/Sao_Paulo` e evidenciando antes e depois*
> <img width="100%" src="https://github.com/user-attachments/assets/17483f30-2ba0-4d26-b9b4-c24f7a1baec2" />

---

## Checklist de Critérios de Aceite

- [x] **O módulo aparece como ativo em `bin/magento module:status`**
- [x] **O bloco aparece na home da loja**
- [x] **A lógica está no ViewModel, não no template nem no Block**
- [x] **Toda saída passa por `escapeHtml()` ou equivalente**
- [x] **O módulo tem CSS próprio em `view/frontend/web/` aplicado ao bloco**
- [x] **README explica a estrutura de pastas criada**
