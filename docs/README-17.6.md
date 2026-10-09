# Desafio 17.6 - Seção própria de customer-data

## Resumo

Seção própria de `customer-data` chamada `scare-counter`, criada no módulo `Webjump_Gustavo` e exibida por um componente no tema `Webjump/halloween`. Cada visitante tem um contador de sustos por sessão: uma abóbora aparece aleatoriamente na página e, ao ser clicada, dispara um jumpscare e um badge flutuante com o total de sustos. O valor é individual por visitante, atualiza sem recarregar a página e nenhum dado individual é impresso no HTML pelo PHP — a entrega do dado é feita exclusivamente pelo endpoint `/customer/section/load`.

## Objetivo

Implementar uma seção de `customer-data` de ponta a ponta: registrar o `sectionSourceMap` e a fonte (`SectionSourceInterface`), declarar uma ação (`halloween/scare`) que a invalida e ter um componente frontend que lê e exibe a seção sem reload e sem o PHP imprimir dados individuais na página.

## Arquitetura

```text
Webjump_Gustavo (módulo)
├─ etc/frontend/routes.xml            → frontName "halloween"
├─ etc/frontend/sections.xml          → action halloween/scare invalida a seção scare-counter
├─ etc/frontend/di.xml                → sectionSourceMap: scare-counter → ScareCounter
├─ CustomerData/ScareCounter.php      → SectionSourceInterface, lê a sessão do cliente
├─ Controller/Scare/Index.php         → POST incrementa a sessão e devolve JSON
└─ view/frontend/
   ├─ layout/default.xml              → bloco scare_counter em before.body.end
   ├─ templates/scare_counter.phtml   → <div id="scare-counter"> com data-scare-url e data-scare-images
   └─ web/js/scare-counter.js         → subscribe em customerData.get('scare-counter') e exibe o badge

Webjump/halloween (tema)
├─ web/js/halloween-fx/scare.js       → spawn da abóbora, jumpscare (alterna 3 SVGs), POST e temporizadores
├─ web/js/halloween-fx/init.js        → inicia scare.init e desliga em /checkout e /customer
└─ web/css/source/extend/_halloween-fx.less → estilos do badge, da abóbora e do jumpscare
```

Fluxo: cliente clica na abóbora → `halloween-fx/scare.js` dispara o jumpscare (alternando `jumpscare.svg` → `jumpscare2.svg` → `jumpscare3.svg`) e faz POST em `/halloween/scare` (AJAX com `X-Requested-With`) → `Controller/Scare/Index` incrementa a sessão → o núcleo `customer-data.js` recarrega a seção via `/customer/section/load?sections=scare-counter` → `ScareCounter::getSectionData()` devolve `{count: N}` → o `subscribe` do componente pinta o badge.

## Estrutura de Arquivos

**Novos:**

```text
src/app/code/Webjump/Gustavo/
├── Controller/Scare/Index.php                        # Ação POST que incrementa a sessão e devolve JSON
├── CustomerData/ScareCounter.php                     # SectionSourceInterface (lê a sessão do cliente)
├── Test/Integration/CustomerData/ScareCounterTest.php# Testes de integração da seção e da ação
├── etc/frontend/
│   ├── routes.xml                                    # Rota do frontName "halloween"
│   └── sections.xml                                  # Invalidação da seção pela action declarada
├── view/frontend/
│   ├── templates/scare_counter.phtml                 # Container vazio #scare-counter + x-magento-init
│   └── web/js/scare-counter.js                       # Componente que lê a seção e mostra o badge
src/app/design/frontend/Webjump/halloween/
└── web/js/halloween-fx/scare.js                      # Abóbora, jumpscare (3 SVGs alternados) e POST (FX do tema)
```

**Alterados:**

```text
src/app/code/Webjump/Gustavo/
├── etc/frontend/di.xml                               # Adiciona "scare-counter" ao sectionSourceMap
├── etc/module.xml                                    # Magento_Customer no <sequence>
└── view/frontend/layout/default.xml                  # Inclui o bloco scare_counter
src/app/design/frontend/Webjump/halloween/
├── web/js/halloween-fx/init.js                       # Chama scare.init junto com bats.init
└── web/css/source/extend/_halloween-fx.less          # Estilos do scare (badge, abóbora, jumpscare)
```

Nenhum arquivo em `vendor/` ou do Luma foi alterado.

## Decisões de Implementação e Justificativas

