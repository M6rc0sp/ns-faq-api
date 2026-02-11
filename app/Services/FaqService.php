<?php

namespace App\Services;

use App\Models\Faq;
use App\Models\FaqQuestion;
use App\Models\FaqProductBinding;
use App\Models\FaqCategoryBinding;
use Illuminate\Support\Facades\Log;

class FaqService
{
    // ─── FAQ CRUD ───────────────────────────────────────────────

    public function getAllFaqs(string $storeId): array
    {
        try {
            $faqs = Faq::byStore($storeId)
                ->with([
                    'questions' => fn($q) => $q->orderBy('order'),
                    'productBindings',
                    'categoryBindings',
                ])
                ->get();

            return ['success' => true, 'data' => $faqs];
        } catch (\Exception $e) {
            Log::error('Erro ao obter FAQs: ' . $e->getMessage());
            return ['success' => false, 'data' => [], 'message' => $e->getMessage()];
        }
    }

    public function getFaq(string $storeId, int $faqId): array
    {
        try {
            $faq = Faq::byStore($storeId)
                ->with([
                    'questions' => fn($q) => $q->orderBy('order'),
                    'productBindings',
                    'categoryBindings',
                ])
                ->find($faqId);

            if (!$faq) {
                return ['success' => false, 'data' => null, 'message' => 'FAQ não encontrado'];
            }

            return ['success' => true, 'data' => $faq];
        } catch (\Exception $e) {
            Log::error('Erro ao obter FAQ: ' . $e->getMessage());
            return ['success' => false, 'data' => null, 'message' => $e->getMessage()];
        }
    }

    public function createFaq(string $storeId, array $data): array
    {
        try {
            $faq = Faq::create([
                'store_id' => $storeId,
                'title' => $data['title'],
                'active' => $data['active'] ?? true,
                'show_on_homepage' => $data['show_on_homepage'] ?? false,
            ]);

            $faq->load(['questions', 'productBindings', 'categoryBindings']);

            Log::info("FAQ criado: {$faq->id} para store {$storeId}");
            return ['success' => true, 'data' => $faq];
        } catch (\Exception $e) {
            Log::error('Erro ao criar FAQ: ' . $e->getMessage());
            return ['success' => false, 'data' => null, 'message' => $e->getMessage()];
        }
    }

    public function updateFaq(string $storeId, int $faqId, array $data): array
    {
        try {
            $faq = Faq::byStore($storeId)->find($faqId);

            if (!$faq) {
                return ['success' => false, 'data' => null, 'message' => 'FAQ não encontrado'];
            }

            // Se está marcando como homepage, remover de todos os outros FAQs do store
            if (isset($data['show_on_homepage']) && $data['show_on_homepage'] === true) {
                Log::info("Marcando FAQ $faqId como homepage. Removendo homepage de outros FAQs do store...");
                Faq::byStore($storeId)
                    ->where('id', '!=', $faqId)
                    ->update(['show_on_homepage' => false]);
                Log::info("Outros FAQs desmarcados de homepage");
            }

            $allowed = array_intersect_key($data, array_flip(['title', 'active', 'show_on_homepage']));
            $faq->update($allowed);
            $faq->load(['questions', 'productBindings', 'categoryBindings']);

            return ['success' => true, 'data' => $faq];
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar FAQ: ' . $e->getMessage());
            return ['success' => false, 'data' => null, 'message' => $e->getMessage()];
        }
    }

