<?php

namespace App\Http\Controllers;

use App\Services\FaqService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FaqController extends Controller
{
    protected FaqService $faqService;

    public function __construct(FaqService $faqService)
    {
        $this->faqService = $faqService;
    }

    private function getStoreId(Request $request): ?string
    {
        return $request->attributes->get('store_id') ?? $request->input('auth_store_id');
    }

    // ─── FAQ CRUD ───────────────────────────────────────────────

    public function index(Request $request)
    {
        $storeId = $this->getStoreId($request);
        $result = $this->faqService->getAllFaqs($storeId);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function show(Request $request, $id)
    {
        $storeId = $this->getStoreId($request);
        $result = $this->faqService->getFaq($storeId, $id);

        return response()->json($result, $result['success'] ? 200 : 404);
    }

    public function store(Request $request)
    {
        $storeId = $this->getStoreId($request);

        $this->validate($request, [
            'title' => 'required|string|max:255',
            'active' => 'boolean',
            'show_on_homepage' => 'boolean',
        ]);

        $result = $this->faqService->createFaq($storeId, $request->all());

        return response()->json($result, $result['success'] ? 201 : 400);
    }

    public function update(Request $request, $id)
    {
        $storeId = $this->getStoreId($request);

        $this->validate($request, [
            'title' => 'string|max:255',
            'active' => 'boolean',
            'show_on_homepage' => 'boolean',
        ]);

        $result = $this->faqService->updateFaq($storeId, $id, $request->all());

        return response()->json($result, $result['success'] ? 200 : 404);
    }

    public function destroy(Request $request, $id)
    {
        $storeId = $this->getStoreId($request);
        $result = $this->faqService->deleteFaq($storeId, $id);

        return response()->json($result, $result['success'] ? 200 : 404);
    }

    // ─── QUESTIONS ──────────────────────────────────────────────

    public function addQuestion(Request $request, $faqId)
    {
        $storeId = $this->getStoreId($request);

        $this->validate($request, [
            'question' => 'required|string',
            'answer' => 'required|string',
            'order' => 'integer',
        ]);

        $result = $this->faqService->addQuestion($storeId, $faqId, $request->all());

        return response()->json($result, $result['success'] ? 201 : 400);
    }

    public function updateQuestion(Request $request, $questionId)
    {
        $storeId = $this->getStoreId($request);

        $this->validate($request, [
            'question' => 'string',
            'answer' => 'string',
            'order' => 'integer',
        ]);

        $result = $this->faqService->updateQuestion($storeId, $questionId, $request->all());

        return response()->json($result, $result['success'] ? 200 : 404);
    }

    public function deleteQuestion(Request $request, $questionId)
    {
        $storeId = $this->getStoreId($request);
        $result = $this->faqService->deleteQuestion($storeId, $questionId);

        return response()->json($result, $result['success'] ? 200 : 404);
    }

    // ─── PRODUCT BINDINGS ───────────────────────────────────────

    public function addProductBinding(Request $request, $faqId)
    {
        $storeId = $this->getStoreId($request);

        $this->validate($request, [
            'product_id' => 'required|string',
        ]);

        $result = $this->faqService->addProductBinding($storeId, $faqId, $request->input('product_id'));

        return response()->json($result, $result['success'] ? 201 : 400);
    }

    public function removeProductBinding(Request $request, $faqId, $productId)
    {
        $storeId = $this->getStoreId($request);
        $result = $this->faqService->removeProductBinding($storeId, $faqId, $productId);

        return response()->json($result, $result['success'] ? 200 : 404);
    }

    // ─── CATEGORY BINDINGS ──────────────────────────────────────

    public function addCategoryBinding(Request $request, $faqId)
    {
        $storeId = $this->getStoreId($request);

        $this->validate($request, [
            'category_id' => 'required|string',
            'category_handle' => 'nullable|string',
        ]);

        $result = $this->faqService->addCategoryBinding(
            $storeId,
            $faqId,
            $request->input('category_id'),
            $request->input('category_handle')
        );

        return response()->json($result, $result['success'] ? 201 : 400);
    }

    public function removeCategoryBinding(Request $request, $faqId, $categoryId)
    {
        $storeId = $this->getStoreId($request);
        $result = $this->faqService->removeCategoryBinding($storeId, $faqId, $categoryId);

        return response()->json($result, $result['success'] ? 200 : 404);
    }

    // ─── PUBLIC ENDPOINTS ───────────────────────────────────────

    public function getProductFaq(Request $request, $storeId, $productId)
    {
        $result = $this->faqService->getFaqByProduct($storeId, $productId);

        if (!$result['success']) {
            return response()->json($result, 404);
        }

        return response()->json($result['data']);
    }

    public function getCategoryFaq(Request $request, $storeId, $categoryHandle)
    {
        $result = $this->faqService->getFaqByCategory($storeId, $categoryHandle);

        if (!$result['success']) {
            return response()->json($result, 404);
        }

        return response()->json($result['data']);
    }

    public function getHomepageFaq(Request $request, $storeId)
    {
        $result = $this->faqService->getFaqByHomepage($storeId);

        if (!$result['success']) {
            return response()->json($result, 404);
        }

        return response()->json($result['data']);
    }

    public function checkProductFaq(Request $request, $storeId, $productId)
    {
        return response()->json($this->faqService->checkExistingProductFaq($storeId, $productId));
    }

    public function checkCategoryFaq(Request $request, $storeId, $categoryHandle)
    {
        return response()->json($this->faqService->checkExistingCategoryFaq($storeId, $categoryHandle));
    }

    public function checkHomepageFaq(Request $request, $storeId)
    {
        return response()->json($this->faqService->checkExistingHomepageFaq($storeId));
    }
}
