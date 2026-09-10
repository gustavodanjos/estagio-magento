# Desafio 13.2 - Estendendo o comportamento do catálogo

## Resumo

Neste desafio, o comportamento nativo do catálogo do Magento 2 foi estendido sem realizar nenhuma alteração nos arquivos do núcleo (`vendor/`). A implementação foi dividida em duas abordagens arquiteturais distintas:

1. Um **Plugin (Interceptor)** do tipo `after` para modificar dinamicamente a exibição do nome dos produtos no Storefront — com **filtro condicional por categoria**: o sufixo `" - Destaque Webjump"` é aplicado **apenas** a produtos que pertencem à categoria "Webjump" (ID 3).
2. Um **Observer** reagindo ao evento de salvamento de produto para gravar informações estruturadas no log do sistema.

Ambas as abordagens seguiram boas práticas de desenvolvimento no Magento, como isolamento de escopo (frontend vs global) e injeção de dependência.

---

## Objetivo

Aprender e aplicar os dois principais padrões do Magento 2 para estender o core (Plugins e Events/Observers), entendendo as diferenças conceituais entre eles, as armadilhas de escopo (como evitar persistência indevida de dados manipulados por plugins) e o uso do padrão PSR-3 para logging seguro e estruturado.

---

## Arquivos Adicionados ao Módulo `Webjump_Gustavo`

```text
app/code/Webjump/Gustavo/
├── etc/
│   ├── module.xml (Modificado: dependência Magento_Catalog adicionada)
│   ├── events.xml (Novo: Registro do Observer no escopo global)
│   └── frontend/
│       └── di.xml (Novo: Registro do Plugin restrito ao storefront)
├── Observer/
│   └── LogProductSaveAfter.php (Novo: Lógica do Observer e injeção do Logger)
└── Plugin/
    └── Catalog/
        └── Model/
            └── AddProductSuffixPlugin.php (Novo: Interceptação do método getName)
```

---

## Passo a Passo da Construção

1. **Inclusão de Dependência**:

   - Atualização do `etc/module.xml` para incluir `<sequence><module name="Magento_Catalog"/></sequence>`, garantindo que o módulo seja carregado após o módulo de Catálogo nativo.

2. **Implementação do Plugin (`AddProductSuffixPlugin`)**:

   - Criação da classe `AddProductSuffixPlugin` interceptando `Magento\Catalog\Model\Product::getName()` através do método `afterGetName`.
   - Verificação de pertencimento à categoria: o plugin checa se o ID da categoria Webjump (constante `WEBJUMP_CATEGORY_ID = 3`) está presente no array retornado por `$subject->getCategoryIds()`. Apenas produtos que pertencem a essa categoria recebem o sufixo.
   - Lógica defensiva com `str_contains` para garantir que o sufixo `" - Destaque Webjump"` seja anexado apenas uma vez e apenas se a string original não estiver vazia. Ambas as constantes (`SUFFIX` e `WEBJUMP_CATEGORY_ID`) são definidas como `const` na classe, evitando magic strings/números espalhados no código.
   - Declaração do plugin em `etc/frontend/di.xml` (restrito ao escopo frontend) para proteger o painel de administração e o banco de dados.

3. **Implementação do Observer (`LogProductSaveAfter`)**:

   - Criação da classe implementando `ObserverInterface` e injetando o `\Psr\Log\LoggerInterface` (padrão PSR-3 nativo).
   - Extração do objeto `$product` através de `$observer->getEvent()->getProduct()`.
   - Registro das informações (ID, SKU e Nome) no arquivo `var/log/system.log` usando `$this->logger->info()`.
   - Declaração do listener em `etc/events.xml` (escopo global) associado ao evento nativo `catalog_product_save_after`.

4. **Validação**:

   - Teste de sintaxe PHP (`php -l`) e compilação do container de DI (`setup:di:compile`).
   > <img width="100%" src="https://github.com/user-attachments/assets/97262b25-5d03-49c2-b00d-8dd65297e172"/>
   - Teste visual no Storefront (Produto e Categoria) e Teste de disparo salvando produto via Admin.

---

## Decisões Tomadas, Alternativas e Justificativas

### Escopo do Plugin: Proteção do Banco de Dados

