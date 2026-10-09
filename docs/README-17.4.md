# Desafio 17.4 - Caixão de Ofertas no Page Builder

## Resumo

Content type próprio "Caixão de Ofertas" (`offer_box`) no Page Builder, entregue pelo módulo dedicado `Webjump_OfferBox`. O componente aparece no painel em uma seção nova "Marketing", com ícone de caixão e rótulo em português, e tem sete campos configuráveis (título, descrição, imagem, tamanho da imagem, link, texto do botão e selo de desconto). O marketing monta o bloco promocional sozinho: arrasta, preenche o formulário, vê o resultado ao vivo no editor e publica — sem pedir nada ao time técnico. O componente tem a forma de um caixão: a arte completa (moldura roxa, miolo de madeira, teias, aranhas e morcegos) é um SVG de fundo, e o conteúdo (selo, título, descrição, imagem, botão) é distribuído como pôster sobre a madeira — identidade aplicada via override de LESS no tema `Webjump/halloween`.

---

## Objetivo

Criar um content type de Page Builder com configuração, formulário de edição, template de preview e template de master, com ao menos quatro campos configuráveis (texto, imagem e link entre eles), presente no painel com ícone e rótulo em português e solável dentro de linha e coluna.

---

## Arquitetura

```text
Page Builder (adminhtml)
  ├─ menu_section.xml                         # Seção nova "Marketing" (sortOrder 40)
  ├─ content_type/offer_box.xml                # Tipo "offer_box": label PT, ícone, form, appearance "default"
  │    ├─ 9 elements: main, wrapper, image, link, empty_link,
  │    │            title, desc, badge, button
  │    ├─ Readers/converters nativos reutilizados:
  │    │    property/link + attribute/link-href|target|type   (link)
  │    │    attribute/src + preview/src                       (imagem)
  │    │    html/tag-escaper                                   (textos)
  │    └─ alt da imagem derivado do título via storage_key
   ├─ ui_component/webjump_offer_box_form.xml   # 7 campos visíveis: input, textarea, imageUploader,
   │                                            #   select (image_size), urlInput, input, input
   │                                            #   + campo oculto appearance (default="default")
   │                                            #   image_size persiste como data-image-size no main
   ├─ layout/webjump_offer_box_form.xml          # Layout handle: liga o uiComponent ao request
   │                                            #   mui/index/render_handle que carrega o form no modal
   └─ web/template/content-type/offer-box/default/
       ├─ preview.html                        # div externo .pagebuilder-content-type + menu de opções
       └─ master.html                         # <a if=link> / <ifnot empty_link> com o card duplicado
                                               #   ordem do conteúdo: selo, título, descrição, imagem, botão

Storefront (frontend)
   ├─ view/base/web/images/caixao.svg                              # Arte completa do caixão (moldura + madeira + decoração)
   ├─ view/frontend/web/css/source/content-type/offer_box/_default.less   # estrutura (variáveis da UI lib)
   └─ Tema Halloween: Webjump_OfferBox/web/css/source/_extend.less       # identidade (arte do caixão + tipografia)
```

O master format é persistido como HTML estático (com atributos `data-content-type`, `data-appearance` e `data-element` por elemento) no conteúdo da CMS page; o reader `master-format/read/configurable` reconstrói os dados do formulário a partir desses atributos ao reabrir o editor. Consequência: mudanças de template só chegam à loja quando o card é reaberto e salvo de novo (o editor re-serializa o master na salvada); o preview do editor, por renderizar a partir dos dados, já mostra a nova estrutura imediatamente.

---

## Estrutura de Arquivos

### Novos

