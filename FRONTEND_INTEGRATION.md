# Integração Front-end com FAQ API

Guia para integrar a FAQ API no aplicativo front-end React/TypeScript.

## 🔌 Endpoints Principais

### URL Base
```
http://localhost:8000
```

### Headers Necessários

**Para rotas admin (autenticadas):**
```javascript
{
  'Authorization': 'Bearer YOUR_JWT_TOKEN',
  'Content-Type': 'application/json'
}
```

**Para rotas públicas (sem autenticação):**
```javascript
{
  'Content-Type': 'application/json'
}
```

---

## 📋 Exemplos de Integração

### 1. Criar FAQ (Admin)

```typescript
async function createFaq(title: string, token: string) {
  const response = await fetch('http://localhost:8000/api/faqs', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      title,
      active: true
    })
  });

  return response.json();
}
```

### 2. Listar FAQs (Admin)

```typescript
async function getAllFaqs(token: string) {
  const response = await fetch('http://localhost:8000/api/faqs', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });

  return response.json();
}
```

### 3. Adicionar Pergunta (Admin)

```typescript
async function addQuestion(
  faqId: number,
  question: string,
  answer: string,
  token: string
) {
  const response = await fetch(
    `http://localhost:8000/api/faqs/${faqId}/questions`,
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        question,
        answer,
        order: 0
      })
    }
  );

  return response.json();
}
```

### 4. Vincular FAQ (Admin)

```typescript
async function linkFaqToProduct(
  faqId: number,
  productId: string,
  token: string
) {
  const response = await fetch(
    `http://localhost:8000/api/faqs/${faqId}/bindings`,
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        bindable_type: 'product',
        bindable_id: productId
      })
    }
  );

  return response.json();
}
```

### 5. Obter FAQ de Produto (Público)

```typescript
async function getProductFaq(storeId: string, productId: string) {
  const response = await fetch(
    `http://localhost:8000/public/faqs/${storeId}/product/${productId}`
  );

  if (!response.ok) {
    return null; // FAQ não encontrado
  }

  return response.json();
}
```

### 6. Obter FAQ de Categoria (Público)

```typescript
async function getCategoryFaq(storeId: string, categoryHandle: string) {
  const response = await fetch(
    `http://localhost:8000/public/faqs/${storeId}/category/${categoryHandle}`
  );

  if (!response.ok) {
    return null;
  }

  return response.json();
}
```

### 7. Obter FAQ de Homepage (Público)

```typescript
async function getHomepageFaq(storeId: string) {
  const response = await fetch(
    `http://localhost:8000/public/faqs/${storeId}/homepage`
  );

  if (!response.ok) {
    return null;
  }

  return response.json();
}
```

---

## 🎨 Componente React de Exemplo

### Exibir FAQ em Página de Produto

```typescript
import React, { useEffect, useState } from 'react';

interface FaqData {
  id: number;
  title: string;
  active: boolean;
  questions: Array<{
    question: string;
    answer: string;
  }>;
}

export function ProductFaq() {
  const [faq, setFaq] = useState<FaqData | null>(null);
  const [loading, setLoading] = useState(true);
  const storeId = localStorage.getItem('storeId') || '';
  const productId = new URLSearchParams(window.location.search).get('id') || '';

  useEffect(() => {
    loadFaq();
  }, [storeId, productId]);

  async function loadFaq() {
    try {
      const response = await fetch(
        `http://localhost:8000/public/faqs/${storeId}/product/${productId}`
      );

      if (response.ok) {
        setFaq(await response.json());
      }
    } catch (error) {
      console.error('Erro ao carregar FAQ:', error);
    } finally {
      setLoading(false);
    }
  }

  if (loading) {
    return <div>Carregando...</div>;
  }

  if (!faq) {
    return null; // FAQ não encontrado
  }

  return (
    <section className="product-faq">
      <h2>{faq.title}</h2>
      <div className="faq-questions">
        {faq.questions.map((q, index) => (
          <details key={index}>
            <summary>{q.question}</summary>
            <p>{q.answer}</p>
          </details>
        ))}
      </div>
    </section>
  );
}
```

### Gerenciar FAQs (Admin)

```typescript
import React, { useEffect, useState } from 'react';

interface Faq {
  id: number;
  title: string;
  active: boolean;
  questions: Array<{
    id: number;
    question: string;
    answer: string;
  }>;
}

