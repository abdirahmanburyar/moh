<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PackingList;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\PackingListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class PackingListApprovalTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $packingList;
    protected $purchaseOrder;
    protected $supplier;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user with approval permissions
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        
        // Assign approval permissions to user
        $this->user->givePermissionTo(['purchase_order_review', 'purchase_order_approve', 'purchase_order_reject']);
        
        // Create supplier
        $this->supplier = Supplier::factory()->create([
            'name' => 'Test Supplier',
            'email' => 'supplier@example.com',
            'phone' => '1234567890',
        ]);
        
        // Create product
        $this->product = Product::factory()->create([
            'name' => 'Test Product',
            'category_id' => 1,
            'dosage_id' => 1,
        ]);
        
        // Create purchase order
        $this->purchaseOrder = PurchaseOrder::factory()->create([
            'supplier_id' => $this->supplier->id,
            'status' => 'pending',
            'po_date' => now(),
        ]);
        
        // Create packing list
        $this->packingList = PackingList::factory()->create([
            'purchase_order_id' => $this->purchaseOrder->id,
            'status' => 'pending',
            'packing_list_number' => 'PL-001',
            'ref_no' => 'REF-001',
            'pk_date' => now(),
        ]);
        
        // Create packing list items
        PackingListItem::factory()->create([
            'packing_list_id' => $this->packingList->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'unit_cost' => 100.00,
            'total_cost' => 1000.00,
            'batch_number' => 'BATCH-001',
            'expire_date' => now()->addMonths(6),
            'barcode' => '123456789',
            'warehouse_id' => 1,
            'location' => 'Location A',
        ]);
    }

    /** @test */
    public function user_can_review_packing_list()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('supplies.reviewPK'), [
            'id' => $this->packingList->id,
            'status' => 'reviewed'
        ]);

        $response->assertStatus(200);
        
        // Assert packing list status is updated
        $this->assertDatabaseHas('packing_lists', [
            'id' => $this->packingList->id,
            'status' => 'reviewed'
        ]);
        
        // Assert reviewed_at and reviewed_by are set
        $updatedPackingList = PackingList::find($this->packingList->id);
        $this->assertNotNull($updatedPackingList->reviewed_at);
        $this->assertEquals($this->user->id, $updatedPackingList->reviewed_by);
    }

    /** @test */
    public function user_can_approve_packing_list_after_review()
    {
        $this->actingAs($this->user);
        
        // First review the packing list
        $this->packingList->update([
            'status' => 'reviewed',
            'reviewed_at' => now(),
            'reviewed_by' => $this->user->id
        ]);

        $response = $this->post(route('supplies.approvePK'), [
            'id' => $this->packingList->id,
            'status' => 'approved',
            'items' => $this->packingList->items->toArray()
        ]);

        $response->assertStatus(200);
        
        // Assert packing list status is updated
        $this->assertDatabaseHas('packing_lists', [
            'id' => $this->packingList->id,
            'status' => 'approved'
        ]);
        
        // Assert approved_at and approved_by are set
        $updatedPackingList = PackingList::find($this->packingList->id);
        $this->assertNotNull($updatedPackingList->approved_at);
        $this->assertEquals($this->user->id, $updatedPackingList->approved_by);
    }

    /** @test */
    public function user_can_reject_packing_list_after_review()
    {
        $this->actingAs($this->user);
        
        // First review the packing list
        $this->packingList->update([
            'status' => 'reviewed',
            'reviewed_at' => now(),
            'reviewed_by' => $this->user->id
        ]);

        $response = $this->post(route('supplies.rejectPK'), [
            'id' => $this->packingList->id,
            'status' => 'rejected',
            'items' => $this->packingList->items->toArray()
        ]);

        $response->assertStatus(200);
        
        // Assert packing list status is updated
        $this->assertDatabaseHas('packing_lists', [
            'id' => $this->packingList->id,
            'status' => 'rejected'
        ]);
        
        // Assert rejected_at and rejected_by are set
        $updatedPackingList = PackingList::find($this->packingList->id);
        $this->assertNotNull($updatedPackingList->rejected_at);
        $this->assertEquals($this->user->id, $updatedPackingList->rejected_by);
    }

    /** @test */
    public function user_cannot_approve_packing_list_without_review()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('supplies.approvePK'), [
            'id' => $this->packingList->id,
            'status' => 'approved',
            'items' => $this->packingList->items->toArray()
        ]);

        $response->assertStatus(422);
        
        // Assert packing list status remains unchanged
        $this->assertDatabaseHas('packing_lists', [
            'id' => $this->packingList->id,
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function user_cannot_reject_packing_list_without_review()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('supplies.rejectPK'), [
            'id' => $this->packingList->id,
            'status' => 'rejected',
            'items' => $this->packingList->items->toArray()
        ]);

        $response->assertStatus(422);
        
        // Assert packing list status remains unchanged
        $this->assertDatabaseHas('packing_lists', [
            'id' => $this->packingList->id,
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function user_cannot_review_already_reviewed_packing_list()
    {
        $this->actingAs($this->user);
        
        // Set packing list as already reviewed
        $this->packingList->update([
            'status' => 'reviewed',
            'reviewed_at' => now(),
            'reviewed_by' => $this->user->id
        ]);

        $response = $this->post(route('supplies.reviewPK'), [
            'id' => $this->packingList->id,
            'status' => 'reviewed'
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function user_cannot_approve_already_approved_packing_list()
    {
        $this->actingAs($this->user);
        
        // Set packing list as already approved
        $this->packingList->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $this->user->id,
            'approved_at' => now(),
            'approved_by' => $this->user->id
        ]);

        $response = $this->post(route('supplies.approvePK'), [
            'id' => $this->packingList->id,
            'status' => 'approved',
            'items' => $this->packingList->items->toArray()
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function user_cannot_reject_already_rejected_packing_list()
    {
        $this->actingAs($this->user);
        
        // Set packing list as already rejected
        $this->packingList->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => $this->user->id,
            'rejected_at' => now(),
            'rejected_by' => $this->user->id
        ]);

        $response = $this->post(route('supplies.rejectPK'), [
            'id' => $this->packingList->id,
            'status' => 'rejected',
            'items' => $this->packingList->items->toArray()
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function unauthorized_user_cannot_review_packing_list()
    {
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser);

        $response = $this->post(route('supplies.reviewPK'), [
            'id' => $this->packingList->id,
            'status' => 'reviewed'
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthorized_user_cannot_approve_packing_list()
    {
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser);

        $response = $this->post(route('supplies.approvePK'), [
            'id' => $this->packingList->id,
            'status' => 'approved',
            'items' => $this->packingList->items->toArray()
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthorized_user_cannot_reject_packing_list()
    {
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser);

        $response = $this->post(route('supplies.rejectPK'), [
            'id' => $this->packingList->id,
            'status' => 'rejected',
            'items' => $this->packingList->items->toArray()
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function review_action_updates_timestamps_correctly()
    {
        $this->actingAs($this->user);

        $beforeTime = now();
        
        $response = $this->post(route('supplies.reviewPK'), [
            'id' => $this->packingList->id,
            'status' => 'reviewed'
        ]);

        $response->assertStatus(200);
        
        $afterTime = now();
        
        $updatedPackingList = PackingList::find($this->packingList->id);
        
        $this->assertGreaterThanOrEqual($beforeTime, $updatedPackingList->reviewed_at);
        $this->assertLessThanOrEqual($afterTime, $updatedPackingList->reviewed_at);
    }

    /** @test */
    public function approve_action_updates_timestamps_correctly()
    {
        $this->actingAs($this->user);
        
        // First review the packing list
        $this->packingList->update([
            'status' => 'reviewed',
            'reviewed_at' => now(),
            'reviewed_by' => $this->user->id
        ]);

        $beforeTime = now();
        
        $response = $this->post(route('supplies.approvePK'), [
            'id' => $this->packingList->id,
            'status' => 'approved',
            'items' => $this->packingList->items->toArray()
        ]);

        $response->assertStatus(200);
        
        $afterTime = now();
        
        $updatedPackingList = PackingList::find($this->packingList->id);
        
        $this->assertGreaterThanOrEqual($beforeTime, $updatedPackingList->approved_at);
        $this->assertLessThanOrEqual($afterTime, $updatedPackingList->approved_at);
    }

    /** @test */
    public function reject_action_updates_timestamps_correctly()
    {
        $this->actingAs($this->user);
        
        // First review the packing list
        $this->packingList->update([
            'status' => 'reviewed',
            'reviewed_at' => now(),
            'reviewed_by' => $this->user->id
        ]);

        $beforeTime = now();
        
        $response = $this->post(route('supplies.rejectPK'), [
            'id' => $this->packingList->id,
            'status' => 'rejected',
            'items' => $this->packingList->items->toArray()
        ]);

        $response->assertStatus(200);
        
        $afterTime = now();
        
        $updatedPackingList = PackingList::find($this->packingList->id);
        
        $this->assertGreaterThanOrEqual($beforeTime, $updatedPackingList->rejected_at);
        $this->assertLessThanOrEqual($afterTime, $updatedPackingList->rejected_at);
    }

    /** @test */
    public function approval_actions_require_valid_packing_list_id()
    {
        $this->actingAs($this->user);

        $invalidId = 99999;

        $response = $this->post(route('supplies.reviewPK'), [
            'id' => $invalidId,
            'status' => 'reviewed'
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function approval_actions_require_valid_status()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('supplies.reviewPK'), [
            'id' => $this->packingList->id,
            'status' => 'invalid_status'
        ]);

        $response->assertStatus(422);
    }
} 