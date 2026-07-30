<?php
namespace App\Services\Api\V1\AI;

use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\Cache;
use LucianoTonet\GroqPHP\Groq;

class ProductSearchAIService
{
    protected $productRepository;

    public function __construct(ProductRepository $productRepo)
    {
        $this->productRepository = $productRepo;
    }

    public function productAiFilter(string $message): array
    {
        $ttl = (int) env('AI_SEARCH_CACHE_TTL', 3600);
        $hash = sha1($message);
        $filtersKey = "ai_filters:{$hash}";
        $productsKey = "ai_products:{$hash}";

        // Cache hit — return immediately
        if (Cache::has($productsKey)) {
            $filters = Cache::get($filtersKey, []);
            return [
                'products' => Cache::get($productsKey),
                'filters'  => $filters,
            ];
        }

        if (Cache::has($filtersKey)) {
            $filters = Cache::get($filtersKey);
            $products = $this->productRepository->getAll($filters);
            Cache::put($productsKey, $products, $ttl);
            return [
                'products' => $products,
                'filters'  => $filters,
            ];
        }

        // Cache miss — call AI synchronously so the first request gets good results
        $filters = $this->callGroq($message);

        if (empty($filters) || !is_array($filters)) {
            $filters = $this->keywordFallback($message);
        }

        // Persist in cache for subsequent requests
        Cache::put($filtersKey, $filters, $ttl);
        $products = $this->productRepository->getAll($filters);
        Cache::put($productsKey, $products, $ttl);

        return [
            'products' => $products,
            'filters'  => $filters,
        ];
    }

    private function callGroq(string $message): array
    {
        try {
            $groq = new Groq(getenv('GROQ_API_KEY'));
            $response = $groq->chat()->completions()->create([
                'model' => 'llama-3.3-70b-versatile',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a product search assistant for a clothing store. The ONLY valid category slugs are: men, women, boys, girls. Never invent any other category slug. If the user says "clothes", "apparel", "wear", "clothing" — these are NOT categories, put them in search_txt instead and set category to null.',
                    ],
                    [
                        'role' => 'user',
                        'content' => 'Convert this search into JSON with these exact keys. Output ONLY valid JSON, no markdown, no explanation.
{
  "search_txt": "core product keywords only",
  "category": null,
  "sub_category": null,
  "price_min": null,
  "price_max": null,
  "color": null,
  "size": null,
  "brand": null,
  "sort_by": null
}
Rules:
- search_txt: ONLY specific product-type keywords (e.g. "t-shirt", "jeans", "dress", "sneakers", "shirt", "pants", "jacket", "shoes", "hoodie", "blazer"). Do NOT include generic words like "clothes", "apparel", "wear", "clothing", "stuff", "things", "items", "outfit" — set search_txt to null instead.
- price_min / price_max: plain numbers only. "20$" "$20" "20 dollars" "under 20" "price 20" all mean price_max = 20
- color: standard color name or null (black, white, red, blue, green, etc.)
- category: ONLY one of — men, women, boys, girls — or null.
- size: like "32", "M", "L", "XL" or null
- If uncertain or no specific product keyword, use null. Never guess or invent values.

Query: ' . $message,
                    ],
                ],
            ]);

            $rawContent = $response['choices'][0]['message']['content'] ?? '';
            $jsonString = preg_replace('/^```(?:json)?\s*|\s*```$/s', '', $rawContent);
            $filters = json_decode($jsonString, true);

            if (!is_array($filters)) {
                preg_match('/\{.*\}/s', $rawContent, $matches);
                $filters = $matches ? json_decode($matches[0], true) : [];
            }

