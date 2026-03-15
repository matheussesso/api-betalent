# Estrutura e funcionamento do projeto

## Visão geral

Este projeto implementa uma API RESTful de pagamentos multi-gateway com fallback entre provedores, autenticação por token e autorização por perfis.

Stack principal:

- Laravel 12
- PHP 8.4 (FrankenPHP)
- MySQL 8
- Docker Compose
- Mocks externos de gateway (`matheusprotzen/gateways-mock`)

## Arquitetura por camadas

### Entrada HTTP

- Rotas web: `routes/web.php` (home com Swagger)
- Rotas de API: `routes/api.php`
- Controllers: `app/Http/Controllers/Api`

Controllers fazem:

- validação de request
- delegação de regra de negócio para serviços
- padronização de respostas JSON

### Domínio e persistência

Modelos em `app/Models`:

- `User`
- `Gateway`
- `Client`
- `Product`
- `Transaction`
- `TransactionProduct`
- `ApiToken`

Relacionamentos principais:

- cliente possui várias transações
- transação pertence a cliente e gateway
- transação possui vários produtos (pivot com quantidade e valor unitário)

### Regras de negócio (services)

Serviços de pagamento em `app/Services/Payments`:

- `PaymentService`: orquestra compra e reembolso
- `PaymentGatewayRegistry`: resolve gateway por driver
- `GatewayOneClient` e `GatewayTwoClient`: integração com APIs externas

Fluxo de compra (`PaymentService::purchase`):

1. Valida e normaliza itens de produto
2. Calcula valor total no backend
3. Busca gateways ativos por ordem de prioridade
4. Tenta cobrança no gateway prioritário
5. Em falha, tenta o próximo gateway
6. Ao primeiro sucesso, persiste transação e itens

Fluxo de reembolso (`PaymentService::refund`):

1. Verifica se a transação já foi reembolsada (`refunded`, `charged_back` ou `charge_back`)
2. Chama o mesmo gateway da transação original
3. Atualiza status local da transação

### Segurança

Autenticação:

- endpoint `POST /api/login` gera token bearer
- middleware `auth.api` valida token em rotas privadas

Autorização por roles:

- middleware `role` aplicado por rota
- perfis: `ADMIN`, `MANAGER`, `FINANCE`, `USER`

## Integração com gateways

Gateway 1:

- autentica via `/login` e usa Bearer token
- endpoints de cobrança/reembolso em inglês

Gateway 2:

- autentica via headers fixos
- endpoints de cobrança/reembolso em português

A o uso do `driver` permite adicionar novos gateways de maneira facilitada, seguindo um padrão já definido.

## Estrutura de pastas relevante

- `app/Http/Controllers/Api`: endpoints REST
- `app/Services/Payments`: orquestração e integrações
- `app/Models`: entidades e relacionamentos
- `database/migrations`: schema
- `database/seeders`: dados iniciais (usuários e gateways)
- `tests/Feature`: cenários principais da API
- `docs`: documentação e collections
- `public/api-betalent.json`: especificação OpenAPI usada pelo Swagger da home

## Execução local (Docker)

Subir ambiente:

```bash
docker compose up -d --build
```

Serviços expostos:

- API: `http://localhost:8000`
- MySQL: `localhost:3306`
- Mock Gateway 1: `http://localhost:3001`
- Mock Gateway 2: `http://localhost:3002`

## Testes

Rodar os testes:

```bash
docker compose exec app php artisan test --testsuite=Feature
```

Organização atual dos testes:

- `tests/Feature/Api/AuthApiTest.php`
- `tests/Feature/Api/PurchaseApiTest.php`
- `tests/Feature/Api/RefundApiTest.php`
- `tests/Feature/Api/GatewayAuthorizationApiTest.php`
- `tests/Feature/Api/PrivateResourcesApiTest.php`

Cobertura dos testes inclui:

- login e emissão de token
- falha de login com credenciais inválidas
- acesso a rotas privadas sem autenticação
- fallback entre gateways
- validação de CVV por gateway
- validação de payload de compra
- compra sem gateway ativo
- reembolso com validação de perfil
- bloqueio de reembolso duplicado
- autorização por role para gateways, produtos, usuários, clientes e transações