- **Decisão**: Declarar o plugin no arquivo `etc/frontend/di.xml` em vez de `etc/di.xml`.
- **Justificativa**: O método `getName()` é chamado em várias áreas, inclusive ao renderizar o formulário de edição de produto no Admin. Se o plugin fosse declarado globalmente, o administrador veria o nome do produto com o sufixo no painel. Ao clicar em "Salvar", esse sufixo seria gravado definitivamente no banco de dados. Salvando o produto cinco vezes, o nome no banco viraria `Produto - Destaque Webjump - Destaque Webjump...`. O escopo `frontend` resolve isso isolando a modificação de interface apenas para a loja pública.

### Filtro por Categoria: Evitando Poluição Visual na Loja

- **Decisão**: Filtrar o sufixo apenas para produtos que pertencem à categoria **"Webjump" (ID=3)**, verificado via `$subject->getCategoryIds()`, em vez de aplicar a **todos** os produtos do Storefront.
- **Alternativa Rejeitada**: Filtrar pelo SKU (`CHAP-COU`) — funciona, mas é muito específico e não escala. Adicionando um segundo produto à categoria, seria necessário alterar o código.
- **Justificativa**: Filtrar por categoria é a abordagem correta no mundo real — você aplica comportamento diferenciado (ex: badge de promoção, sufixo de categoria, destaque) a um **conjunto lógico de produtos** e não a itens individuais. O método `getCategoryIds()` é eficiente porque o Magento já carrega os IDs de categoria no modelo do produto sem disparar uma query extra quando o produto já está hidratado.
- **Nota sobre o hardcode do ID**: O ID `3` está definido como constante de classe (`WEBJUMP_CATEGORY_ID`) com nome autoexplicativo. Trata-se de um ID fixo de ambiente de estudo. Em produção, essa configuração seria exposta via **Store Config** (System Configuration) para que o lojista escolha a categoria pelo admin sem precisar alterar código.

---

## Plugin × Observer

Esta é a decisão arquitetural central do desafio. No Magento 2, existem dois mecanismos distintos para estender o comportamento do core sem editar `vendor/`. Cada um tem um propósito bem definido:

|                       | Plugin (Interceptor)                                                                 | Observer (Evento)                                                                     |
| --------------------- | ------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------- |
| **O que faz?**        | Intercepta a chamada de um método e modifica sua entrada, saída ou fluxo             | Escuta um evento disparado pelo Magento e executa uma ação secundária                 |
| **Quando usar?**      | Quando você precisa**modificar o comportamento ou o retorno** de um método existente | Quando você precisa **reagir a algo que já aconteceu**, sem alterar o fluxo principal |
| **Como é declarado?** | `etc/di.xml` (ou `etc/frontend/di.xml` para escopo)                                  | `etc/events.xml`                                                                      |
| **Acoplamento**       | Direto ao método da classe                                                           | Indireto ao evento (desacoplado)                                                      |
| **Tipo de operação**  | Síncrona, dentro do fluxo da requisição                                              | Síncrona, mas sem alterar o resultado do fluxo                                        |

---

### Plugin — `AddProductSuffixPlugin`

Arquivo criado:

```text
Plugin/Catalog/Model/AddProductSuffixPlugin.php
```

O plugin intercepta o método:

```text
Magento\Catalog\Model\Product::getName()
```

através do interceptor:

```text
afterGetName(Product $subject, ?string $result): ?string
```

**O que ele faz nesta implementação:**
A cada vez que qualquer código no Magento chama `$product->getName()` no escopo do Storefront, o nosso método `afterGetName` é executado automaticamente logo após, recebendo o resultado original (`$result`) e podendo modificá-lo antes de devolver ao chamador. Neste caso, se o produto pertence à categoria **Webjump (ID=3)**, o sufixo `" - Destaque Webjump"` é concatenado ao nome. O produto `Chapéu de Couro` passa a ser exibido como:

```text
Chapéu de Couro - Destaque Webjump
```

A declaração foi feita em:

```text
etc/frontend/di.xml
```

> O escopo `frontend` garante que o sufixo **não aparece no Admin** e **não é salvo no banco de dados**, protegendo a integridade dos dados.

---

### Observer — `LogProductSaveAfter`

Arquivo criado:

```text
Observer/LogProductSaveAfter.php
```

O Observer escuta o evento:

```text
catalog_product_save_after
```

declarado em:

```text
etc/events.xml
```

