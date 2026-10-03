# Política de segurança

## Versões

Este projeto ainda é uma refatoração inicial. Até haver uma versão implantada e formalmente suportada, não há SLA de correções nem suporte de produção.

## Reportar uma vulnerabilidade

Não publique detalhes exploráveis, tokens, dumps ou dados pessoais em issues ou pull requests públicos. Antes de publicar o repositório, o mantenedor deve habilitar o recurso de **Private vulnerability reporting** do GitHub ou definir um canal privado verificável no perfil do projeto. Se nenhum canal privado estiver disponível, não abra um relato público com detalhes técnicos; peça ao mantenedor um canal privado primeiro.

## Dados e credenciais

- Não teste este código em produção ou com dados de clientes sem autorização explícita.
- Não reutilize chaves que estavam no projeto legado; revogue-as no provedor correspondente.
- `.env`, logs, dumps SQL, uploads e dados de clientes não devem ser versionados.
- Use banco descartável para reprodução de bugs e testes automatizados.
