<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Proxy para buscar dados da API da Nuvemshop (produtos, categorias)
 * Usa o access_token da loja armazenado no banco
 */
class NuvemshopProxyController extends Controller
{
    protected string $apiBaseUrl = 'https://api.nuvemshop.com.br/2025-03';

    /**
     * GET /api/ns/products
     * Listar produtos da loja
     */
    public function products(Request $request)
    {
        try {
            $store = $request->attributes->get('store');
            if (!$store || !$store->access_token) {
                return response()->json(['success' => false, 'message' => 'Loja sem token de acesso'], 401);
            }

            $query = $request->input('q', '');
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 25);

            $url = "{$this->apiBaseUrl}/{$store->store_id}/products";
            $params = [
                'page' => $page,
                'per_page' => $perPage,
                'fields' => 'id,name,variants,images',
            ];

            if ($query) {
                $params['q'] = $query;
            }

            $response = Http::withHeaders([
                'Authentication' => 'bearer ' . $store->access_token,
                'User-Agent' => config('services.nuvemshop.user_agent', 'FAQ App'),
            ])->get($url, $params);

            if (!$response->successful()) {
                Log::error('Erro ao buscar produtos', ['status' => $response->status(), 'body' => $response->body()]);
                return response()->json(['success' => false, 'message' => 'Erro ao buscar produtos', 'data' => []], $response->status());
            }

            $products = $response->json();

            // Simplificar dados para o frontend
            $simplified = array_map(function ($product) {
                $image = null;
                if (!empty($product['images'])) {
                    $image = $product['images'][0]['src'] ?? null;
                }
                $name = $product['name']['pt'] ?? $product['name']['es'] ?? $product['name']['en'] ?? (is_string($product['name']) ? $product['name'] : 'Produto');

                return [
                    'id' => $product['id'],
                    'name' => $name,
                    'image' => $image,
                ];
            }, $products);

            return response()->json([
                'success' => true,
                'data' => $simplified,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar produtos: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro interno', 'data' => []], 500);
        }
    }

    /**
     * GET /api/ns/categories
     * Listar categorias da loja
     */
    public function categories(Request $request)
    {
        try {
            $store = $request->attributes->get('store');
            if (!$store || !$store->access_token) {
                return response()->json(['success' => false, 'message' => 'Loja sem token de acesso'], 401);
            }

            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 50);

            $url = "{$this->apiBaseUrl}/{$store->store_id}/categories";
            $params = [
                'page' => $page,
                'per_page' => $perPage,
                'fields' => 'id,name,handle',
            ];

            $response = Http::withHeaders([
                'Authentication' => 'bearer ' . $store->access_token,
                'User-Agent' => config('services.nuvemshop.user_agent', 'FAQ App'),
            ])->get($url, $params);

            if (!$response->successful()) {
                Log::error('Erro ao buscar categorias', ['status' => $response->status(), 'body' => $response->body()]);
                return response()->json(['success' => false, 'message' => 'Erro ao buscar categorias', 'data' => []], $response->status());
            }

            $categories = $response->json();

            // Simplificar dados para o frontend
            $simplified = array_map(function ($cat) {
                $name = $cat['name']['pt'] ?? $cat['name']['es'] ?? $cat['name']['en'] ?? (is_string($cat['name']) ? $cat['name'] : 'Categoria');

                return [
                    'id' => $cat['id'],
                    'name' => $name,
                    'handle' => $cat['handle'] ?? null,
                ];
            }, $categories);

            return response()->json([
                'success' => true,
                'data' => $simplified,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar categorias: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro interno', 'data' => []], 500);
        }
    }
}
