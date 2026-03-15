# API Multi-Gateway de Pagamentos (Laravel)

Implementação do desafio técnico para uma API RESTful com:

- Laravel 12
- Servidor FrankenPHP
- Banco de dados MySQL
- Integração com 2 gateways de pagamento externos, com fallback por prioridade
- Autenticação por token Bearer
- Controle de acesso por roles (`ADMIN`, `MANAGER`, `FINANCE`, `USER`)
- Testes de feature (TDD-oriented)

## Requisitos

- Docker
- Docker Compose

## Subindo com Docker Compose

```bash
git clone git@github.com:matheussesso/api-betalent.git
cd api-betalent
cp .env.example .env
docker compose up -d --build
```

O container da API executa automaticamente:

- `php artisan key:generate --force`
- `php artisan migrate --force`
- `php artisan db:seed --force`
- `frankenphp php-server --listen 0.0.0.0:8000 --root /var/www/html/public`

Serviços:

- API: `http://localhost:8000`
- MySQL: `localhost:3306`
- Mock Gateway 1: `http://localhost:3001`
- Mock Gateway 2: `http://localhost:3002`

## Usuários padrão (seed)

- ADMIN: `admin@betalent.local` / `password`
- MANAGER: `manager@betalent.local` / `password`
- FINANCE: `finance@betalent.local` / `password`

## Regras de negócio implementadas

- Compra recebe produtos + quantidades e calcula valor no backend (centavos)
- Tenta cobrança em gateways ativos por ordem de prioridade
- Em falha no gateway atual, tenta o próximo
- Se algum gateway aprovar, retorna sucesso sem erro
- Validação de CVV por gateway:
	- `gateway_1`: rejeita `100` e `200`
	- `gateway_2`: rejeita `200` e `300`
- Reembolso usa o mesmo gateway da transação original
- Cadastro de gateways modular por `driver` para facilitar expansão futura

## Rotas

Base URL: `http://localhost:8000/api`

### Públicas

- `POST /login`
- `POST /purchase`

### Privadas (`Authorization: Bearer <token>`)

- `GET /gateways` (`ADMIN`)
- `PATCH /gateways/{gateway}/active` (`ADMIN`)
- `PATCH /gateways/{gateway}/priority` (`ADMIN`)
- `GET|POST|PUT|PATCH|DELETE /users` (`ADMIN`, `MANAGER`)
- `GET|POST|PUT|PATCH|DELETE /products` (`ADMIN`, `MANAGER`, `FINANCE`)
- `GET /clients` (`ADMIN`, `MANAGER`)
- `GET /clients/{client}` (`ADMIN`, `MANAGER`)
- `GET /transactions` (`ADMIN`, `MANAGER`, `FINANCE`)
- `GET /transactions/{transaction}` (`ADMIN`, `MANAGER`, `FINANCE`)
- `POST /transactions/{transaction}/refund` (`ADMIN`, `FINANCE`)

## Estrutura de dados principal

- `users`: email, password, role
- `gateways`: name, driver, is_active, priority
- `clients`: name, email
- `products`: name, amount
- `transactions`: client_id, gateway_id, external_id, status, amount, card_last_numbers
- `transaction_products`: transaction_id, product_id, quantity, unit_amount
- `api_tokens`: autenticação Bearer do sistema

## Testes

Rodar os testes:

```bash
docker compose exec app php artisan test --testsuite=Feature
```

## Documentações adicionais

- Estrutura e funcionamento do projeto: [docs/project-structure.md](docs/project-structure.md)
- Estrutura e endpoints da API: [docs/api-structure.md](docs/api-structure.md)
- Collection para Postman/Insomnia: [docs/api-betalent.collection.json](docs/api-betalent.collection.json)
