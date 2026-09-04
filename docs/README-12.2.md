# Desafio 12.2 - Explorando a loja e o catálogo

## Resumo

Neste desafio, trabalhei diretamente no admin do Magento e na linha de comando para colocar a mão na massa no catálogo e no conteúdo da loja. Criei uma categoria nova com um produto simples associado a ela, validei a exibição desse produto na loja (rodando reindex quando necessário e cache flush), montei uma página CMS com um bloco CMS exibido no site, e alterei uma configuração em Stores > Configuration para observar o impacto direto no storefront. Ao longo do processo, entendi na prática como Website, Store e Store View se relacionam entre si, o que está detalhado mais abaixo.

## Objetivo

Conhecer o admin do Magento na prática e entender como o catálogo se comporta, para não ficar perdido na hora de programar em cima dele. O trabalho foi feito direto no admin e na linha de comando, criando conteúdo real e observando o efeito de cada ação no site.

---

## 1. Categoria e produto criados

**O que foi feito:**

- Categoria criada: `Acessórios`
  - Caminho no menu: `Home > Acessórios`
  - URL Key: `acessorios`
- Produto simples criado e associado à categoria: `Chapéu de Couro`
  - SKU: `CHAP-COU`
  - Preço: `50.00`
  - Status: `Enable / In Stock`

> **Tela do admin mostrando a categoria criada (Catalog > Categories)**
> <img width="100%" src="https://github.com/user-attachments/assets/a5313561-fad7-4c18-a835-d98e3bf2eff1" />

> **Tela do admin mostrando o produto criado e associado à categoria.**
> <img width="100%" src="https://github.com/user-attachments/assets/ce926a9b-9e9b-4feb-93ca-35e4c1f410e6" />

---

## 2. Produto na loja

**O que foi feito:**

- Verifiquei a loja (storefront) e o produto não apareceu de imediato na categoria Acessórios.
- Rodei o comando de reindexação e de limpar o cache para que o produto passasse a aparecer:
  ```bash
  bin/magento indexer:reindex
  bin/magento cache:flush
  ```

> **Produto visível na loja (storefront), dentro da categoria criada**
> <img width="100%"  src="https://github.com/user-attachments/assets/67d4ad2d-f389-4580-889d-623b2f0beffd" />

> **Terminal com a saída do `bin/magento indexer:reindex`**
> <img width="100%"  src="https://github.com/user-attachments/assets/12c92336-060c-41e0-8566-84d85e50aff8" />

---

## 3. Página CMS e bloco CMS

**O que foi feito:**

- Página CMS criada: `Acessórios`
  - Caminho no admin: `Content > Pages`
  - URL Key: `acessorios-page`
- Bloco CMS criado: `Banner tt / banner_test`
  - Caminho no admin: `Content > Blocks`
- Local onde o bloco foi exibido: `inserido via widget na home page e referenciado dentro da página CMS criada`

> **Admin mostrando a página CMS criada (Content > Pages)**
> <img width="100%" src="https://github.com/user-attachments/assets/5048be27-429e-48d8-b102-6bab0689aa66" />

> <img width="100%" src="https://github.com/user-attachments/assets/cfe313cb-abf9-4181-80b3-ca10fbef93e9" />

> <img width="100%"  src="https://github.com/user-attachments/assets/3469bc90-43e6-45c6-a31b-73afd92dfbc1" />

> **Admin mostrando o bloco CMS criado (Content > Blocks)**
> <img width="100%"  src="https://github.com/user-attachments/assets/61671e29-5302-4d7f-a896-9f8a90d61cf5" />

> **Bloco CMS aparecendo no site (storefront)**
> <img width="100%" src="https://github.com/user-attachments/assets/71a8a5ec-7e70-4f73-b0f6-d8a7ea1612f7" />

---

## 4. Alteração em Stores > Configuration

**O que foi feito:**

- Configuração alterada: `Stores > Configuration > General > Currency Setup`
- Valor anterior: `US Dollar` → Valor novo: `Brazilian Real`
- Efeito observado no site: `Os preços/valores colocados nos produtos vinham por padrão configurado como Dólar. Com a alteração feita os valores setados para os produtos passaram a ser exibidos em Reais(R$) e não mais em dólares americanos($).`

> **Tela de Stores > Configuration com a alteração**
> <img width="100%" src="https://github.com/user-attachments/assets/190832ff-94e7-46b1-bc24-cfff5820ed92" />

> **Efeito da alteração visível no site**
> <img width="100%" src="https://github.com/user-attachments/assets/4b597e4b-a70c-4b64-a50b-ff2270a5af40" />

