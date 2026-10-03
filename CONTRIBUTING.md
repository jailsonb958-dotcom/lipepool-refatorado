# Contribuindo

Obrigado por ajudar a manter o LipePool organizado.

## Preparação

1. Use PHP 8.2+, Composer 2, MySQL 8.0.16+ ou MariaDB 10.11 para validações locais.
2. Configure `.env` localmente; nunca envie esse arquivo, credenciais, dump de clientes ou dados de produção.
3. Instale dependências com `composer install`.

## Antes de abrir uma alteração

```bash
composer lint
composer test
composer audit --no-interaction
```

- Prefira controllers enxutos e acesso ao banco via repositórios PDO parametrizados.
- Toda rota administrativa deve validar role no servidor.
- Toda mutação deve usar POST e verificar CSRF.
- Escape valores dinâmicos ao exibi-los em HTML.
- Não altere uma migration já aplicada: acrescente uma nova migration versionada.
- Não adicione credenciais, dados pessoais reais nem informações comerciais presumidas.
- Execute as migrations num banco de teste descartável antes de alterar o schema.

## Pull requests

Descreva a necessidade, as rotas/tabelas afetadas, riscos de migração e como validou. Inclua capturas de tela quando alterar a interface; não inclua informações de clientes.