export function FaqManager() {
  const [faqs, setFaqs] = useState<Faq[]>([]);
  const [loading, setLoading] = useState(true);
  const token = localStorage.getItem('authToken') || '';

  useEffect(() => {
    loadFaqs();
  }, []);

  async function loadFaqs() {
    try {
      const response = await fetch('http://localhost:8000/api/faqs', {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });

      if (response.ok) {
        const data = await response.json();
        setFaqs(data.data || []);
      }
    } catch (error) {
      console.error('Erro ao carregar FAQs:', error);
    } finally {
      setLoading(false);
    }
  }

  async function createFaq(title: string) {
    const response = await fetch('http://localhost:8000/api/faqs', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ title, active: true })
    });

    if (response.ok) {
      await loadFaqs();
    }
  }

  async function deleteFaq(id: number) {
    if (confirm('Tem certeza?')) {
      const response = await fetch(`http://localhost:8000/api/faqs/${id}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });

      if (response.ok) {
        await loadFaqs();
      }
    }
  }

  if (loading) {
    return <div>Carregando...</div>;
  }

  return (
    <div className="faq-manager">
      <h1>Gerenciar FAQs</h1>

      <div className="create-section">
        <input
          type="text"
          placeholder="Título do novo FAQ"
          onKeyPress={(e) => {
            if (e.key === 'Enter') {
              createFaq(e.currentTarget.value);
              e.currentTarget.value = '';
            }
          }}
        />
      </div>

      <div className="faq-list">
        {faqs.map((faq) => (
          <div key={faq.id} className="faq-card">
            <h3>{faq.title}</h3>
            <p>Perguntas: {faq.questions.length}</p>
            <p>Status: {faq.active ? '✅ Ativo' : '❌ Inativo'}</p>

            <button onClick={() => deleteFaq(faq.id)}>Deletar</button>
            <button onClick={() => window.location.href = `/admin/faqs/${faq.id}`}>
              Editar
            </button>
          </div>
        ))}
      </div>
    </div>
  );
}
```

---

## 🔑 Obter Token JWT

O token JWT deve ser obtido da Nexo API. Exemplo:

```javascript
// Ao fazer login
const response = await fetch('https://nexo-auth-api.com/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'user@example.com',
    password: 'senha'
  })
});

const data = await response.json();
localStorage.setItem('authToken', data.token);
localStorage.setItem('storeId', data.storeId);
```

---

## 🚨 Tratamento de Erros

```typescript
async function handleApiError(error: any) {
  if (error.status === 401) {
    console.error('Não autenticado - faça login novamente');
    // Redirecionar para login
  } else if (error.status === 404) {
    console.error('Recurso não encontrado');
  } else if (error.status === 422) {
    console.error('Dados inválidos:', error.errors);
  } else {
    console.error('Erro interno do servidor');
  }
}
```

---

## 📊 Tipos TypeScript

```typescript
interface FaqResponse {
  success: boolean;
  data: Faq | Faq[] | null;
  message: string;
  errors?: Record<string, string[]>;
}

interface Faq {
  id: number;
  store_id: string;
  title: string;
  active: boolean;
  questions: FaqQuestion[];
  bindings: FaqBinding[];
  created_at: string;
  updated_at: string;
}

interface FaqQuestion {
  id: number;
  faq_id: number;
  question: string;
  answer: string;
  order: number;
  created_at: string;
  updated_at: string;
}

interface FaqBinding {
  id: number;
  faq_id: number;
  bindable_type: 'homepage' | 'product' | 'category';
  bindable_id: string | null;
  category_handle: string | null;
  created_at: string;
  updated_at: string;
}
```

---

## 🧪 Testar com cURL

```bash
# Listar FAQs
curl -H "Authorization: Bearer TOKEN" \
  http://localhost:8000/api/faqs

# Criar FAQ
curl -X POST http://localhost:8000/api/faqs \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"FAQ Teste","active":true}'

# Obter FAQ público de produto
curl http://localhost:8000/public/faqs/123456/product/12345
```

---

## 📝 Variáveis de Ambiente (Frontend)

```bash
VITE_API_URL=http://localhost:8000
VITE_STORE_ID=seu_store_id
```

---

## ✨ Próximas Etapas

1. Implementar cache local (localStorage/sessionStorage)
2. Adicionar loading states
3. Implementar paginação se necessário
4. Adicionar otimista updates (UI updates antes da resposta)
5. Integrar com analytics

---

## 🆘 Troubleshooting

**CORS errors?**
- Verifique se o CORS está habilitado na API
- Cheque os headers de resposta

**Token expirado?**
- Implemente refresh token flow
- Redirecione para login quando receber 401

**FAQ não aparece?**
- Verifique se o binding foi criado
- Confira se o FAQ está `active: true`
- Verifique o `storeId`

---

Para mais detalhes, veja [ENDPOINTS.md](ENDPOINTS.md).
