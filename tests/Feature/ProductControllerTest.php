<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\TestCase;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create necessary roles first
        $superAdminRole = Role::create(['name' => 'super_admin', 'guard_name' => 'api']);
        $productManagerRole = Role::create(['name' => 'product_manager', 'guard_name' => 'api']);
        
        // Create permissions
        Permission::create(['name' => 'view_products', 'guard_name' => 'api']);
        Permission::create(['name' => 'create_products', 'guard_name' => 'api']);
        Permission::create(['name' => 'edit_products', 'guard_name' => 'api']);
        Permission::create(['name' => 'delete_products', 'guard_name' => 'api']);
        
        // Assign permissions to roles
        $superAdminRole->givePermissionTo(['view_products', 'create_products', 'edit_products', 'delete_products']);
        $productManagerRole->givePermissionTo(['view_products', 'create_products', 'edit_products']);

        // Create users and assign roles
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin', 'api');

        // Create a user with the product_manager role
        $this->user = User::factory()->create();
        $this->user->assignRole('product_manager', 'api');
        
        // Set up Sanctum authentication
        Sanctum::actingAs($this->admin);
    }

    public function test_can_create_product_with_valid_data(): void
    {
        $category = Category::factory()->create();

        $productData = [
            'name' => $this->faker->unique()->words(3, true),
            'price' => 99.99,
            'stock' => 100,
            'status' => 'disponible',
            'category_id' => $category->id
        ];

        $response = $this->postJson('/api/v1/admin/products', $productData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'price',
                    'stock',
                    'status',
                    'category_id'
                ]
            ])
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'name' => $productData['name'],
                    'price' => $productData['price'],
                    'stock' => $productData['stock'],
                    'status' => $productData['status'],
                    'category_id' => $productData['category_id'],
                ]
            ]);

        $this->assertDatabaseHas('products', [
            'name' => $productData['name'],
            'slug' => Str::of($productData['name'])->slug('-'),
        ]);

    
    }

    public function test_cannot_create_product_without_required_fields(): void
    {
        $response = $this->postJson('/api/v1/admin/products', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price', 'stock']);
    }

    public function test_cannot_create_product_with_duplicate_name(): void
    {
        $existingProduct = Product::factory()->create();

        $response = $this->postJson('/api/v1/admin/products', [
            'name' => $existingProduct->name,
            'price' => 99.99,
            'stock' => 100,
            'status' => 'disponible',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_cannot_create_product_with_duplicate_slug(): void
    {
        $existingProduct = Product::factory()->create();

        $response = $this->postJson('/api/v1/admin/products', [
            'name' => 'New Product Name',
            'slug' => $existingProduct->slug,
            'price' => 99.99,
            'stock' => 100,
            'status' => 'disponible',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_generates_slug_automatically_if_not_provided(): void
    {
        $category = Category::factory()->create();

        $productData = [
            'name' => 'Test Product Name',
            'price' => 99.99,
            'stock' => 100,
            'status' => 'disponible',
            'category_id' => $category->id
        ];

        $response = $this->postJson('/api/v1/admin/products', $productData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('products', [
            'name' => 'Test Product Name',
            'slug' => 'test-product-name',
        ]);
    }

    public function test_validates_price_as_decimal(): void
    {
        $response = $this->postJson('/api/v1/admin/products', [
            'name' => 'Test Product',
            'price' => 'not-a-decimal',
            'stock' => 100,
            'status' => 'disponible',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    public function test_validates_stock_as_positive_integer(): void
    {
        $response = $this->postJson('/api/v1/admin/products', [
            'name' => 'Test Product',
            'price' => 99.99,
            'stock' => -10,
            'status' => 'disponible',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['stock']);
    }

    public function test_validates_status_as_valid_option(): void
    {
        $response = $this->postJson('/api/v1/admin/products', [
            'name' => 'Test Product',
            'price' => 99.99,
            'stock' => 100,
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_validates_category_exists(): void
    {
        $response = $this->postJson('/api/v1/admin/products', [
            'name' => 'Test Product',
            'price' => 99.99,
            'stock' => 100,
            'status' => 'disponible',
            'category_id' => 9999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_can_get_all_products()
    {
        $products = Product::factory(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/admin/products');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_product()
    {
        $category = Category::factory()->create();

        $productData = [
            'name' => 'Test Product',
            'price' => 99.99,
            'stock' => 10,
            'status' => 'disponible',
            'category_id' => $category->id
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/admin/products', $productData);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'name' => 'Test Product',
                    'slug' => 'test-product',
                ]
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'slug' => 'test-product',
        ]);
    }

    public function test_validates_required_fields()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/admin/products', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price', 'stock']);
    }

    public function test_validates_unique_name()
    {
        $product = Product::factory()->create(['name' => 'Existing Product']);

        $category = Category::factory()->create();
        $productData = [
            'name' => 'Existing Product',
            'price' => 99.99,
            'stock' => 10,
            'status' => 'disponible',
            'category_id' => $category->id
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/admin/products', $productData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_show_product()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/admin/products/' . $product->id);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name
                ]
            ]);
    }

    public function test_can_update_product()
    {
        $product = Product::factory()->create();

        $updatedData = [
            'name' => 'Updated Product Name',
            'price' => 149.99
        ];

        $response = Sanctum::actingAs($this->user,'api')
            ->putJson('/api/v1/admin/products/' . $product->id, $updatedData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $product->id,
                    'name' => 'Updated Product Name',
                    'price' => 149.99
                ]
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
            'price' => 149.99
        ]);
    }

    public function test_can_delete_product()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson('/api/v1/admin/products/' . $product->id);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Product deleted successfully'
            ]);

        $this->assertSoftDeleted('products', [
            'id' => $product->id
        ]);
    }
}
