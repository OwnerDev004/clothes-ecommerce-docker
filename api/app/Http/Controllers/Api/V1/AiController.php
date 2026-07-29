<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Api\V1\AI\ProductSearchAIService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use LucianoTonet\GroqPHP\Groq;

class AiController extends Controller
{
    use ApiResponse;
    protected $productsearchService;

    public function __construct(ProductSearchAIService $product_search_service)
    {
        $this->productsearchService = $product_search_service;
    }
    public function index(Request $request)
    {
        $message = $request->validate([
            'message' => 'required'
        ]);
        $groq = new Groq(getenv('GROQ_API_KEY'));
        try {
            $response = $groq->chat()->completions()->create([
                'model' => 'llama-3.3-70b-versatile', // Or another supported model
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'You are ai assistant for search product, please Convert user search into JSON filters OUTPUT FORMAT (STRICT JSON):
                                    {
                                    "search_txt": null,
                                    "category": null,
                                    "sub_category": null,
                                    "price": null,
                                    "price_min": null,
                                    "price_max": null,
                                    "color": null,
                                    "size": null,
                                    "brand": null,
                                    "collection": null,
                                    "dress_style": null,
                                    "sort_by": null
                                    } ' . $message['message']
                    ],
                ],
            ]);

            return $this->success($response['choices'][0]['message']['content'], 'openAIKEY', 200);
        } catch (\LucianoTonet\GroqPHP\GroqException $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }

    public function productFilter(Request $request)
    {
        $filter_prompt = $request->validate([
            'message' => 'required|string'
        ]);
        $result = $this->productsearchService->productAiFilter($filter_prompt['message']);
        return $this->paginate($result, 'Products list');
    }
}