1. **Seção registrada em `etc/frontend/di.xml` via `sectionSourceMap`** — o mapa do `SectionPoolInterface` é o mecanismo nativo que liga o nome da seção à classe fonte. Foi seguido o mesmo padrão do core (`module-customer/etc/frontend/di.xml`), que adiciona `cart`, `customer` etc. no escopo frontend.
2. **Persistência na sessão do cliente** — o `ScareCounter` grava em `Magento\Customer\Model\Session` a chave `webjump_gustavo_scare_counter`. Alternativas descartadas: cookie próprio e storage client-side. Sessão PHP garante valor individual por visitante (funciona também anônimo, sem login) e integra com a prateleira de `customer-data`.
3. **POST sem `form_key`, valendo a isenção de XHR do `CsrfValidator`** — o `Magento\Framework\App\Request\CsrfValidator` só valida form key em POST que não seja XMLHttpRequest, e o jQuery envia `X-Requested-With: XMLHttpRequest` por padrão. Alternativa descartada: renderizar o form key no HTML — `$block->getFormKey()` em um `Template` puro é chamada mágica do `DataObject` (retorna vazio) e, na página servida pelo FPC, o form key cacheado não corresponde à sessão nova (resultava em "Invalid Form Key").
4. **Abóbora com o SVG já existente do tema via CSS** — o botão `.scare-pumpkin` usa o mesmo `loader-pumpkin.svg` da loading mask como `background-image`. Descarta o SVG inline no JS e mantém a divisão de responsabilidade: o módulo cuida do backend/seção/componente; o tema, do FX visual.
5. **Spawn local no tema com regras de convivência** — a abóbora aparece aleatoriamente entre 10 e 25 segundos, fica visível no máximo 30 segundos, existe no máximo uma por vez, e a mecânica vale só para a página atual. É desligada em `/checkout`, `/customer` e `prefers-reduced-motion` (via `halloween-fx/init.js`).
6. **Teste de integração com dispatch real de `customer/section/load`** — o `SectionPool` é um objeto com dependência de área: instanciá-lo fora do escopo frontend não enxerga o mapa do módulo. O teste dispara o controller real de seção e confere o `count`, validando o registro no escopo em que a loja executa.

## Problemas Encontrados e Correções

1. - **Sintoma:** POST em `/halloween/scare` respondia `302` com "Invalid Form Key. Please refresh the page.", e o `data-form-key` saía vazio no HTML.
   - **Causa:** `$block->getFormKey()` em bloco `Template` puro é uma chamada mágica do `DataObject` (devolve `getData('form_key')` = `''`); além disso, na página vinda do FPC o form key cacheado não corresponde à `_form_key` da sessão nova do visitante.
   - **Correção:** removidos o `data-form-key` do template e o envio de `form_key` no JS. O POST passou a depender somente do branch `isXmlHttpRequest()` do `CsrfValidator` (mesma mecânica usada pelos AJAXs nativos do core).
2. - **Sintoma:** o `styles-m.css` servido não continha os estilos novos de scare (`.scare-pumpkin`, `.scare-counter-badge`, `.scare-jumpscare`).
   - **Causa:** o static do tema estava "bakeado" no volume `/var/www/html/pub/static` do container (em docker-magento, um named volume), e o `setup:static-content:deploy` rápido não recompilava quando já havia estado deployado; o `var/view_preprocessed` só é consultado quando o arquivo deployado não existe.
   - **Correção:** removido `pub/static/frontend/Webjump` no container, `cache:flush` e `setup:static-content:deploy -f -t Webjump/halloween -l en_US` (cerca de 7s). Reconfirmado o CSS com os estilos de scare compilados.
3. - **Sintoma:** teste de integração falhava ao instanciar `SectionPoolInterface` para conferir `getSectionNames()` ("Cannot instantiate interface").
   - **Causa:** o objeto é sensível ao escopo de área; fora do frontend o mapa de seções do módulo não é aplicado.
   - **Correção:** o teste passou a disparar `/customer/section/load?sections=scare-counter&force_new_section_timestamp=1` e checar o `count` no JSON de resposta, validando o registro da seção no escopo real (frontend). Suite final: 4 testes, 8 assertions, OK.
4. - **Sintoma:** a abóbora ficava "grudada na tela" (permanecia no mesmo ponto da viewport ao rolar a página), em vez de ficar onde apareceu no documento.
   - **Causa:** `.scare-pumpkin` usava `position: fixed` e o JS posicionava com `left/top` em `%`, o que é relativo à viewport (tela do usuário).
   - **Correção:** `position: absolute` (ancorado no documento) e o spawn passou a calcular coordenadas de documento (`window.scrollX/Y + percentual da viewport`), nascendo na área visível e rolando junto com a página.
5. - **Sintoma:** ao passar o mouse (ou focar) na abóbora, aparecia um quadrado escuro com borda laranja cobrindo a imagem.
   - **Causa:** regras globais compiladas do tema (`button:hover`, `button:focus, button:active`) têm especificidade `(0,1,1)`, maior que `.scare-pumpkin` `(0,1,0)`; o `background` *shorthand* de `#2A2A33`/`#141416` apagava o SVG e a borda `#D84B20` pintava o quadrado.
   - **Correção:** selectores qualificados como `button.scare-pumpkin` com estados explícitos `:hover/:focus/:active` reafirmando `background` (SVG) e `border: 0`, elevando a especificidade para `(0,2,1)`.
