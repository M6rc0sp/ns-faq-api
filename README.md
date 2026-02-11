# FAQ API - Nuvemshop

Gerenciador avançado de FAQs (Perguntas Frequentes) para a Nuvemshop, com suporte a múltiplos tipos de páginas: homepage, produtos e categorias.

## Características

✨ **Multi-página**: Vincule um FAQ a homepage, produtos ou categorias  
✨ **Flexível**: Um FAQ pode estar em múltiplos locais diferentes  
✨ **Isolamento por loja**: Cada loja acessa apenas seus próprios FAQs  
✨ **REST API**: Endpoints bem estruturados e documentados  
✨ **Autenticação JWT**: Protegido com tokens Bearer da Nexo  
✨ **CORS habilitado**: Pronto para consumo em aplicações frontend  

## Requisitos

- PHP 8.1+
- Laravel Lumen 11.x
- MySQL/PostgreSQL
- Composer

## Instalação

```bash
# Instalar dependências
composer install

# Copiar arquivo de ambiente
cp .env.example .env

# Gerar chave da aplicação
php artisan key:generate

# Executar migrations
php artisan migrate

# Iniciar servidor (desenvolvimento)
php artisan serve
```

## Configuração

### Variáveis de Ambiente (.env)

```env
APP_NAME="FAQ API"
APP_DEBUG=true
APP_ENV=local
APP_KEY=base64:...

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=faq_api
DB_USERNAME=root
DB_PASSWORD=

# Nuvemshop OAuth
NUVEMSHOP_CLIENT_ID=seu_client_id
NUVEMSHOP_CLIENT_SECRET=seu_client_secret
NUVEMSHOP_USER_AGENT="FAQ App (seu_email@example.com)"
```

## Arquitetura

```
app/
├── Models/                      # Modelos Eloquent
│   ├── Faq.php                 # FAQ principal
│   ├── FaqQuestion.php         # Perguntas
│   ├── FaqBinding.php          # Relacionamentos com páginas
│   └── Store.php               # Dados da loja (autenticação)
├── Services/
│   ├── FaqService.php          # Lógica de negócio
│   └── NuvemshopService.php    # Integração com Nuvemshop
├── Http/
│   ├── Controllers/
│   │   ├── FaqController.php   # Endpoints FAQs
│   │   └── NuvemshopController.php
│   └── Middleware/
│       ├── NexoApiAuth.php     # Autenticação JWT
│       └── CorsMiddleware.php
└── Exceptions/
    └── Handler.php             # Tratamento de erros
```

## Estrutura de Dados

### Tabelas

#### `stores`
Armazena informações de autenticação de cada loja Nuvemshop.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | int | PK |
| store_id | string | ID único da loja Nuvemshop |
| store_name | string | Nome da loja |
| access_token | string | Token de acesso OAuth |
| refresh_token | string | Token para renovação |
| token_expires_at | datetime | Quando o token expira |
| store_data | json | Dados adicionais da loja |

#### `faqs`
FAQs principais vinculadas a uma loja.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | int | PK |
| store_id | string | FK para stores |
| title | string | Título do FAQ |
| active | boolean | Se está ativo |
| created_at | datetime | Data criação |
| updated_at | datetime | Data atualização |

#### `faq_questions`
Perguntas e respostas de um FAQ.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | int | PK |
| faq_id | int | FK para faqs |
| question | longtext | Pergunta |
| answer | longtext | Resposta |
| order | int | Ordem de exibição |
| created_at | datetime | Data criação |
| updated_at | datetime | Data atualização |

#### `faq_bindings`
Relacionamentos entre FAQ e páginas (produto/categoria/homepage).

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | int | PK |
| faq_id | int | FK para faqs |
| bindable_type | enum | 'homepage', 'product', 'category' |
| bindable_id | string | ID do produto (pode ser null) |
| category_handle | string | Handle da categoria (pode ser null) |
| created_at | datetime | Data criação |
| updated_at | datetime | Data atualização |

