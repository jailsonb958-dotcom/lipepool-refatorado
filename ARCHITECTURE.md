# Arquitetura do LipePool

Este documento descreve a estrutura atual da aplicação, o fluxo principal de execução e os principais pontos de manutenção.

## Visão geral

O LipePool é um sistema de agendamento de serviços em PHP com MySQL. A aplicação foi organizada para separar:

- apresentação HTTP
- regras de negócio
- acesso a dados
- autenticação/autorização
- segurança e validação
- migrations do banco

A proposta da refatoração foi reduzir acoplamento, manter fluxos de negócio legíveis e tornar a aplicação mais segura e fácil de evoluir.

## Estrutura de pastas

```text
/
├── public/
│   ├── index.php
│   ├── router.php
│   └── assets/
├── src/
│   ├── Auth/
│   ├── Controller/
│   ├── Database/
│   ├── Http/
│   ├── Repository/
│   ├── Security/
│   └── Support/
├── templates/
├── database/
│   └── migrations/
├── bin/
├── tests/
├── .env.example
├── composer.json
├── phpunit.xml
├── README.md
├── CONTRIBUTING.md
├── SECURITY.md
└── .github/
    └── workflows/
```

## Camadas da aplicação

### 1. Camada HTTP

Responsável por receber requisições web e rotear para o controller adequado.

Pontos principais:
- `public/router.php` -> ponto de entrada HTTP
- `src/Http/` -> abstrações de request, response e roteamento
- `src/Controller/` -> ações executadas por rota

Fluxo típico:

```text
HTTP Request
   ↓
router.php
   ↓
Controller
   ↓
Repository / Service / use case
   ↓
Template ou resposta HTTP
```

### 2. Camada de autenticação e autorização

A autenticação é tratada em um módulo específico para separar lógica de sessão e autorização do restante do código.

Pontos principais:
- `src/Auth/`
- controle de sessão
- login/logout
- verificação do papel do usuário (`customer` / `admin`)

Regras esperadas:
- nenhuma rota administrativa deve aceitar usuário não autorizado
- sessão deve ser regenerada após login
- cookies com políticas de segurança

### 3. Camada de acesso a dados

A camada `Repository` centraliza a persistência e leitura de dados. Isso evita espalhar consultas SQL diretamente nos controllers.

Pontos principais:
- `src/Repository/`
- queries preparadas com PDO
- leitura de dados por entidade ou caso de uso

Boa prática atual:
- `Controller` não deve montar SQL
- `Repository` deve encapsular isso

### 4. Camada de banco de dados

A aplicação usa PDO e migrations versionadas.

Pontos principais:
- `src/Database/` -> fábrica e conexão com o banco
- `database/migrations/` -> schema versionado
- `bin/migrate.php` -> aplica schema

Objetivos:
- manter o banco rastreável
- permitir ambientes locais e de produção com controle versionado
- evitar alterações manuais e inconsistentes no schema

### 5. Camada de segurança

A pasta `src/Security/` guarda comportamentos críticos do sistema, como:
- CSRF
- validação de entrada
- controle de roteamento público vs privado
- proteção de mutações HTTP

Regras do projeto mencionadas no README:
- only POST para mutações
- CSRF em formulários
- autenticação obrigatória em áreas sensíveis
- escape de saída em templates
- nenhuma senha em texto puro

### 6. Camada de suporte

A pasta `src/Support/` geralmente guarda utilitários e estruturas auxiliares, como:
- configuração
- flash messages
- helper functions
- validações de domínio
- suporte de views

A ideia é que essa camada não contenha regra de negócio principal, mas suporte a execução da aplicação de forma consistente.

## Fluxo principal: agendamento

O fluxo central do sistema é o agendamento de serviços.

### Fluxo sugerido

```text
Cliente acessa página de agendamento
   ↓
Validar dados do formulário
   ↓
Verificar disponibilidade do horário
   ↓
Criar registro em appointments
   ↓
Persistir em banco
   ↓
Retornar confirmação para o cliente
```

### Entidades principais

- `users` -> identidade do usuário e papel
- `customer_profiles` -> dados do cliente, como CPF e endereço
- `services` -> catálogo de serviços
- `appointments` -> histórico de agendamentos

### Estados de agendamento

O README indica que o sistema trabalha com estados como:
- `requested`
- `completed`
- `cancelled`

Esse histórico é importante porque o sistema precisa manter integridade e rastreabilidade, mesmo com alterações de status.

## Regras de negócio importantes

### Separação de papéis

A aplicação assume pelo menos dois papéis:
- `customer`
- `admin`

O papel administrativo não deve ser criado por formulário público. Ele deve ser gerado por script CLI ou outra ação interna autorizada.

### Dados sensíveis

CPF, endereço e outras informações sensíveis devem ser tratados com cuidado:
- armazenar apenas o necessário
- evitar exposição
- manter regras de privacidade e retenção
- não expor em logs ou erros do sistema

### Segurança no banco

A organização do projeto enfatiza:
- uso de prepared statements
- usuário MySQL com privilégio mínimo
- migrações em vez de SQL manual ad hoc
- integridade referencial via chaves estrangeiras
- índices para consultas relevantes

## Fluxo de desenvolvimento

### Ambiente local

```bash
composer install
cp .env.example .env
php bin/migrate.php
php bin/create-admin.php
php -S 127.0.0.1:8080 -t public public/router.php
```

### Validação

```bash
composer lint
composer test
```

## Pontos fortes da arquitetura atual

- separação clara entre HTTP, regras de negócio e persistência
- uso de migrations como padrão
- foco em segurança por design
- organização por pastas e responsabilidades
- leitura simples da stack do projeto

## Pontos de melhoria arquiteturais

### 1. Camada de domínio mais explícita

Hoje a aplicação parece manter a lógica em controllers/repositories. O próximo passo natural é criar uma camada de domínio ou service layer para centralizar regras de negócio complexas.

### 2. Mais testes de integração

O projeto já tem testes, mas ainda pode ganhar:
- testes de autenticação
- testes de autorização
- testes de agendamento
- testes de manutenção de status

### 3. Logs estruturados

Para facilitar monitoramento e auditoria, o sistema pode ganhar:
- logger centralizado
- logs de login/logout
- logs de ações administrativas
- logs de agendamento e cancelamento

### 4. Documentação de endpoints

Se a aplicação evoluir para APIs ou integrações, vale criar:
- documentação de rotas
- contratos de entrada/saída
- OpenAPI/Swagger

### 5. Camada de validação centralizada

Validações em vários lugares podem crescer de forma inconsistente. Criar um validador reutilizável simplifica manutenção.

## Recomendação de evolução

A próxima evolução ideal para o projeto é:

1. introduzir `Service`/`UseCase` para regras de negócio
2. centralizar validação em classes dedicadas
3. adicionar logs estruturados e auditoria
4. criar testes de integração mais completos
5. automatizar CI/CD com GitHub Actions

## Resumo

A arquitetura atual está bem organizada para um projeto refatorado em PHP. O código está dividido por responsabilidade e já tem preocupação forte com segurança e versionamento de banco. O principal próximo passo é evoluir da camada de execução para uma estrutura mais formal de domínio e testes.

A aplicação já está em uma boa base para crescer de forma sustentável.
