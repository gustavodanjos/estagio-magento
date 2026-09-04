# Instalação local do Magento (docker-magento)

## Objetivo

Configurar o ambiente local de Magento Open Source usando o projeto [docker-magento](https://github.com/markshust/docker-magento) (Docker + Composer), cumprindo os requisitos do desafio 12.1.

## Ambiente

- SO: Ubuntu (nativo, não WSL2)
- Docker + Docker Compose
- Magento Open Source 2.4.8-p1
- Domínio local: https://magento.test

---

## Passo a passo seguido

1. Criei a pasta do projeto e rodei a instalação automática:

```bash
   mkdir -p ~/Sites/magento && cd ~/Sites/magento
   curl -s https://raw.githubusercontent.com/markshust/docker-magento/master/lib/onelinesetup \
     | bash -s -- magento.test community 2.4.8-p1
```

2. Criei uma conta em https://commercemarketplace.adobe.com e gerei um par de chaves de acesso (public/private key) em "My Profile > Access Keys".
3. Quando solicitado, informei as chaves de acesso da Adobe Commerce Marketplace
   (Username = chave pública, Password = chave privada).
4. Aguardei o script subir os containers, instalar o Magento via Composer e
   rodar o setup automaticamente.
5. Criei um usuário admin próprio:

- **Exemplo ↓**

```bash
   bin/magento admin:user:create \
     --admin-user='gusta_dev' \
     --admin-password='SuaSenh@123' \
     --admin-email='seu_email@exemplo.com' \
     --admin-firstname='Gustavo' \
     --admin-lastname='Fernandes'
```

6. Confirmei o modo de deploy:

```bash
   bin/magento deploy:mode:show
   # Current application mode: developer
```

> <img width="100%" src="https://github.com/user-attachments/assets/3824eb3a-6195-452b-b4ac-70e682971670" />

7. Acessei https://magento.test (site) e https://magento.test/admin (painel).

- **https://magento.test**

> <img width="100%" src="https://github.com/user-attachments/assets/d133b704-1877-4c69-910a-134b08abd71d" />

- **https://magento.test/admin**

> <img width="100%" src="https://github.com/user-attachments/assets/ebc3e571-c24b-40c8-8ac6-f54c6648e7e4" />

> [!NOTE]
> As instalações do docker e do docker-compose já estavam previamente instaladas, antes de rodar o script de instalação do Magento. <img width="100%" src="https://github.com/user-attachments/assets/e3c93fd0-60ca-4e3e-a3b6-d7fd95172dc6" />


---

## Problemas enfrentados e soluções

### 1. Falha no pull das imagens Docker (erro de rede)

Na primeira tentativa, adicionei user e password genéricos, além disso, o download das imagens Docker falhou no meio do processo (`failed to authorize: ... EOF`), interrompendo o script antes de ele baixar o código-fonte do Magento. Resultado: `bin/magento` não existia.
**Solução:** limpar a instalação incompleta (`bin/removeall` + apagar a
pasta do projeto) e rodar o setup novamente com a conexão estável.

### 2. Autenticação do Composer com repo.magento.com

O Composer precisa de chaves de acesso (public/private key) de uma conta Adobe Commerce Marketplace para baixar o Magento Open Source, usar usuário/senha genéricos resulta em erro 401 (Unauthorized).
**Solução:** criar conta em https://commercemarketplace.adobe.com, gerar um par de chaves em "My Profile > Access Keys" e usá-las quando o script pedir usuário/senha do Composer.

### 3. Credenciais antigas presas mesmo após limpar o projeto

Mesmo apagando a pasta do projeto e recriando tudo, o script continuava usando credenciais inválidas que foram criadas na primeira tentativa, pois o Composer guarda essas credenciais em `~/.composer/auth.json`, **no host**, fora da pasta do projeto, não é apagado por `bin/removeall`.
**Solução:** apagar `~/.composer/auth.json` manualmente e rodar o setup de novo, e adicionar as credenciais corretas quando solicitado (public/private key).

### 4. Bloqueio por autenticação de dois fatores (2FA)

Ao logar no admin pela primeira vez, o Magento exige configurar 2FA por e-mail, mas o e-mail não chegava (sem SMTP configurado no ambiente local).
**Solução:** desabilitar o módulo de 2FA (aceitável em ambiente de
desenvolvimento local, nunca em produção):

```bash
bin/magento module:disable Magento_TwoFactorAuth Magento_AdminAdobeImsTwoFactorAuth
bin/magento cache:flush
```

---

## Comandos úteis do dia a dia aprendidos

```bash
bin/start   # sobe os containers (mantém os dados)
bin/stop    # para os containers sem apagar nada
bin/magento cache:flush # limpa o cache do Magento
bin/magento deploy:mode:show # mostra o modo de deploy atual
bin/magento module:status # mostra o status dos módulos 
```

`bin/removeall` só deve ser usado para destruir o ambiente por completo.

## CRITÉRIO DE ACEITE

- [x] Site abrindo em https://magento.test
- [x] Admin acessível, com usuário próprio criado por bin/magento admin:user:create
- [x] bin/magento deploy:mode:show retorna developer
- [x] README com o passo a passo que você seguiu e os problemas que enfrentou
- [x] Print do site e do admin abertos