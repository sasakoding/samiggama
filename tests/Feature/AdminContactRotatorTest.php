<?php

namespace Tests\Feature;

use App\Models\AdminContact;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminContactRotatorTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@samaggigama.org',
            'role' => 'admin',
        ]);
    }

    public function test_admin_contacts_can_be_created_edited_and_toggled(): void
    {
        Livewire::actingAs($this->admin)
            ->test('admin::pengaturan')
            ->call('setTab', 'rotator')
            ->call('openCreateContactModal')
            ->set('contactName', 'Admin Upasaka Budi')
            ->set('contactPhone', '081234567890')
            ->set('contactRole', 'Sekretariat & Pelayanan')
            ->set('contactIsActive', true)
            ->set('contactSortOrder', 1)
            ->call('saveContact')
            ->assertHasNoErrors()
            ->assertSet('contactModalOpen', false);

        $this->assertDatabaseHas('admin_contacts', [
            'name' => 'Admin Upasaka Budi',
            'phone' => '081234567890',
            'role' => 'Sekretariat & Pelayanan',
            'is_active' => true,
        ]);

        $contact = AdminContact::first();

        // Test editing
        Livewire::actingAs($this->admin)
            ->test('admin::pengaturan')
            ->call('openEditContactModal', $contact->id)
            ->set('contactRole', 'Layanan Donasi & Umat')
            ->call('saveContact')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('admin_contacts', [
            'id' => $contact->id,
            'role' => 'Layanan Donasi & Umat',
        ]);

        // Test toggle status
        Livewire::actingAs($this->admin)
            ->test('admin::pengaturan')
            ->call('toggleContactStatus', $contact->id);

        $this->assertFalse((bool) $contact->fresh()->is_active);

        // Test delete
        Livewire::actingAs($this->admin)
            ->test('admin::pengaturan')
            ->call('deleteContact', $contact->id);

        $this->assertDatabaseMissing('admin_contacts', ['id' => $contact->id]);
    }

    public function test_rotator_returns_random_active_admin_phone(): void
    {
        AdminContact::create([
            'name' => 'Admin A',
            'phone' => '081122334455',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        AdminContact::create([
            'name' => 'Admin B',
            'phone' => '089988776655',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        AdminContact::create([
            'name' => 'Admin C Inactive',
            'phone' => '085544332211',
            'is_active' => false,
            'sort_order' => 3,
        ]);

        $phone = AdminContact::getRandomActivePhone();
        $this->assertContains($phone, ['6281122334455', '6289988776655']);
        $this->assertNotEquals('6285544332211', $phone);
    }

    public function test_rotator_fallbacks_gracefully_when_no_active_contacts(): void
    {
        Setting::set('foundation_phone', '+62 899-888-777');

        $phone = AdminContact::getRandomActivePhone();
        $this->assertEquals('62899888777', $phone);
    }
}
