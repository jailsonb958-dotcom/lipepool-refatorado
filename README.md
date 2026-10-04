# LipePool — sistema de agendamento refatorado

Nova base do sistema LipePool, reestruturada em PHP orientado a objetos e MySQL. A implementação preserva os fluxos centrais identificáveis no legado — cadastro/login de clientes, solicitação de horário, consulta de agendamentos e administração de clientes, serviços e reservas — sem copiar a chave de e-mail, o código inseguro ou os dados de clientes do pacote antigo.

> Esta é uma refatoração inicial baseada na leitura estática do projeto de 2018. O ZIP original não incluía schema/dump do MySQL; por isso, horários e algumas regras foram inferidos do código, e ainda precisam ser confirmados antes de migrar dados ou colocar em produção.

## Jeito mais simples no Windows (Docker Desktop)

Com Docker, não é preciso instalar PHP, Composer nem MySQL separadamente. O modo abaixo é **somente para desenvolvimento local**.

1. Instale e abra o [Docker Desktop](https://www.docker.com/products/docker-desktop/). Espere ele indicar que está em execução.
2. Baixe este repositório privado com GitHub Desktop: **File → Clone repository → URL**, usando `https://github.com/jailsonb958-dotcom/lipepool-refatorado`.
3. Na pasta baixada, dê dois cliques em `iniciar.bat`. Na primeira vez, ele cria a configuração local, baixa dependências, prepara um MySQL isolado, aplica as migrations e inicia o site. Isso pode levar alguns minutos; deixe a janela aberta.
4. Dê dois cliques em `criar-admin.bat` e responda às perguntas para criar sua conta de administrador.
5. Abra [http://localhost:8080](http://localhost:8080) no navegador.

Para rodar os testes, mantenha o site iniciado e dê dois cliques em `testes.bat`. Para parar os contêineres, use `parar.bat`; os dados locais do banco são preservados. Os dados só são apagados se você executar `docker compose down -v` no terminal dentro da pasta do projeto — **não use esse comando se quiser preservar seus dados locais**.

O atalho cria `.env` automaticamente a partir de `docker.env.example`. Esse arquivo e as senhas de demonstração servem apenas para o ambiente local isolado. Não reutilize essas senhas fora do Docker e não use essa configuração em produção.

## Requisitos do modo manual (sem Docker)

- PHP 8.2+ com `pdo_mysql`, `mbstring`, `openssl` e `session`.
- MySQL 8.0.16+ (InnoDB, `utf8mb4`).
- Composer 2.

## Instalação local

```bash
composer install
cp .env.example .env
```

Edite `.env` com senhas novas para o ambiente local. **Não reutilize a credencial SendGrid que estava no legado**; ela foi removida e deve ser revogada. Nunca comite `.env`.

Crie o banco e separe o usuário de aplicação do usuário de migrations (não use `root` pela aplicação):

```sql
CREATE DATABASE lipepool CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'lipepool_app'@'127.0.0.1' IDENTIFIED BY 'uma-senha-local-forte';
GRANT SELECT, INSERT, UPDATE, DELETE ON lipepool.* TO 'lipepool_app'@'127.0.0.1';
CREATE USER 'lipepool_migrator'@'127.0.0.1' IDENTIFIED BY 'outra-senha-local-forte';
GRANT ALL PRIVILEGES ON lipepool.* TO 'lipepool_migrator'@'127.0.0.1';
```

Configure ambos os usuários em `.env` e aplique as migrations versionadas. Se o banco não estiver no host local, ajuste o componente de host das contas MySQL para a origem real da conexão e restrinja-o ao mínimo necessário:

```bash
php bin/migrate.php
```

O runner registra nome e checksum de cada migration em `schema_migrations`, recusa migrations já alteradas e não habilita multi-statements no PDO. DDL do MySQL não é transacional; em caso de falha parcial, revise o banco antes de tentar novamente.

Crie o primeiro administrador pelo terminal — a rota pública de cadastro nunca aceita papel administrativo:

```bash
php bin/create-admin.php
```

Depois de entrar como administrador, cadastre em **Gerenciar serviços** somente os serviços, descrições e durações confirmados pela empresa. O catálogo inicial fica vazio de propósito para não publicar preços, ofertas ou regras fictícias.

Inicie o servidor local usando o router script e o document root `public/`:

```bash
php -S 127.0.0.1:8080 -t public public/router.php
```

Acesse `http://localhost:8080`. O PHP built-in server é somente para desenvolvimento; use Apache/Nginx com TLS em produção.

## Organização

```text
public/                 front controller, router e assets públicos
src/                    aplicação, controllers, segurança e repositórios
  Auth/                  sessão/autorização
  Controller/            ações HTTP
  Database/              fábrica PDO
  Http/                  Request, Response, Router
  Repository/             acesso a dados preparado
  Security/              CSRF
  Support/                configuração, views, flash e validação
templates/              vistas PHP com escape HTML
database/migrations/     schema MySQL versionado
bin/                     tarefas CLI de migrations/admin
tests/                   testes automatizados
```

## Regras e premissas do schema

- `users` guarda identidade de login e papel (`customer`/`admin`); papel de cliente é definido no servidor.
- `customer_profiles` separa CPF/endereço dos dados de autenticação; os campos sensíveis são opcionais. Reavalie a necessidade de coletá-los e os deveres de privacidade antes de produção.
- `services` é uma entidade própria; agenda referencia `service_id` por chave estrangeira. O catálogo começa vazio para que o responsável cadastre apenas ofertas confirmadas.
- A administração inclui catálogo de serviços com inclusão/edição e desativação lógica; um serviço referenciado por agendamentos não é apagado do histórico.
- `appointments` preserva histórico por estados `requested`, `completed`, `cancelled`, com FKs e índices. Um índice único com coluna gerada impede, no banco, duas solicitações ativas para a mesma data/hora, inclusive sob concorrência.
- Os horários `08:00`, `10:00`, `13:00`, `15:00`, `17:00` e `19:00` foram lidos do formulário antigo. Não foi possível confirmar expediente, duração, intervalos, fuso de operação nem se o horário deve ser global ou por técnico. A regra atual mantém um único atendimento ativo por horário global.
- A aplicação não importa clientes, hashes MD5, agendamentos nem configurações do banco legado. Faça qualquer migração real somente com backup, mapeamento validado e redefinição/migração segura de credenciais.

## Segurança incluída

- PDO com prepared statements e erros não enviados ao cliente.
- Senhas novas com `password_hash()`/`password_verify()`; nunca MD5. Cadastro aceita no mínimo 12 caracteres e até 72 bytes UTF-8 para evitar truncamento do bcrypt padrão.
- Sessões `HttpOnly`, `SameSite=Strict`, regeneração após login, logout completo e cookie `Secure` quando HTTPS.
- Controle de acesso aplicado no servidor em cada rota administrativa/privada.
- Tokens CSRF em formulários POST e métodos POST para mutações.
- Bloqueio de login após 5 falhas em 15 minutos por combinação de e-mail/IP; a tabela armazena somente um hash da combinação, não os valores brutos, e descarta registros inativos antigos de forma periódica.
- Validação no servidor de e-mail, tamanho, data, horário e conteúdo.
- Escape com `htmlspecialchars()` em templates.
- Papel administrativo criado somente pelo script CLI.
- Segredos somente por ambiente; nenhuma credencial externa é incluída.

## Contato e recuperação de senha

A chave SendGrid embutida no legado foi removida. Envio de e-mail e recuperação de senha não estão ativados até que o responsável escolha/configure um provedor e um processo de envio verificável. Contatos institucionais também permanecem em branco no `.env.example` para não publicar telefone/e-mail potencialmente desatualizados.

## Validação

```bash
composer lint
composer test
composer audit
```

Os testes automatizados usam SQLite em memória para regras e consultas portáveis. As migrations foram verificadas localmente em MariaDB 10.11; valide também na versão exata do MySQL do ambiente de produção.

## Contribuir e reportar vulnerabilidades

Consulte [CONTRIBUTING.md](CONTRIBUTING.md) para executar as verificações e [SECURITY.md](SECURITY.md) para a política de divulgação. Antes de publicar o repositório, habilite o canal privado de reporte de vulnerabilidades no GitHub.

## Antes de usar com clientes

1. Revogar a credencial SendGrid antiga e confirmar que não foi reutilizada.
2. Confirmar fuso, expediente, duração, disponibilidade, serviço, cancelamento e status com o negócio.
3. Revisar retenção e tratamento de CPF/endereço; manter somente dados necessários.
4. Configurar HTTPS, backups, logs protegidos, usuário MySQL de privilégio mínimo, monitoramento e provedor de e-mail.
5. Revisar licença e direitos das imagens/identidade antes de distribuir publicamente.

## Licença

A licença MIT do pacote original foi mantida neste refactor como ponto de partida; titular e data devem ser confirmados antes de publicar.
