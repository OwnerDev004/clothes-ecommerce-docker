<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Collection as ProductCollection;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCollectionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_collection_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/admin/collections')->assertUnauthorized();
        $this->postJson('/api/v1/admin/collections', [])->assertUnauthorized();
    }

    public function test_admin_can_list_collections_with_filters(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'admin');

        $categoryA = $this->createCategory('Women');
        $categoryB = $this->createCategory('Men');

        ProductCollection::create([
            'category_id' => $categoryA->id,
            'name' => 'Summer Drop',
            'slug' => 'summer-drop',
            'desc' => 'Summer catalog',
            'sort_order' => 1,
        ]);

        ProductCollection::create([
            'category_id' => $categoryB->id,
            'name' => 'Winter Drop',
            'slug' => 'winter-drop',
            'desc' => 'Winter catalog',
            'sort_order' => 2,
        ]);

        $response = $this->getJson("/api/v1/admin/collections?search_txt=summer&category_id={$categoryA->id}&per_page=10");

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Summer Drop')
            ->assertJsonPath('data.0.category_id', $categoryA->id);
    }

    public function test_admin_can_view_collection_detail(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'admin');

        $category = $this->createCategory('Shoes');
        $collection = ProductCollection::create([
            'category_id' => $category->id,
            'name' => 'Street',
            'slug' => 'street',
            'desc' => 'Street style',
            'sort_order' => 3,
        ]);

        $product = $this->createProduct($category->id, 'Runner');
        $collection->products()->sync([$product->id]);

        $response = $this->getJson("/api/v1/admin/collections/{$collection->id}");

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.id', $collection->id)
            ->assertJsonPath('data.category.id', $category->id)
            ->assertJsonPath('data.products.0.id', $product->id);
    }

    public function test_admin_can_create_collection_and_attach_products(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'admin');

        $category = $this->createCategory('Kids');
        $productA = $this->createProduct($category->id, 'Kid Tee');
        $productB = $this->createProduct($category->id, 'Kid Pants');

        $payload = [
            'category_id' => $category->id,
            'name' => 'Back To School',
            'desc' => 'Seasonal picks',
            'sort_order' => 7,
            'product_ids' => [$productA->id, $productB->id],
        ];

        $response = $this->postJson('/api/v1/admin/collections', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.name', 'Back To School')
            ->assertJsonPath('data.category.id', $category->id)
            ->assertJsonPath('data.products_count', 2);

        $collectionId = (int) $response->json('data.id');
        $this->assertDatabaseHas('collections', [
            'id' => $collectionId,
            'category_id' => $category->id,
            'name' => 'Back To School',
            'sort_order' => 7,
        ]);
        $this->assertDatabaseHas('collection_product', [
            'collection_id' => $collectionId,
            'product_id' => $productA->id,
        ]);
        $this->assertDatabaseHas('collection_product', [
            'collection_id' => $collectionId,
            'product_id' => $productB->id,
        ]);
    }

    public function test_admin_can_update_collection_and_sync_products(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'admin');

        $oldCategory = $this->createCategory('Old Cat');
        $newCategory = $this->createCategory('New Cat');

        $oldProduct = $this->createProduct($oldCategory->id, 'Old Product');
        $newProduct = $this->createProduct($newCategory->id, 'New Product');

        $collection = ProductCollection::create([
            'category_id' => $oldCategory->id,
            'name' => 'Legacy Collection',
            'slug' => 'legacy-collection',
            'desc' => 'Legacy',
            'sort_order' => 0,
        ]);
        $collection->products()->sync([$oldProduct->id]);

        $payload = [
            'category_id' => $newCategory->id,
            'name' => 'Modern Collection',
            'desc' => 'Updated',
            'sort_order' => 9,
            'product_ids' => [$newProduct->id],
        ];

        $this->putJson("/api/v1/admin/collections/{$collection->id}", $payload)
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.name', 'Modern Collection')
            ->assertJsonPath('data.category.id', $newCategory->id)
            ->assertJsonPath('data.products_count', 1);

        $this->assertDatabaseHas('collections', [
            'id' => $collection->id,
            'category_id' => $newCategory->id,
            'name' => 'Modern Collection',
            'desc' => 'Updated',
            'sort_order' => 9,
        ]);
        $this->assertDatabaseHas('collection_product', [
            'collection_id' => $collection->id,
            'product_id' => $newProduct->id,
        ]);
        $this->assertDatabaseMissing('collection_product', [
            'collection_id' => $collection->id,
            'product_id' => $oldProduct->id,
        ]);
    }

    public function test_admin_can_delete_collection(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'admin');

        $category = $this->createCategory('Delete Cat');
        $collection = ProductCollection::create([
            'category_id' => $category->id,
            'name' => 'Delete Me',
            'slug' => 'delete-me',
            'desc' => 'To be deleted',
            'sort_order' => 0,
        ]);

        $this->deleteJson("/api/v1/admin/collections/{$collection->id}")
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('message', 'Collection deleted');

        $this->assertDatabaseMissing('collections', ['id' => $collection->id]);
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
        ]);
    }

    private function createCategory(string $name): Category
    {
        $slug = strtolower(str_replace(' ', '-', $name)) . '-' . uniqid();

        return Category::create([
            'name' => $name . ' ' . uniqid(),
            'des' => 'Category description',
            'slug' => $slug,
        ]);
    }

    private function createProduct(int $categoryId, string $name): Product
    {
        $suffix = uniqid();

        return Product::create([
            'sku' => 'SKU-' . strtoupper($suffix),
            'slug' => strtolower(str_replace(' ', '-', $name)) . '-' . $suffix,
            'name' => $name . ' ' . $suffix,
            'desc' => 'Product description',
            'price' => 20,
            'category_id' => $categoryId,
        ]);
    }
}