**O que ele faz nesta implementação:**
Em vez de interceptar um método, o Observer fica aguardando o Magento **disparar um evento**. Toda vez que qualquer produto é salvo (pelo Admin, pela API ou pela CLI), o Magento dispara o evento `catalog_product_save_after`. Nosso Observer é notificado automaticamente, extrai o objeto `$product` do evento e registra no log:

```log
[Webjump_Gustavo] Product saved successfully. ID: 1, SKU: CHAP-COU, Name: "Chapéu de Couro"
```

O `LoggerInterface` é recebido via Dependency Injection no construtor, seguindo o padrão PSR-3 do Magento.

## Por que usei Plugin em um caso e Observer no outro?

### Plugin - `AddProductSuffixPlugin`

**Porque o objetivo era alterar um retorno:** eu precisava que getName() devolvesse um valor diferente do que está armazenado, sem tocar no dado original. Isso é exatamente o que um Plugin after resolve, ele intercepta a saída do método e permite modificá-la antes que ela chegue ao chamador. Um Observer não serviria aqui, porque eventos não alteram o valor de retorno de um método, eles só reagem a algo que já aconteceu. Se eu tivesse usado um Observer para esse caso, não haveria como "interceptar" a chamada de getName() e mudar o que é exibido no Storefront.

### Observer - `LogProductSaveAfter`

Porque o objetivo era reagir a um acontecimento (produto salvo) sem alterar o fluxo nem o resultado do salvamento. Eu não queria modificar nada que o Magento faz ao persistir o produto, só queria "escutar" que aquilo aconteceu e disparar uma ação paralela (gravar no log).

**Um Plugin seria a ferramenta errada aqui:** usar afterSave como Plugin para simplesmente logar seria acoplar desnecessariamente minha lógica ao método de salvamento, quando o próprio Magento já expõe um evento `catalog_product_save_after` desenhado exatamente para esse tipo de ação secundária e desacoplada.

### Resumindo o critério de decisão:

- Preciso mudar o que o método devolve ou como ele se comporta? → **Plugin**.
- Preciso apenas saber que algo aconteceu e agir em paralelo, sem interferir no resultado? → **Observer**.

---

## Evidências


> **Filtro por categoria: sufixo aplicado na listagem da categoria Webjump (Storefront)**
> <img width="100%" src="https://github.com/user-attachments/assets/78432225-233c-41e1-b790-f65a1b90fce8" />


> **Filtro por categoria (contraprova): produto de outra categoria SEM sufixo (Storefront)**
> <img width="1511" height="636" alt="image" src="https://github.com/user-attachments/assets/23de189f-463c-4b38-adef-0b77b43e2b47" />


> **Plugin visível na página de detalhes do produto (Storefront)**
> <img width="100%" src="https://github.com/user-attachments/assets/578ac6d2-603f-4fd4-8200-aafe42662bd0" />


> **Isolamento de Escopo: Produto sem o sufixo no Admin**
> <img width="100%" src="https://github.com/user-attachments/assets/18dfe07c-1651-4aa1-a49f-9a9e63bc3cc7" />


> **Disparo do Observer: Salvamento do Produto**
* **Produto salvo**
> <img width="100%" src="https://github.com/user-attachments/assets/9d2ee3b5-d3b4-48c3-a717-c9b677e688ad" />

* **Trecho de código que expõe o log do disparo do observer**
> <img width="100%" src="https://github.com/user-attachments/assets/36fedf26-f4b6-4ed6-9dce-8826cfade410" />


> **Log registrado em `var/log/system.log`**
* **Log comprovando disparo do observer após o salvamento**
> <img width="100%" src="https://github.com/user-attachments/assets/32ea8342-e7ec-4a0f-b0c9-650855a30742" />


> **Constantes de classe no Plugin (`AddProductSuffixPlugin.php`)**
> <img width="1120" src="https://github.com/user-attachments/assets/a2f18169-c522-4e22-b104-da39e82aadcc" />


> **Integridade do Core (`vendor/`) preservada**
> <img width="100%" src="https://github.com/user-attachments/assets/496cb51c-c12c-4037-b76b-1920c46fc1fa" />


---

## Checklist de Critérios de Aceite

- [x] O plugin está declarado no `di.xml` e funciona na loja
- [x] O observer está declarado em `events.xml` e dispara ao salvar um produto
- [x] A mensagem aparece no log (comprovada por print ou trecho do arquivo)
- [x] Nenhum arquivo dentro de `vendor/` foi modificado
- [x] O README responde detalhadamente: por que usei plugin em um caso e observer no outro?
