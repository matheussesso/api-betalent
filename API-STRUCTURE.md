# Estrutura e endpoints da API

## Base URL

- `http://localhost:8000/api`

## Autenticação

A autenticação usa Bearer token retornado por `POST /login`.

Header para rotas privadas:

```http
Authorization: Bearer <access_token>
Accept: application/json
Content-Type: application/json
```

## Perfis (roles)

- `ADMIN`: acesso total
- `MANAGER`: gestão de usuários e produtos
- `FINANCE`: gestão de produtos e reembolsos
- `USER`: operações sem privilégios administrativos

## Endpoints públicos

### POST /login
Autentica usuário e retorna token.

Request:

```json
{
  "email": "admin@betalent.local",
  "password": "password"
}
```

### POST /purchase
Cria uma compra com produtos e quantidades.

Request:

```json
{
  "client_name": "Cliente Teste",
  "client_email": "cliente@teste.com",
  "card_number": "5569000000006063",
  "cvv": "010",
  "products": [
    { "product_id": 1, "quantity": 2 }
  ]
}
```

Regras importantes:

- valor total é calculado no backend
- fallback por prioridade de gateway
- validação de CVV por gateway:
  - `gateway_1`: rejeita `100` e `200`
  - `gateway_2`: rejeita `200` e `300`

## Endpoints privados

### Gateways (`ADMIN`)

- `GET /gateways`
- `PATCH /gateways/{gateway}/active`
- `PATCH /gateways/{gateway}/priority`

Payloads:

```json
{ "is_active": true }
```

```json
{ "priority": 1 }
```

### Usuários (`ADMIN`, `MANAGER`)

- `GET /users`
- `POST /users`
- `GET /users/{user}`
- `PUT /users/{user}`
- `PATCH /users/{user}`
- `DELETE /users/{user}`

Payload de criação:

```json
{
  "name": "Novo Usuário",
  "email": "novo@teste.com",
  "password": "password",
  "role": "USER"
}
```

### Produtos (`ADMIN`, `MANAGER`, `FINANCE`)

- `GET /products`
- `POST /products`
- `GET /products/{product}`
- `PUT /products/{product}`
- `PATCH /products/{product}`
- `DELETE /products/{product}`

Payload:

```json
{
  "name": "Produto A",
  "amount": 1000
}
```

### Clientes (`ADMIN`, `MANAGER`)

- `GET /clients`
- `GET /clients/{client}`

### Transações

- `GET /transactions` (`ADMIN`, `MANAGER`, `FINANCE`)
- `GET /transactions/{transaction}` (`ADMIN`, `MANAGER`, `FINANCE`)
- `POST /transactions/{transaction}/refund` (`ADMIN`, `FINANCE`)

Reembolso:

- usa o mesmo gateway da transação original
- não permite reembolso duplicado (status já reembolsado)

## Códigos de resposta comuns

- `200` OK
- `201` Created
- `204` No Content
- `401` Não autenticado / credenciais inválidas
- `403` Sem permissão para o perfil
- `404` Recurso não encontrado
- `422` Erro de validação ou regra de negócio

## Cobertura de testes da API

Os testes estão separados por:

- `AuthApiTest`: autenticação e acesso sem token
- `PurchaseApiTest`: compra, fallback e validações
- `RefundApiTest`: reembolso e bloqueio de duplicidade
- `GatewayAuthorizationApiTest`: permissões de gateways
- `PrivateResourcesApiTest`: permissões de produtos, usuários, clientes e transações

Comando para rodar os testes:

```bash
docker compose exec app php artisan test --testsuite=Feature
```

## Documentação interativa

A documentação Swagger está na home do projeto:

- `http://localhost:8000/`

Especificação OpenAPI em:

- `http://localhost:8000/api-betalent.json`