## Endpoints

### Admin (Autenticado)

**FAQs**
- `GET /api/faqs` - Listar todos
- `POST /api/faqs` - Criar novo
- `GET /api/faqs/{id}` - Obter específico
- `PUT /api/faqs/{id}` - Atualizar
- `DELETE /api/faqs/{id}` - Deletar

**Perguntas**
- `POST /api/faqs/{faqId}/questions` - Adicionar pergunta
- `PUT /api/faqs/questions/{questionId}` - Atualizar pergunta
- `DELETE /api/faqs/questions/{questionId}` - Deletar pergunta

**Bindings**
- `POST /api/faqs/{faqId}/bindings` - Vincular FAQ a página
- `DELETE /api/faqs/bindings/{bindingId}` - Remover vínculo

### Público (Não autenticado)

- `GET /public/faqs/{storeId}/product/{productId}` - FAQ de produto
- `GET /public/faqs/{storeId}/category/{categoryHandle}` - FAQ de categoria
- `GET /public/faqs/{storeId}/homepage` - FAQ de homepage

### Instalação

- `GET /api/ns/install?code={code}` - Callback OAuth Nuvemshop

Veja [ENDPOINTS.md](ENDPOINTS.md) para documentação completa.

## Autenticação

A API usa tokens JWT Bearer. Para acessar endpoints admin, inclua o header:

```http
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

O middleware `NexoApiAuth` extrai o `store_id` do token e valida se a loja existe no banco de dados.

## Exemplo de Uso

### 1. Criar um FAQ

```bash
curl -X POST http://localhost:8000/api/faqs \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Dúvidas sobre entrega",
    "active": true
  }'
```

### 2. Adicionar perguntas

```bash
curl -X POST http://localhost:8000/api/faqs/1/questions \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "question": "Quanto tempo leva para entregar?",
    "answer": "Entregamos em 5 dias úteis.",
    "order": 0
  }'
```

### 3. Vincular a um produto

```bash
curl -X POST http://localhost:8000/api/faqs/1/bindings \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "bindable_type": "product",
    "bindable_id": "12345"
  }'
```

### 4. Consumir (frontend)

```bash
curl http://localhost:8000/public/faqs/123456/product/12345
```

```json
{
  "id": 1,
  "title": "Dúvidas sobre entrega",
  "active": true,
  "questions": [
    {
      "question": "Quanto tempo leva para entregar?",
      "answer": "Entregamos em 5 dias úteis."
    }
  ]
}
```

## Development

### Executar Testes

```bash
php artisan test
```

### Comandos Úteis

```bash
# Fresh migrations (limpar e recriar)
php artisan migrate:fresh

# Ver logs
tail -f storage/logs/laravel.log

# Tinker (REPL)
php artisan tinker
```

## Estrutura de Resposta

Todas as respostas seguem o padrão:

```json
{
  "success": true|false,
  "data": {...} | null,
  "message": "Descrição da resposta",
  "errors": {...} // Apenas em validações
}
```

## Tratamento de Erros

| Status | Erro | Descrição |
|--------|------|-----------|
| 401 | unauthorized | Token não fornecido |
| 401 | invalid_token | Token inválido |
| 404 | store_not_found | Loja não encontrada |
| 404 | not_found | Recurso não encontrado |
| 422 | validation_failed | Dados inválidos |
| 500 | internal_error | Erro interno do servidor |

## Deploy

### Production

1. Configure as variáveis de ambiente
2. Execute migrations: `php artisan migrate --force`
3. Configure CORS apropriadamente
4. Use HTTPS
5. Configure logs em centralized system (Sentry, etc)

## Contribuindo

Pull requests são bem-vindas! Para mudanças maiores, abra uma issue primeiro.

## Licença

Proprietary - Todos os direitos reservados.

## Suporte

Para dúvidas ou issues, contate: suporte@example.com
