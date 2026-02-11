# FAQ API - Endpoints Documentation

## Visão Geral

A FAQ API permite gerenciar perguntas frequentes (FAQs) que podem ser vinculadas a múltiplos tipos de páginas:
- **Homepage**: FAQ da página inicial da loja
- **Product**: FAQ específico de um produto
- **Category**: FAQ específico de uma categoria

## Estrutura de Dados

### FAQ
```json
{
  "id": 1,
  "store_id": "123456",
  "title": "Perguntas frequentes do produto XYZ",
  "active": true,
  "created_at": "2025-02-09T10:00:00Z",
  "updated_at": "2025-02-09T10:00:00Z"
}
```

### FAQ Question
```json
{
  "id": 1,
  "faq_id": 1,
  "question": "Qual é a garantia deste produto?",
  "answer": "Este produto tem uma garantia de 2 anos contra defeitos de fabricação.",
  "order": 0,
  "created_at": "2025-02-09T10:00:00Z",
  "updated_at": "2025-02-09T10:00:00Z"
}
```

### FAQ Binding
```json
{
  "id": 1,
  "faq_id": 1,
  "bindable_type": "product",
  "bindable_id": "12345",
  "category_handle": null,
  "created_at": "2025-02-09T10:00:00Z",
  "updated_at": "2025-02-09T10:00:00Z"
}
```

---

## Rotas Admin (Requer Autenticação JWT via header `Authorization: Bearer <token>`)

### FAQs

#### GET `/api/faqs`
Listar todos os FAQs da loja autenticada.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "store_id": "123456",
      "title": "FAQ Exemplo",
      "active": true,
      "questions": [
        {
          "id": 1,
          "faq_id": 1,
          "question": "Pergunta?",
          "answer": "Resposta.",
          "order": 0
        }
      ],
      "bindings": [
        {
          "id": 1,
          "faq_id": 1,
          "bindable_type": "product",
          "bindable_id": "12345",
          "category_handle": null
        }
      ]
    }
  ],
  "message": "FAQs obtidos com sucesso"
}
```

---

#### GET `/api/faqs/{id}`
Obter um FAQ específico.

**Parameters:**
- `id` (integer, required): ID do FAQ

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "store_id": "123456",
    "title": "FAQ Exemplo",
    "active": true,
    "questions": [...],
    "bindings": [...]
  },
  "message": "FAQ obtido com sucesso"
}
```

---

#### POST `/api/faqs`
Criar um novo FAQ.

**Request Body:**
```json
{
  "title": "Perguntas frequentes do produto XYZ",
  "active": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "store_id": "123456",
    "title": "Perguntas frequentes do produto XYZ",
    "active": true,
    "created_at": "2025-02-09T10:00:00Z",
    "updated_at": "2025-02-09T10:00:00Z"
  },
  "message": "FAQ criado com sucesso"
}
```

---

#### PUT `/api/faqs/{id}`
Atualizar um FAQ.

**Parameters:**
- `id` (integer, required): ID do FAQ

**Request Body:**
```json
{
  "title": "Título atualizado",
  "active": false
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "store_id": "123456",
    "title": "Título atualizado",
    "active": false,
    "updated_at": "2025-02-09T10:30:00Z"
  },
  "message": "FAQ atualizado com sucesso"
}
```

---

#### DELETE `/api/faqs/{id}`
Deletar um FAQ e todas suas perguntas e bindings.

**Parameters:**
- `id` (integer, required): ID do FAQ

**Response:**
```json
{
  "success": true,
  "message": "FAQ deletado com sucesso"
}
```

---

### Perguntas (Questions)

#### POST `/api/faqs/{faqId}/questions`
Adicionar uma pergunta a um FAQ.

**Parameters:**
- `faqId` (integer, required): ID do FAQ

**Request Body:**
```json
{
  "question": "Qual é a garantia deste produto?",
  "answer": "Este produto tem uma garantia de 2 anos contra defeitos de fabricação.",
  "order": 0
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "faq_id": 1,
    "question": "Qual é a garantia deste produto?",
    "answer": "Este produto tem uma garantia de 2 anos contra defeitos de fabricação.",
    "order": 0,
    "created_at": "2025-02-09T10:00:00Z",
    "updated_at": "2025-02-09T10:00:00Z"
  },
  "message": "Pergunta adicionada com sucesso"
}
```

---

#### PUT `/api/faqs/questions/{questionId}`
Atualizar uma pergunta.

**Parameters:**
- `questionId` (integer, required): ID da pergunta