```text
app/code/Webjump/OfferBox/
├── registration.php                                              # Registro do módulo
├── etc/module.xml                                                # sequence: Magento_PageBuilder
├── etc/di.xml                                                    # Whitelist WYSIWYG: libera data-image-size no save
├── view/base/web/images/caixao.svg                               # Arte do caixão (publica em frontend E adminhtml)
└── view/adminhtml/
    ├── pagebuilder/
    │   ├── menu_section.xml                                      # Seção "Marketing" (sortOrder 40)
    │   └── content_type/offer_box.xml                            # Config do content type + appearance
    ├── ui_component/webjump_offer_box_form.xml                   # Formulário (7 campos PT-BR)
    ├── layout/webjump_offer_box_form.xml                         # Handle que renderiza o form no modal
    └── web/
        ├── css/source/_module.less                              # Ícone do painel (mask SVG) + espelho do caixão
        │                                                          #   no preview (fundo, zona segura, tamanhos, largura máxima)
        ├── images/coffin.svg                                    # Caixão do tema Halloween (ícone do painel)
        └── template/content-type/offer-box/default/
            ├── preview.html                                     # Preview no editor
            └── master.html                                      # Master format salvo na página

app/code/Webjump/OfferBox/view/frontend/web/css/source/
├── _module.less                                                  # Importa o content type
└── content-type/offer_box/_default.less                          # Estilos estruturais do card

app/design/frontend/Webjump/halloween/Webjump_OfferBox/web/css/source/
└── _extend.less                                                  # Identidade: caixão SVG de fundo, zona segura,
                                                                   #   título âmbar, botão pill, largura máxima (560px)
```

### Alterados

Nenhum arquivo existente foi alterado. Nenhum arquivo em `vendor/` ou do Luma foi alterado.

---

## Decisões de Implementação e Justificativas

### 1. Módulo dedicado `Webjump_OfferBox` em vez de estender `Webjump_Gustavo`

Content type é uma feature autocontida (config, form, templates, LESS). Um módulo próprio separa a responsabilidade, pode ser desabilitado/reusado isoladamente e não incha o módulo de reviews/catálogo. A alternativa descartada era continuar acumulando tudo no mesmo módulo dos desafios anteriores.

### 2. Seção nova "Marketing" em vez de reusar seção nativa

O desafio pede o componente "na seção escolhida" para o time de marketing. Uma seção própria (`menu_section.xml`) deixa o agrupamento explícito e demonstra a capacidade de estender o painel. A alternativa descartada era encaixar em `media` ou `add_content`, misturando o caixão com tipos genéricos.

### 3. Aparência única `default` em vez de múltiplas

Layout fixo (imagem no topo, conteúdo abaixo, botão à direita) com um par preview/master. Cada aparência adicional dobraria templates e config; o inversor de imagem fica para o marketing resolver com CSS classes. A alternativa descartada era o padrão collage-left/right do Banner nativo.

### 4. Rótulos hardcoded em PT-BR em vez de translate + CSV

O critério exige rótulo em português no painel. Hardcode garante o resultado em qualquer locale do admin, sem depender de CSV nem do idioma da conta. A alternativa descartada era `translate="true"` + `i18n/pt_BR.csv`, que falharia com o admin em en_US.

### 5. Reutilização dos readers/converters nativos em vez de JS próprio

Link (property/link + link-href/target/type), imagem (attribute/src com preview/src) e textos (html/tag-escaper) usam exatamente a mesma infraestrutura do Banner e do Image nativos. Nenhum componente JS novo foi criado — o preview base (`Magento_PageBuilder/js/content-type/preview`) já entrega menu de opções e eventos. A alternativa descartada era escrever preview component custom, duplicando comportamento que o core já resolve.

### 6. `alt` da imagem derivado do título via `storage_key`

O elemento `image` declara `<attribute name="alt" storage_key="title" source="alt" persistence_mode="write"/>`: o que é digitado no título alimenta o alt no HTML salvo (acessibilidade/SEO de graça, sem sétimo campo). É o mesmo mecanismo dos atributos virtuais de link do Banner.

### 7. Ícone via CSS mask com o `coffin.svg` do tema em vez de novo glifo no font do core

O painel usa um font de ícones (`pagebuilder-icons`) que exigiria pipeline de build de font para ganhar um glifo novo. A classe `.icon-pagebuilder-offer-box:before` com `mask` + `currentColor` herda a cor e o tamanho do painel e reaproveita o caixão já desenhado para a campanha. A alternativa descartada era reusar o ícone do Banner (duplicado e sem identidade).

### 8. Estilo em duas camadas: estrutura no módulo, identidade no tema

O módulo estiliza o card com variáveis da UI library (`@button-primary__background`, `@text__color__muted` etc.) — neutro e funcional em qualquer tema. O tema Halloween sobrescreve com a identidade da campanha: a arte do caixão como fundo, zona segura percentual, título âmbar e botão pill, mantendo a tipografia Cormorant Garamond da loja. O SVG da arte mora no módulo (`view/base/web/images`) por necessidade técnica — a área base publica em todas as árvores estáticas, então o mesmo caminho atende a CSS do tema (frontend) e o espelho do editor (adminhtml); temas não publicam na árvore do admin. O critério "segue a identidade do tema" fica satisfeito na camada certa. A alternativa descartada era hardcodar o visual Halloween dentro do módulo.