---

## 5. Website, Store e Store View: explicação com minhas palavras

- **Website:** é o nível mais alto da hierarquia. Normalmente representa um negócio/marca, com seu(s) próprio(s) domínio(s) e mesma(s) configuração(ões) de pagamento/checkout.

Ex.: Pensando na marca "kinder" → temos várias variações como: "Bueno", "Joy", "Surprise", etc. → O "Kinder" seria o que define o escopo de negócio de uma ou mais Stores, por exemplo: os pagamento para um único destinatário sendo estendido para todas as Stores "filhas".

Isso pode ficar melhor entendido nesse desenho da arquitetura:

```
Website: Kinder (Main)
  │
  ├── Store: Kinder Bueno
  │     ├── Store View: Português
  │     └── Store View: English
  │
  ├── Store: Kinder Surprise
  │     ├── Store View: Português
  │     └── Store View: English
  │
  └── Store: Kinder Joy
        ├── Store View: Português
        └── Store View: English
```

- É estrutural, não visual

- Define regras de negócio, não conteúdo → coisas como pagamento. Não define quais produtos aparecem (isso é papel da Store) nem qual idioma ou moeda (isso é papel da Store View).

- Agrupa, não separa por marca → várias marcas/sub-lojas podem estar sob o mesmo Website se compartilham a mesma operação comercial; só viram Websites separados quando a operação em si é isolada.

Ele não representa somente "uma versão do site" nem "uma marca", mas sim a infraestrutura comercial compartilhada por trás de uma ou várias lojas.

- **Store (Store Group):** define qual catálogo de produtos a loja utiliza, através da associação com uma root category determina qual catálogo de produtos será mostrado pela Store view e herda as regras de negócio vindas do Website: É o elo entre o Website e as Store Views.

Ex.: Pensando ainda no exemplo do kinder → pensando na hierarquia "Kinder/Kinder Bueno" → o "Bueno" teria uma root category(categoria raiz) com os produtos e categorias do Bueno, e isso define quais produtos/categorias aparecem naquela loja. → O "Joy" teria outra root category e "Surprise" outra diferente das duas anteriores também.

```
Store: Kinder Bueno
  │
  ├── Root Category: Bueno-Root
  │     ├── Categoria: Chocolates
  │     ├── Categoria: Wafers
  │     └── Categoria: Kits Presente
  │
  ├── Store View: Português
  └── Store View: English
```

- **Store View:** o nível mais abaixo da hierarquia, dentro de uma Store. Não define catálogo novo, os produtos e categorias já foram definidos no Store Group através da root category. O papel da Store View é decidir como esse mesmo catálogo é apresentado: idioma, moeda e outras variações de exibição.
  Ex.: Pensando ainda no exemplo do kinder → dentro da Store "Kinder Bueno", teríamos as Store Views "Português" e "English" → as duas mostram exatamente os mesmos produtos (a mesma root category do Bueno), só que uma renderiza textos/moeda/formatação em português, e a outra em inglês

```
Store: Kinder Bueno
  │
  ├── Store View: Português
  └── Store View: English
```

- É o único nível "visível" pro cliente → Website e Store são conceitos internos do admin; o cliente só interage com o resultado de uma Store View.

- Não define produtos, só exibição → não decide quais produtos existem (isso é papel da Store) nem regras de negócio como pagamento (isso é papel do Website).

- Não é "catálogo próprio" → se uma Store View mostrasse produtos diferentes das outras, isso na verdade significaria um catálogo diferente, e catálogo diferente é papel do Store Group, não da Store View.

Onde vi isso na prática: ao alterar a moeda de exibição dos produtos de dólar para real (em Stores > Configuration > General > Currency Setup), o efeito ficou visível direto na Store View, os preços passaram a ser exibidos em "Brazilian Real" na loja. Isso reforça bem o papel da Store View: a moeda é só uma forma diferente de apresentar o mesmo catálogo e os mesmos preços-base, sem alterar o produto em si nem o catálogo definido no Store Group.

---

## 7. Checklist de critérios de aceite

- [x] Criei uma categoria nova e um produto simples associado a ela
- [x] O produto aparece na loja (reindex rodado quando necessário)
- [x] Criei uma página CMS e um bloco CMS, e exibi o bloco em algum lugar
- [x] Alterei uma configuração em Stores > Configuration e vi o efeito no site
- [x] README explicando Website / Store / Store View com minhas próprias palavras
- [x] Prints comprovando cada item