            return is_array($filters) ? $this->sanitizeFilters($filters) : [];
        } catch (\Throwable $e) {
            \Log::warning('Groq AI call failed, falling back to keyword extraction', [
                'message' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Lightweight regex-based keyword extraction when AI is unavailable.
     */
    private function keywordFallback(string $message): array
    {
        $filters = [];
        $text = strtolower(trim($message));

        // Extract price: "12$$", "$12", "12 dollars", "price 12", "12$"
        if (preg_match('/(?:price|under|below|upto|<|around|~)?\s*\$?\s*(\d{1,6})\s*(?:\$|dollars?|usd)?/i', $text, $m)) {
            $filters['price_max'] = (float) $m[1];
        }

        // Match color names
        $colors = ['black', 'white', 'red', 'blue', 'green', 'yellow', 'pink', 'purple', 'orange', 'brown', 'grey', 'gray', 'navy', 'beige', 'cream', 'gold', 'silver'];
        foreach ($colors as $color) {
            if (str_contains($text, $color)) {
                $filters['color'] = $color;
                $text = str_replace($color, '', $text);
                break;
            }
        }

        // Strip price-like tokens so they don't pollute search_txt
        $text = preg_replace('/\b(price|under|below|upto|around|usd)\s*\$?\s*\d{1,6}\s*\$*/i', '', $text);
        $text = preg_replace('/\b\d{1,6}\s*\$+\s*/', '', $text);
        $text = preg_replace('/\$+\s*\d{1,6}\b/', '', $text);

        // Clean up remaining text for search_txt
        $text = preg_replace('/\s+/', ' ', trim($text));
        $text = preg_replace('/[^\w\s\-]/', '', $text);

        // Strip common stop/filler words
        $stopWords = ['for', 'a', 'an', 'the', 'with', 'and', 'or', 'of', 'to', 'in', 'on', 'at', 'by', 'is', 'are', 'i', 'im', 'my', 'me', 'want', 'need', 'find', 'get', 'show', 'looking', 'that', 'this', 'those', 'these', 'it', 'its', 'have', 'has', 'price', 'all', 'any', 'some'];
        $words = explode(' ', $text);
        $words = array_filter($words, fn($w) => !in_array(strtolower(trim($w)), $stopWords) && trim($w) !== '');
        $text = implode(' ', $words);

        if (trim($text)) {
            $filters['search_txt'] = trim($text);
        }

        return $this->sanitizeFilters($filters);
    }

    private function sanitizeFilters(array $filters): array
    {
        $allowedKeys = [
            'search_txt', 'category', 'sub_category', 'price', 'price_min',
            'price_max', 'color', 'size', 'brand', 'collection', 'dress_style',
            'sort_by', 'per_page', 'new_arrivals',
        ];
        $filters = array_intersect_key($filters, array_flip($allowedKeys));

        foreach (['search_txt', 'sub_category', 'color', 'size', 'brand', 'collection', 'dress_style'] as $key) {
            if (array_key_exists($key, $filters) && !is_null($filters[$key])) {
                $filters[$key] = trim((string) $filters[$key]) ?: null;
            }
        }

        // Only allow valid category slugs — never let the AI hallucinate a category
        if (!empty($filters['category'])) {
            $valid = ['men', 'women', 'boys', 'girls'];
            $slug = strtolower(trim((string) $filters['category']));
            $filters['category'] = in_array($slug, $valid, true) ? $slug : null;
        }

        // Strip generic clothing words from search_txt — no product names contain them
        if (!empty($filters['search_txt'])) {
            $genericWords = ['clothes', 'clothing', 'apparel', 'wear', 'wearable', 'garment', 'garments', 'outfit', 'outfits', 'attire', 'stuff', 'things', 'items'];
            $words = explode(' ', $filters['search_txt']);
            $words = array_filter($words, fn($w) => !in_array(strtolower(trim($w)), $genericWords));
            $cleaned = trim(implode(' ', $words));
            $filters['search_txt'] = $cleaned ?: null;
        }

        $normalizeNumber = function ($val) {
            if (is_null($val) || $val === '') return null;
            if (is_numeric($val)) return (float) $val;
            $str = preg_replace('/[^0-9.\-]/', '', (string) $val);
            return $str === '' ? null : (float) $str;
        };

        // Handle human text like "under 300", "below $300", "between 100 and 300"
        if (!empty($filters['price'])) {
            $p = strtolower((string) $filters['price']);
            if (preg_match('/between\s*(\d+\.?\d*)\s*(?:and|-)\s*(\d+\.?\d*)/i', $p, $m)) {
                $filters['price_min'] = $normalizeNumber($m[1]);
                $filters['price_max'] = $normalizeNumber($m[2]);
                unset($filters['price']);
            } elseif (preg_match('/(?:under|below|<)\s*(\d+\.?\d*)/i', $p, $m)) {
                $filters['price_max'] = $normalizeNumber($m[1]);
                unset($filters['price']);
            } elseif (preg_match('/(?:over|above|>)\s*(\d+\.?\d*)/i', $p, $m)) {
                $filters['price_min'] = $normalizeNumber($m[1]);
                unset($filters['price']);
            } else {
                $n = $normalizeNumber($p);
                if (!is_null($n)) {
                    $filters['price'] = $n;
                } else {
                    unset($filters['price']);
                }
            }
        }

        foreach (['price_min', 'price_max'] as $key) {
            if (!empty($filters[$key])) {
                $filters[$key] = $normalizeNumber($filters[$key]);
            }
        }

        $sortAliases = ['newest' => 'latest', 'price_asc' => 'price_low', 'price_desc' => 'price_high'];
        if (!empty($filters['sort_by'])) {
            $filters['sort_by'] = $sortAliases[$filters['sort_by']] ?? $filters['sort_by'];
        }

        return array_filter($filters, static fn ($value) => $value !== null && $value !== '');
    }
}