### 9. Página de campanha montada manualmente no Admin

A publicação da página de campanha é o próprio fluxo que o desafio quer provar (marketing sozinho, sem time técnico). A alternativa descartada era data patch escrevendo master format na mão, que é frágil e não demonstra nada.

### 10. Tamanho da imagem como atributo `data-image-size` + CSS em vez de style converter

O campo "Tamanho da Imagem" (Grande/Média/Pequena) persiste como atributo no elemento `main` e o CSS aplica a escala: Grande ocupa a largura do container; Média/Pequena limitam a altura (320px/200px) com `width: auto` + `max-width: 100%` + `margin: 0 auto`, o que preserva a proporção da imagem e a centraliza — sem cortar. Reusa o mecanismo padrão de atributos do Page Builder (ler/gravar no roundtrip, igual `data-appearance`), sem converter JS novo. A alternativa descartada era converter o valor em `style="max-height"` inline, que exigiria um componente JS dedicado.

### 11. Caixão como arte SVG de fundo em vez de clip-path/CSS puro

A forma do caixão vem de um único SVG autoral (viewBox 400×440: moldura roxa com brilho, miolo de tábuas com filete laranja, teias nos 4 cantos, 2 aranhas e 3 morcegos fora da silhueta), aplicado como `background` do wrapper com `background-size: 100% 100%`. O conteúdo fica numa zona segura com `padding` percentual derivado do miolo do SVG (`20% 21% 10%`, nas coordenadas da arte); `aspect-ratio: 400 / 440` assegura o piso de proporção — o pôster nunca fica achatado e cresce quando o conteúdo pede. As alternativas descartadas eram recriar a arte com `clip-path` + camadas CSS (fidelidade menor e muito mais regra) e travar a proporção com `overflow: hidden` (cortaria texto).

### 12. Largura máxima fixa (600px) centralizada em vez de percentual da coluna

O wrapper herdava a largura inteira da coluna (1280px na página de campanha → 1408px de altura), tomando a primeira dobra. Um `max-width` fixo com `margin: 0 auto` devolve a escala de pôster da referência com proporção exata da arte SVG (400×440). Medido na loja via Playwright: **600×660** em 1280px e 390×429 em 390px. O valor vive numa variável nomeada (`@offer-box-coffin__max-width`), no tema e no espelho do editor, para ajustar em um único lugar por área. A alternativa descartada era percentual da coluna, que muda de tamanho conforme o container onde o card é solto e não limita nada em colunas largas.

---

## Problemas Encontrados e Correções

- **Spinner infinito no modal do editor:** falta do layout handle `webjump_offer_box_form.xml` impedia o bloco do formulário de ser carregado (resposta vazia) — corrigido adicionando o handle equivalente ao dos tipos nativos.
- **Salvamento travado (renderingLock eterno):** comentários KO órfãos e `</div>` extras nos templates quebravam o render do master no iframe — corrigido rebalanceando master/preview. Templates versionados antigos em abas abertas também exigiam hard-refresh após deploy.
- **Alinhamento central ignorado:** `text-align: left` explícito no content vencía o `text-align` herdado do `main` (e preview não fazia bind do main) — removido e feito bind no preview.
- **Corte de imagem (Média/Pequena):** regra base com `width: 100%` + `object-fit: cover` cortava quando limitado por `max-height`. Corrigido para `width: auto; max-width: 100%` e centralizado (`margin: 0 auto`); espelhado no admin.
- **Tamanhos da imagem não funcionavam (Grande/Média/Pequena):** `flex: 1 1 0` no `.pagebuilder-offer-box-image` forçava crescimento total — alterado para `flex: 0 1 auto`, liberando os `max-height` das classes `[data-image-size]`. Ajustado padding superior (30%→21%) para ganhar espaço para o chapéu em Grande.
- **Aspect-ratio cedia ao min-content:** imagem contribuía com altura completa; fix com `flex: 1 1 0` + `min-height: 0` na imagem (conteúdo) + `flex: 0 1 auto` revisto para permitir controle por tamanho. Resultado: wrapper exato 600×660 (desktop) e 390×429 (mobile).
- **SVG não carregava no frontend:** arte movida de `view/adminhtml/web/images` para `view/base/web/images` (área base publica em todas as árvores estáticas).
- **Warning de validação WYSIWYG ao salvar:** `data-image-size` não estava na whitelist de `DefaultWYSIWYGValidator` — adicionado via `etc/di.xml` no módulo.
- **Requisito "traduzido" não atendido no rótulo?** nada — mantido rótulo PT-BR explícito (decisão 4). 