6. - **Sintoma:** o contador aparecia pequeno, no rodapé centralizado e com texto "Scared 1 times".
   - **Causa:** `.scare-counter-badge` usava `bottom: 28px; left: 50%` com `font-size: 1rem`, e o texto `$t('Scared %1 times')` não tinha tradução no `i18n/en_US.csv` do tema.
   - **Correção:** badge reposicionado no canto superior direito abaixo do header (`top: 100px; right: 24px`), maior (`font-size: 1.6rem; padding: 14px 26px`), e adicionada a entrada `"Scared %1 times","Você foi assustado %1 vezes"` ao dicionário do tema.
7. - **Sintoma:** o jumpscare era um ícone SVG genérico embutido no `scare.js`, e os arquivos `jumpscare.svg`, `jumpscare2.svg` e `jumpscare3.svg` do tema davam 404.
   - **Causa:** os SVGs estavam na pasta correta (`web/images/` do tema), mas o deploy estático rodou 1 minuto antes de os arquivos existirem (02:26 vs 02:27–02:36), então nunca chegaram em `pub/static`; e a referência `Magento_Theme::images/...` resoveu para `static/.../Magento_Theme/images/`, caminho que o SCD não publica (arquivos próprios do tema ficam em `static/.../images/`).
   - **Correção:** o template passou a emitir `data-scare-images` com as URLs resolvidas via caminho simples (`getViewFileUrl('images/jumpscare.svg')`), o JS removeu o SVG inline e monta um `<img>` alternando as 3 imagens em round-robin (1ª → 2ª → 3ª → 1ª...), e o CSS do flash trocou o seletor `svg` por `img` com sizing que preserva o aspecto das 3 imagens (`height: 60vh; max-width: 80vw`).
8. - **Sintoma:** a abóbora era exibida com 56px (grande para o propósito de jumpscare discreto).
   - **Causa:** `button.scare-pumpkin` com `width/height: 56px`.
   - **Correção:** reduzida para 40×40px em `_halloween-fx.less`.

## Onde o contador aparece

| Superfície | Comportamento |
|---|---|
| Home, PLP, PDP e demais páginas da loja | `#scare-counter` presente, abóbora, jumpscare e badge ativos |
| `/checkout` e `/customer` | `#scare-counter` existe, mas abóbora/badge são desligados por regra do `init.js` |
| HTML da resposta | Apenas o container vazio com `data-scare-url`; o contador so existe no JSON da seção |

Fora do escopo: login/distinção cliente → visitante (o contador funciona para ambos porque usa a sessão `Magento\Customer\Model\Session`), e persistência entre sessões (o valor limpa ao expirar a sessão/cookie).

Riscos conhecidos: o valor depende da sessão PHP (desaparece ao limpar cookies); a isenção de form key vale para qualquer XHR do mesmo domínio (mecânica nativa do core); com `prefers-reduced-motion` nada é exibido (decisão intencional do tema).

## Evidências

### 1. Componente lê a seção e exibe o badge

> Abóbora com `loader-pumpkin.svg` clicável; após o click o badge "Você foi assustado 1 vezes" aparece por ~3 segundos no canto superior direito, sem recarregar a página.

> <img width="212" height="139" alt="image" src="https://github.com/user-attachments/assets/aa7cefba-35d4-4711-b6bb-6ce37bf27b6f" />

### 2. Jumpscare ao clicar na abóbora

> Flash fullscreen `.scare-jumpscare` (~600ms) disparado pela ação de click.

> <img width="1855" height="925" alt="image" src="https://github.com/user-attachments/assets/92b6c117-6532-462a-810c-237e31d09520" />


### 3. Nenhum dado individual no HTML

> `#scare-counter` existe na página e está vazio (sem contagem), confirmado por inspeção do DOM; o valor só chega via `/customer/section/load`.

> <img width="1855" height="925" alt="image" src="https://github.com/user-attachments/assets/536732c0-bb35-4500-a8a2-3bd39f673756" />

### 4. Validação de "O valor é individual por visitante"
> caminho "https://magento.test/" aberto em guia anônima demonstrando que o valor do contador é reiniciado para cada sessão de usuário
> <img width="1846" height="663" alt="Captura de tela de 2026-10-09 07-56-49" src="https://github.com/user-attachments/assets/67f45539-2955-47ac-8b63-e7ee9231a7b9" />

Validações de runtime (via Playwright, sessoes independentes A e B): `navigation type` permanece `navigate` (sem reload), `#scare-counter` com `innerHTML` vazio, nenhum `"count"` no HTML e zero erros de console.



### 5. Demonstração final do desafio em vídeo

> **A abóbora aparece → o usuário clica nela → uma das 3 imagens jumpscare.svg é jogada na tela → O contador de sustos atualiza sem recarregar a página**
> [Gravação de tela de 2026-10-09 10-47-27.webm](https://github.com/user-attachments/assets/78b25f12-d829-4919-b83e-5a559c6b4ccd)



### CRITÉRIO DE ACEITE

- [x] A seção é lida pelo componente e exibida na tela
- [x] O valor é individual por visitante
- [x] Ao executar a ação declarada, o valor se atualiza sem recarregar a página
- [x] Nenhum dado individual foi impresso no HTML pelo PHP
