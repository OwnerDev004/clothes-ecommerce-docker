<?php
namespace App\Jobs;

use App\Repositories\ProductRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use LucianoTonet\GroqPHP\Groq;
use LucianoTonet\GroqPHP\GroqException;
use Illuminate\Support\Facades\Cache;

class PopulateProductSearchAiCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $message;
    protected $filtersKey;
    protected $productsKey;
    protected $ttl;

    public function __construct(string $message, string $filtersKey, string $productsKey, int $ttl = 3600)
    {
        $this->message = $message;
        $this->filtersKey = $filtersKey;
        $this->productsKey = $productsKey;
        $this->ttl = $ttl;
    }

    public function handle()
    {
        $groq = new Groq(getenv('GROQ_API_KEY'));
        try {
            $response = $groq->chat()->completions()->create([
                'model' => 'llama-3.3-70b-versatile',
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
                                    } ' . $this->message
                    ],
                ],
            ]);

            $rawContent = $response['choices'][0]['message']['content'] ?? '';
            $jsonString = preg_replace('/^```(?:json)?\s*|\s*```$/s', '', $rawContent);
            $filters = json_decode($jsonString, true);

            if (!is_array($filters)) {
                \Log::info($filters);
                preg_match('/\{.*\}/s', $rawContent, $matches);
                $filters = $matches ? json_decode($matches[0], true) : [];
            }

            if (is_array($filters) && !empty($filters)) {
                $filters = $this->sanitizeFilters($filters);
                Cache::put($this->filtersKey, $filters, $this->ttl);

                $productRepo = app(ProductRepository::class);
                $products = $productRepo->getAll(array_merge(['per_page' => 12], $filters));

                // Cache whatever the repository returned (may be a paginator/collection)
                Cache::put($this->productsKey, $products, $this->ttl);
            }

        } catch (\Throwable $e) {
            // Swallow errors: the request that triggered this job already returned a fallback.
            \Log::warning('AI product search cache population failed', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Normalize filter values coming from AI output into formats expected by repository.
     */
    private function sanitizeFilters(array $filters): array
    {
        $allowedKeys = [
            'search_txt', 'category', 'sub_category', 'price', 'price_min',
            'price_max', 'min_price', 'max_price', 'color', 'size', 'brand', 'collection', 'dress_style',
            'sort_by', 'per_page',
        ];
        $filters = array_intersect_key($filters, array_flip($allowedKeys));

        foreach (['search_txt', 'category', 'sub_category', 'color', 'size', 'brand', 'collection', 'dress_style'] as $key) {
            if (array_key_exists($key, $filters) && !is_null($filters[$key])) {
                $filters[$key] = trim((string) $filters[$key]) ?: null;
            }
        }

        foreach (['min_price' => 'price_min', 'max_price' => 'price_max'] as $source => $target) {
            if (array_key_exists($source, $filters) && !array_key_exists($target, $filters)) {
                $filters[$target] = $filters[$source];
            }
            unset($filters[$source]);
        }

        // Normalize price fields
        $normalizeNumber = function ($val) {
            if (is_null($val) || $val === '') {
                return null;
            }
            if (is_numeric($val)) {
                return (float) $val;
            }
            // Remove currency symbols and commas
            $str = preg_replace('/[^0-9.\-]/', '', (string) $val);
            if ($str === '') {
                return null;
            }
            return (float) $str;
        };

        // Handle human text like "under 300", "below $300", "between 100 and 300"
        if (!empty($filters['price'])) {
            $p = strtolower((string) $filters['price']);
            if (preg_match('/between\s*(\d+[,.]?\d*)\s*(and|-)\s*(\d+[,.]?\d*)/i', $p, $m)) {
                $filters['price_min'] = $normalizeNumber($m[1]);
                $filters['price_max'] = $normalizeNumber($m[3]);
                unset($filters['price']);
            } elseif (preg_match('/(?:under|below|<)\s*(\d+[,.]?\d*)/i', $p, $m)) {
                $filters['price_max'] = $normalizeNumber($m[1]);
                unset($filters['price']);
            } elseif (preg_match('/(?:over|above|>)\s*(\d+[,.]?\d*)/i', $p, $m)) {
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

        if (!empty($filters['price_min'])) {
            $filters['price_min'] = $normalizeNumber($filters['price_min']);
        }
        if (!empty($filters['price_max'])) {
            $filters['price_max'] = $normalizeNumber($filters['price_max']);
        }

        $sortAliases = [
            'newest' => 'latest',
            'price_asc' => 'price_low',
            'price_desc' => 'price_high',
        ];
        if (!empty($filters['sort_by'])) {
            $filters['sort_by'] = $sortAliases[$filters['sort_by']] ?? $filters['sort_by'];
        }

        return array_filter($filters, static fn ($value) => $value !== null && $value !== '');
    }
}
