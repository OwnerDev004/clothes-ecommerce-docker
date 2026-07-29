<?php

namespace Api\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_category_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/admin/categories')->assertUnauthorized();
        $this->postJson('/api/v1/admin/categories', [])->assertUnauthorized();
        $this->putJson('/api/v1/admin/categories/1', [])->assertUnauthorized();
        $this->deleteJson('/api/v1/admin/categories/1')->assertUnauthorized();
    }

}