**Request Body:**
```json
{
  "question": "Pergunta atualizada?",
  "answer": "Resposta atualizada.",
  "order": 1
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "faq_id": 1,
    "question": "Pergunta atualizada?",
    "answer": "Resposta atualizada.",
    "order": 1,
    "updated_at": "2025-02-09T10:30:00Z"
  },
  "message": "Pergunta atualizada com sucesso"
}
```

---

#### DELETE `/api/faqs/questions/{questionId}`
Deletar uma pergunta.

**Parameters:**
- `questionId` (integer, required): ID da pergunta

**Response:**
```json
{
  "success": true,
  "message": "Pergunta deletada com sucesso"
}
```

---

### Bindings

#### POST `/api/faqs/{faqId}/bindings`
Vincular um FAQ a uma página (produto, categoria ou homepage).

**Parameters:**
- `faqId` (integer, required): ID do FAQ

**Request Body:**
```json
{
  "bindable_type": "product",
  "bindable_id": "12345",
  "category_handle": null
}
```

**Tipos de binding:**
- `"homepage"`: Para homepage (deixar `bindable_id` e `category_handle` como null)
- `"product"`: Para produto (informar o `bindable_id` do produto)
- `"category"`: Para categoria (informar o `category_handle` da categoria)

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "faq_id": 1,
    "bindable_type": "product",
    "bindable_id": "12345",
    "category_handle": null,
    "created_at": "2025-02-09T10:00:00Z"
  },
  "message": "Binding criado com sucesso"
}
```

---

#### DELETE `/api/faqs/bindings/{bindingId}`
Remover um binding.

**Parameters:**
- `bindingId` (integer, required): ID do binding

**Response:**
```json
{
  "success": true,
  "message": "Binding deletado com sucesso"
}
```

---

## Rotas Públicas (Sem Autenticação)

### GET `/public/faqs/{storeId}/product/{productId}`
Obter FAQ de um produto específico.

**Parameters:**
- `storeId` (string, required): ID da loja
- `productId` (string, required): ID do produto

**Response:**
```json
{
  "id": 1,
  "title": "Perguntas frequentes do produto XYZ",
  "active": true,
  "questions": [
    {
      "question": "Qual é a garantia deste produto?",
      "answer": "Este produto tem uma garantia de 2 anos contra defeitos de fabricação."
    },
    {
      "question": "Posso devolver o produto se não estiver satisfeito?",
      "answer": "Sim, você pode devolver o produto dentro de 30 dias após a compra, desde que esteja em condições originais."
    }
  ]
}
```

---

### GET `/public/faqs/{storeId}/category/{categoryHandle}`
Obter FAQ de uma categoria específica.

**Parameters:**
- `storeId` (string, required): ID da loja
- `categoryHandle` (string, required): Handle da categoria

**Response:**
```json
{
  "id": 1,
  "title": "Perguntas frequentes da categoria XYZ",
  "active": true,
  "questions": [
    {
      "question": "Qual é a garantia deste produto?",
      "answer": "Este produto tem uma garantia de 2 anos contra defeitos de fabricação."
    }
  ]
}
```

---

### GET `/public/faqs/{storeId}/homepage`
Obter FAQ da homepage da loja.

**Parameters:**
- `storeId` (string, required): ID da loja

**Response:**
```json
{
  "id": 1,
  "title": "Perguntas frequentes da home",
  "active": true,
  "questions": [
    {
      "question": "Como funciona?",
      "answer": "Resposta sobre o funcionamento."
    }
  ]
}
```

---

## Rota de Instalação

### GET `/api/ns/install?code={code}`
Rota de callback para instalação do app na Nuvemshop.

**Parameters:**
- `code` (string, required): Código de autorização da Nuvemshop

**Response:**
```json
{
  "success": true,
  "message": "Instalação realizada com sucesso",
  "data": {
    "user_id": "123456",
    "access_token": "...",
    "refresh_token": "..."
  }
}
```

---

## Tratamento de Erros

### Erro de Validação
```json
{
  "success": false,
  "data": null,
  "message": "Validation failed",
  "errors": {
    "title": ["The title field is required"]
  }
}
```
**Status: 422**

### Erro 404
```json
{
  "success": false,
  "data": null,
  "message": "Resource not found"
}
```
**Status: 404**

### Erro de Autenticação
```json
{
  "success": false,
  "message": "Token de autenticação não fornecido",
  "error": "unauthorized"
}
```
**Status: 401**

### Erro Interno
```json
{
  "success": false,
  "data": null,
  "message": "Internal server error"
}
```
**Status: 500**
