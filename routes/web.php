<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api'], function () use ($router) {
    // Rota pública de instalação do app Nuvemshop
    $router->get('/ns/install', 'NuvemshopController@install');

    // Proxy para dados da Nuvemshop (produtos, categorias)
    $router->group(['prefix' => 'ns', 'middleware' => 'nexo.auth'], function () use ($router) {
        $router->get('/products', 'NuvemshopProxyController@products');
        $router->get('/categories', 'NuvemshopProxyController@categories');
    });

    // Rotas protegidas pelo middleware Nexo (requer autenticação via token JWT)
    $router->group(['prefix' => 'faqs', 'middleware' => 'nexo.auth'], function () use ($router) {
        // CRUD de FAQs
        $router->get('/', 'FaqController@index');
        $router->post('/', 'FaqController@store');
        $router->get('/{id}', 'FaqController@show');
        $router->put('/{id}', 'FaqController@update');
        $router->delete('/{id}', 'FaqController@destroy');

        // Perguntas do FAQ
        $router->post('/{faqId}/questions', 'FaqController@addQuestion');
        $router->put('/questions/{questionId}', 'FaqController@updateQuestion');
        $router->delete('/questions/{questionId}', 'FaqController@deleteQuestion');

        // Vínculos com produtos
        $router->post('/{faqId}/products', 'FaqController@addProductBinding');
        $router->delete('/{faqId}/products/{productId}', 'FaqController@removeProductBinding');

        // Vínculos com categorias
        $router->post('/{faqId}/categories', 'FaqController@addCategoryBinding');
        $router->delete('/{faqId}/categories/{categoryId}', 'FaqController@removeCategoryBinding');
    });

    // Rotas públicas para consumir FAQs (para frontend ou widgets)
    // Estas rotas requerem o store_id como parâmetro na URL
    $router->group(['prefix' => 'public'], function () use ($router) {
        // FAQ por produto
        $router->get('/faqs/{storeId}/product/{productId}', 'FaqController@getProductFaq');
        
        // FAQ por categoria (usando handle)
        $router->get('/faqs/{storeId}/category/{categoryHandle}', 'FaqController@getCategoryFaq');
        
        // FAQ da homepage
        $router->get('/faqs/{storeId}/homepage', 'FaqController@getHomepageFaq');

        // Verificar se já existe FAQ vinculado
        $router->get('/check/product/{storeId}/{productId}', 'FaqController@checkProductFaq');
        $router->get('/check/category/{storeId}/{categoryHandle}', 'FaqController@checkCategoryFaq');
        $router->get('/check/homepage/{storeId}', 'FaqController@checkHomepageFaq');
    });
});