---

## Onde o Caixão de Ofertas aparece

---

## Onde o Caixão de Ofertas aparece

| Superfície | O quê |
|---|---|
| Painel do Page Builder | Seção "Marketing", ícone de caixão, rótulo "Caixão de Ofertas" |
| Editor | Preview ao vivo com menu de opções; formulário lateral com 7 campos |
| Loja | Card renderizado dentro de linha/coluna em qualquer CMS page (ex.: página de campanha) |

Fora do escopo: instâncias do componente em outros temas (herdam só a camada estrutural, sem a arte do caixão) e widgets de CMS fora do Page Builder. Risco conhecido: o rótulo hardcoded não se traduz sozinho para outros idiomas — decisão consciente do item 4.

---

## Evidências

### 1. Painel do Page Builder

> **Seção "Marketing" com ícone de caixão e rótulo "Caixão de Ofertas" no painel lateral**
>
> <img width="203" height="482" alt="image" src="https://github.com/user-attachments/assets/1be7294a-e651-4fd4-a0de-7c25e8775a1e" />


### 2. Formulário de edição

> **Componente arrastado para dentro de linha/coluna com os campos preenchidos (título, descrição, imagem, tamanho da imagem, link, botão, selo)**
>
> <img width="1701" height="926" alt="image" src="https://github.com/user-attachments/assets/3d9b4a33-9607-40ec-a95f-5944efc0bf1e" />
> <img width="1701" height="926" alt="image" src="https://github.com/user-attachments/assets/2d9fc324-23d3-43a3-9094-871249f67ef3" />


### 3. Preview no editor

> **Card renderizado ao vivo no editor; inspeção mostrando `class="pagebuilder-content-type ..."` no elemento externo do preview**
>
> <img width="1824" height="876" alt="image" src="https://github.com/user-attachments/assets/f07da7ea-e7d7-4f14-91f6-69d2d3380cb7" />


### 4. Loja

> **Página publicada com o card em forma de caixão na vitrine: arte SVG de fundo (moldura roxa, madeira, teias, aranhas, morcegos), largura limitada a 600px e centralizado na coluna, título âmbar em Cormorant Garamond, botão pill e imagem centralizada**
>
> <img width="1824" height="991" alt="image" src="https://github.com/user-attachments/assets/1540cb47-af42-4f80-9e04-d46517598b5d" />


### 5. Página de campanha publicada

> **CMS Page de campanha habilitada (grid de Content > Pages)**
>
> <img width="2386" height="1114" alt="pages" src="https://github.com/user-attachments/assets/e7f01139-306d-4bbe-9065-943ee1eb7f1d" />

### 6. A classe pagebuilder-content-type está no elemento externo do preview

> <img width="1852" height="1004" alt="image" src="https://github.com/user-attachments/assets/b69385f7-7f80-4fc0-86ce-18aa016ce3ad" />

### 7. Demonstração do fluxo completo do desafio

> **Arrastar para a página, configurar e ver o resultado no editor**
> [17.4-1.webm](https://github.com/user-attachments/assets/49e4a40a-7a44-4d62-a807-00a59a3c9c05)

> **O que é configurado no editor é o que aparece na loja**
> [17.4-2.webm](https://github.com/user-attachments/assets/e9d67ae3-d95e-45c1-8fec-9b7ae56047b5)

> **O estilo do componente segue a identidade do tema**
> <img width="1842" height="868" alt="image" src="https://github.com/user-attachments/assets/651c62e5-39ef-401e-866d-42a9616ff6bf" />


---

## CRITÉRIO DE ACEITE

- [x] O componente aparece no painel do Page Builder, na seção escolhida
- [x] Dá para arrastar para a página, configurar e ver o resultado no editor
- [x] O que é configurado no editor é o que aparece na loja
- [x] A classe pagebuilder-content-type está no elemento externo do preview
- [x] O estilo do componente segue a identidade do tema
- [x] Montei uma página de campanha usando o componente, e ela está publicada
