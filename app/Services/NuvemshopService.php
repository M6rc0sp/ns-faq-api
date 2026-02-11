<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NuvemshopService
{
    protected string $clientId;

    protected string $clientSecret;

    protected string $tokenUrl = 'https://www.nuvemshop.com.br/apps/authorize/token';

    protected string $apiBaseUrl = 'https://api.nuvemshop.com.br/2025-03';

    public function __construct()
    {
        $this->clientId = config('services.nuvemshop.client_id');
        $this->clientSecret = config('services.nuvemshop.client_secret');
    }

    /**
     * Authorize app installation with Nuvemshop
     */
    public function authorize(string $code): array
    {
        try {
            Log::info('Tentando autorizar com código: '.substr($code, 0, 10).'...');

            $response = Http::asForm()->post($this->tokenUrl, [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'authorization_code',
                'code' => $code,
            ]);

            if (! $response->successful()) {
                Log::error('Erro na autorização Nuvemshop', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Falha na autorização: '.$response->body(),
                    'status' => $response->status(),
                ];
            }

            $data = $response->json();

            if (! isset($data['access_token'])) {
                Log::error('Token não recebido na resposta', $data);

                return [
                    'success' => false,
                    'message' => 'Falha na autorização: Token não recebido',
                    'status' => 400,
                ];
            }

            Log::info('Token recebido com sucesso: '.substr($data['access_token'], 0, 10).'...');

            // Armazenar tokens na tabela stores
            $storeId = $data['user_id'] ?? $data['store_id'] ?? null;
            if (!$storeId) {
                Log::error('Store ID não encontrado na resposta', $data);
                return [
                    'success' => false,
                    'message' => 'Store ID não fornecido',
                    'status' => 400,
                ];
            }

            // Buscar nome da loja via API
            $storeName = 'Loja ' . $storeId;
            try {
                $storeResponse = Http::withHeaders([
                    'Authentication' => 'bearer ' . $data['access_token'],
                    'User-Agent' => config('services.nuvemshop.user_agent', 'FAQ App'),
                ])->get("{$this->apiBaseUrl}/{$storeId}/store");

                if ($storeResponse->successful()) {
                    $storeInfo = $storeResponse->json();
                    $storeName = $storeInfo['name']['pt'] ?? $storeInfo['name']['es'] ?? $storeName;
                }
            } catch (\Exception $e) {
                Log::warning('Não foi possível buscar nome da loja: ' . $e->getMessage());
            }

            Store::updateOrCreate(
                ['store_id' => $storeId],
                [
                    'store_name' => $storeName,
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? null,
                    'token_expires_at' => isset($data['expires_in']) 
                        ? now()->addSeconds($data['expires_in'])
                        : null,
                    'store_data' => $data,
                ]
            );

            Log::info("Loja {$storeId} salva/atualizada com sucesso");

            return [
                'success' => true,
                'data' => $data,
                'message' => 'Autorização realizada com sucesso',
            ];
        } catch (\Exception $e) {
            Log::error('Erro na autorização Nuvemshop: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro interno: '.$e->getMessage(),
                'status' => 500,
            ];
        }
    }
}