    public function deleteFaq(string $storeId, int $faqId): array
    {
        try {
            $faq = Faq::byStore($storeId)->find($faqId);

            if (!$faq) {
                return ['success' => false, 'message' => 'FAQ não encontrado'];
            }

            $faq->questions()->delete();
            $faq->productBindings()->delete();
            $faq->categoryBindings()->delete();
            $faq->delete();

            return ['success' => true, 'message' => 'FAQ deletado com sucesso'];
        } catch (\Exception $e) {
            Log::error('Erro ao deletar FAQ: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─── QUESTIONS ──────────────────────────────────────────────

    public function addQuestion(string $storeId, int $faqId, array $data): array
    {
        try {
            $faq = Faq::byStore($storeId)->find($faqId);
            if (!$faq) {
                return ['success' => false, 'data' => null, 'message' => 'FAQ não encontrado'];
            }

            $question = FaqQuestion::create([
                'faq_id' => $faqId,
                'question' => $data['question'],
                'answer' => $data['answer'],
                'order' => $data['order'] ?? 0,
            ]);

            return ['success' => true, 'data' => $question];
        } catch (\Exception $e) {
            Log::error('Erro ao adicionar pergunta: ' . $e->getMessage());
            return ['success' => false, 'data' => null, 'message' => $e->getMessage()];
        }
    }

    public function updateQuestion(string $storeId, int $questionId, array $data): array
    {
        try {
            $question = FaqQuestion::whereHas('faq', fn($q) => $q->where('store_id', $storeId))
                ->find($questionId);

            if (!$question) {
                return ['success' => false, 'data' => null, 'message' => 'Pergunta não encontrada'];
            }

            $allowed = array_intersect_key($data, array_flip(['question', 'answer', 'order']));
            $question->update($allowed);

            return ['success' => true, 'data' => $question];
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar pergunta: ' . $e->getMessage());
            return ['success' => false, 'data' => null, 'message' => $e->getMessage()];
        }
    }

    public function deleteQuestion(string $storeId, int $questionId): array
    {
        try {
            $question = FaqQuestion::whereHas('faq', fn($q) => $q->where('store_id', $storeId))
                ->find($questionId);

            if (!$question) {
                return ['success' => false, 'message' => 'Pergunta não encontrada'];
            }

            $question->delete();
            return ['success' => true, 'message' => 'Pergunta deletada'];
        } catch (\Exception $e) {
            Log::error('Erro ao deletar pergunta: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─── PRODUCT BINDINGS ───────────────────────────────────────

    public function addProductBinding(string $storeId, int $faqId, string $productId): array
    {
        try {
            Log::info("Iniciando addProductBinding: storeId=$storeId, faqId=$faqId, productId=$productId");
            
            $faq = Faq::byStore($storeId)->find($faqId);
            if (!$faq) {
                Log::warning("FAQ não encontrado: storeId=$storeId, faqId=$faqId");
                return ['success' => false, 'data' => null, 'message' => 'FAQ não encontrado'];
            }

            // Remover o produto de QUALQUER outro FAQ deste store (garantir exclusividade)
            Log::info("Removendo produto de outros FAQs do store...");
            $otherBindings = FaqProductBinding::join('faqs', 'faq_product_bindings.faq_id', '=', 'faqs.id')
                ->where('faqs.store_id', $storeId)
                ->where('faq_product_bindings.product_id', $productId)
                ->where('faq_product_bindings.faq_id', '!=', $faqId)
                ->delete();
            Log::info("Removidas {$otherBindings} vinculações antigas do produto");
            
            Log::info("Produto removido de outros FAQs. Agora criando binding no FAQ atual...");
            
            $binding = FaqProductBinding::firstOrCreate([
                'faq_id' => $faqId,
                'product_id' => $productId,
            ]);

            Log::info("Binding de produto criado com sucesso: id={$binding->id}");
            return ['success' => true, 'data' => $binding];
        } catch (\Exception $e) {
            Log::error('Erro ao vincular produto: ' . $e->getMessage(), ['exception' => $e]);
            return ['success' => false, 'data' => null, 'message' => $e->getMessage()];
        }
    }

    public function removeProductBinding(string $storeId, int $faqId, string $productId): array
    {
        try {
            $faq = Faq::byStore($storeId)->find($faqId);
            if (!$faq) {
                return ['success' => false, 'message' => 'FAQ não encontrado'];
            }

            $deleted = FaqProductBinding::where('faq_id', $faqId)
                ->where('product_id', $productId)
                ->delete();

            if (!$deleted) {
                return ['success' => false, 'message' => 'Vínculo não encontrado'];
            }

            return ['success' => true, 'message' => 'Produto desvinculado'];
        } catch (\Exception $e) {
            Log::error('Erro ao desvincular produto: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─── CATEGORY BINDINGS ──────────────────────────────────────

    public function addCategoryBinding(string $storeId, int $faqId, string $categoryId, ?string $categoryHandle = null): array
    {
        try {
            Log::info("Iniciando addCategoryBinding: storeId=$storeId, faqId=$faqId, categoryId=$categoryId, categoryHandle=$categoryHandle");
            
            $faq = Faq::byStore($storeId)->find($faqId);
            if (!$faq) {
                Log::warning("FAQ não encontrado: storeId=$storeId, faqId=$faqId");
                return ['success' => false, 'data' => null, 'message' => 'FAQ não encontrado'];
            }

            // Remover a categoria de QUALQUER outro FAQ este store (garantir exclusividade)
            Log::info("Removendo categoria de outros FAQs do store...");
            $otherBindings = FaqCategoryBinding::join('faqs', 'faq_category_bindings.faq_id', '=', 'faqs.id')
                ->where('faqs.store_id', $storeId)
                ->where('faq_category_bindings.category_id', $categoryId)
                ->where('faq_category_bindings.faq_id', '!=', $faqId)
                ->delete();
            Log::info("Removidas {$otherBindings} vinculações antigas da categoria");
            
            Log::info("Categoria removida de outros FAQs. Agora criando binding no FAQ atual...");
            
            $binding = FaqCategoryBinding::updateOrCreate(
                ['faq_id' => $faqId, 'category_id' => $categoryId],
                ['category_handle' => $categoryHandle]
            );

            Log::info("Binding de categoria criado com sucesso: id={$binding->id}");
            return ['success' => true, 'data' => $binding];
        } catch (\Exception $e) {
            Log::error('Erro ao vincular categoria: ' . $e->getMessage(), ['exception' => $e]);
            return ['success' => false, 'data' => null, 'message' => $e->getMessage()];
        }
    }

    public function removeCategoryBinding(string $storeId, int $faqId, string $categoryId): array
    {
        try {
            $faq = Faq::byStore($storeId)->find($faqId);
            if (!$faq) {
                return ['success' => false, 'message' => 'FAQ não encontrado'];
            }

            $deleted = FaqCategoryBinding::where('faq_id', $faqId)
                ->where('category_id', $categoryId)
                ->delete();

            if (!$deleted) {
                return ['success' => false, 'message' => 'Vínculo não encontrado'];
            }

            return ['success' => true, 'message' => 'Categoria desvinculada'];
        } catch (\Exception $e) {
            Log::error('Erro ao desvincular categoria: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─── PUBLIC QUERIES ─────────────────────────────────────────

    private function formatPublicFaq(Faq $faq): array
    {
        return [
            'id' => $faq->id,
            'title' => $faq->title,
            'active' => $faq->active,
            'questions' => $faq->questions->map(fn($q) => [
                'question' => $q->question,
                'answer' => $q->answer,
            ]),
        ];
    }

    public function getFaqByProduct(string $storeId, string $productId): array
    {
        try {
            $faq = Faq::byStore($storeId)
                ->active()
                ->whereHas('productBindings', fn($q) => $q->where('product_id', $productId))
                ->with(['questions' => fn($q) => $q->orderBy('order')])
                ->first();

            if (!$faq) {
                return ['success' => false, 'data' => null, 'message' => 'FAQ não encontrado para este produto'];
            }

            return ['success' => true, 'data' => $this->formatPublicFaq($faq)];
        } catch (\Exception $e) {
            Log::error('Erro ao obter FAQ do produto: ' . $e->getMessage());
            return ['success' => false, 'data' => null, 'message' => $e->getMessage()];
        }
    }

    public function getFaqByCategory(string $storeId, string $categoryHandle): array
    {
        try {
            $faq = Faq::byStore($storeId)
                ->active()
                ->whereHas('categoryBindings', fn($q) => $q->where('category_handle', $categoryHandle))
                ->with(['questions' => fn($q) => $q->orderBy('order')])
                ->first();

            if (!$faq) {
                return ['success' => false, 'data' => null, 'message' => 'FAQ não encontrado para esta categoria'];
            }

            return ['success' => true, 'data' => $this->formatPublicFaq($faq)];
        } catch (\Exception $e) {
            Log::error('Erro ao obter FAQ da categoria: ' . $e->getMessage());
            return ['success' => false, 'data' => null, 'message' => $e->getMessage()];
        }
    }

    public function getFaqByHomepage(string $storeId): array
    {
        try {
            $faq = Faq::byStore($storeId)
                ->active()
                ->homepage()
                ->with(['questions' => fn($q) => $q->orderBy('order')])
                ->first();

            if (!$faq) {
                return ['success' => false, 'data' => null, 'message' => 'FAQ não encontrado para a homepage'];
            }

            return ['success' => true, 'data' => $this->formatPublicFaq($faq)];
        } catch (\Exception $e) {
            Log::error('Erro ao obter FAQ da homepage: ' . $e->getMessage());
            return ['success' => false, 'data' => null, 'message' => $e->getMessage()];
        }
    }

    // ─── CHECK EXISTING BINDINGS ────────────────────────────────

    public function checkExistingProductFaq(string $storeId, string $productId): array
    {
        try {
            $faq = Faq::byStore($storeId)
                ->active()
                ->whereHas('productBindings', fn($q) => $q->where('product_id', $productId))
                ->with(['questions' => fn($q) => $q->orderBy('order')])
                ->first();

            if (!$faq) {
                return ['success' => true, 'exists' => false, 'data' => null];
            }

            return ['success' => true, 'exists' => true, 'data' => $this->formatPublicFaq($faq)];
        } catch (\Exception $e) {
            Log::error('Erro ao verificar FAQ do produto: ' . $e->getMessage());
            return ['success' => false, 'exists' => false, 'data' => null];
        }
    }

    public function checkExistingCategoryFaq(string $storeId, string $categoryHandle): array
    {
        try {
            $faq = Faq::byStore($storeId)
                ->active()
                ->whereHas('categoryBindings', fn($q) => $q->where('category_handle', $categoryHandle))
                ->with(['questions' => fn($q) => $q->orderBy('order')])
                ->first();

            if (!$faq) {
                return ['success' => true, 'exists' => false, 'data' => null];
            }

            return ['success' => true, 'exists' => true, 'data' => $this->formatPublicFaq($faq)];
        } catch (\Exception $e) {
            Log::error('Erro ao verificar FAQ da categoria: ' . $e->getMessage());
            return ['success' => false, 'exists' => false, 'data' => null];
        }
    }

    public function checkExistingHomepageFaq(string $storeId): array
    {
        try {
            $faq = Faq::byStore($storeId)
                ->active()
                ->homepage()
                ->with(['questions' => fn($q) => $q->orderBy('order')])
                ->first();

            if (!$faq) {
                return ['success' => true, 'exists' => false, 'data' => null];
            }

            return ['success' => true, 'exists' => true, 'data' => $this->formatPublicFaq($faq)];
        } catch (\Exception $e) {
            Log::error('Erro ao verificar FAQ da homepage: ' . $e->getMessage());
            return ['success' => false, 'exists' => false, 'data' => null];
        }
    }
}
