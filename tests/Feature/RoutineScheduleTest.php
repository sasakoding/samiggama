<?php

namespace Tests\Feature;

use App\Models\RoutineSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RoutineScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Admin Vihara',
            'role' => 'Super Admin',
            'status' => 'aktif',
        ]);
    }

    public function test_admin_can_view_routine_schedules_table(): void
    {
        $sch = RoutineSchedule::create([
            'event_date' => '2026-08-30',
            'activity_name' => 'Puja Bakti Umum Minggu Pagi',
            'leader_1' => 'Upasaka Tanoto',
            'leader_2' => 'Upasika Lenny',
            'speaker' => 'Bhikkhu Subhamitto Mahāthera',
            'topic' => 'Mengikis Dosa Melalui Kesabaran (Khanti)',
            'time_range' => '08:30 - 10:30 WIB',
            'status' => 'aktif',
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin::jadwal-rutin')
            ->assertSee('Puja Bakti Umum Minggu Pagi')
            ->assertSee('Upasaka Tanoto')
            ->assertSee('Upasika Lenny')
            ->assertSee('Bhikkhu Subhamitto Mahāthera')
            ->assertSee('Mengikis Dosa Melalui Kesabaran (Khanti)')
            ->assertStatus(200);
    }

    public function test_admin_can_create_new_routine_schedule(): void
    {
        Livewire::actingAs($this->admin)
            ->test('admin::jadwal-rutin')
            ->call('openCreateModal')
            ->set('formDate', '2026-09-06')
            ->set('formActivityName', 'Puja Bakti Umum Minggu Pagi')
            ->set('formLeader1', 'Upasaka Hendra Wijaya')
            ->set('formLeader2', 'Upasika Ratna Dewi')
            ->set('formSpeaker', 'Pandita Dr. S. Widyadharma')
            ->set('formTopic', 'Penerapan Panca Sila dalam Era Digital')
            ->set('formTimeRange', '08:30 - 10:30 WIB')
            ->set('formStatus', 'aktif')
            ->call('saveSchedule')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('routine_schedules', [
            'activity_name' => 'Puja Bakti Umum Minggu Pagi',
            'leader_1' => 'Upasaka Hendra Wijaya',
            'leader_2' => 'Upasika Ratna Dewi',
            'speaker' => 'Pandita Dr. S. Widyadharma',
            'topic' => 'Penerapan Panca Sila dalam Era Digital',
        ]);
    }

    public function test_admin_can_edit_routine_schedule(): void
    {
        $sch = RoutineSchedule::create([
            'event_date' => '2026-08-30',
            'activity_name' => 'Puja Bakti Awal',
            'leader_1' => 'Pemimpin A',
            'speaker' => 'Pembicara A',
            'topic' => 'Topik A',
            'status' => 'aktif',
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin::jadwal-rutin')
            ->call('openEditModal', $sch->id)
            ->set('formTopic', 'Topik Baru Telah Diperbarui')
            ->call('saveSchedule')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('routine_schedules', [
            'id' => $sch->id,
            'topic' => 'Topik Baru Telah Diperbarui',
        ]);
    }

    public function test_admin_can_delete_routine_schedule(): void
    {
        $sch = RoutineSchedule::create([
            'event_date' => '2026-08-30',
            'activity_name' => 'Puja Bakti Hapus',
            'leader_1' => 'Pemimpin X',
            'speaker' => 'Pembicara X',
            'topic' => 'Topik X',
            'status' => 'aktif',
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin::jadwal-rutin')
            ->call('deleteSchedule', $sch->id);

        $this->assertDatabaseMissing('routine_schedules', ['id' => $sch->id]);
    }

    public function test_public_kegiatan_page_displays_routine_schedules(): void
    {
        RoutineSchedule::create([
            'event_date' => '2026-08-30',
            'activity_name' => 'Puja Bakti Umum & Dhammadesana',
            'leader_1' => 'Upasaka Tanoto',
            'leader_2' => 'Upasika Lenny',
            'speaker' => 'Bhikkhu Subhamitto Mahāthera',
            'topic' => 'Praktek Keheningan Batin',
            'status' => 'aktif',
        ]);

        $response = $this->get('/kegiatan');
        $response->assertStatus(200);
        $response->assertSee('Puja Bakti Umum');
        $response->assertSee('Upasaka Tanoto');
        $response->assertSee('Bhikkhu Subhamitto Mahāthera');
        $response->assertSee('Praktek Keheningan Batin');
    }

    public function test_public_kegiatan_page_displays_special_events_card_grid(): void
    {
        \App\Models\Schedule::create([
            'title' => 'Perayaan Hari Raya Kathina Dāna 2570 TB',
            'slug' => 'perayaan-kathina-dana-2570',
            'category' => 'Hari Raya',
            'schedule_type' => 'Tahunan',
            'event_date' => '2026-10-25',
            'start_time' => '08:30:00',
            'end_time' => '13:00:00',
            'location' => 'Dhammasala Utama Lt. 2',
            'leader' => 'Bhikkhu Sangha',
            'description' => 'Persembahan jubah kathina dan perlengkapan sarana bagi Bhikkhu Sangha.',
            'status' => 'aktif',
        ]);

        $response = $this->get('/kegiatan');
        $response->assertStatus(200);
        $response->assertSee('Perayaan Hari Raya Kathina Dāna 2570 TB');
        $response->assertSee('Hari Raya');
        $response->assertSee('Dhammasala Utama Lt. 2');
        $response->assertSee('Lihat Rincian & Poster Lengkap', false);
    }

    public function test_public_kegiatan_page_renders_buddhist_calendar(): void
    {
        $response = $this->get('/kegiatan');
        $response->assertStatus(200);
        $response->assertSee('Kalender Hari Besar', false);
        $response->assertSee('Hari Raya Buddhis', false);
        $response->assertSee('Hari Ini', false);
    }

    public function test_admin_can_customize_routine_schedule_header_settings(): void
    {
        Livewire::actingAs($this->admin)
            ->test('admin::jadwal-rutin')
            ->call('openHeaderModal')
            ->set('headerBadge', 'Penugasan Khusus Sangha & Pandita')
            ->set('headerTitle', 'Jadwal Kebaktian Utama Dhammasala')
            ->set('headerSubtitle', 'Keterangan waktu dan penceramah kebaktian mingguan.')
            ->call('saveHeaderSettings')
            ->assertHasNoErrors()
            ->assertSet('headerModalOpen', false);

        $this->assertEquals('Penugasan Khusus Sangha & Pandita', \App\Models\Setting::get('routine_schedule_badge'));
        $this->assertEquals('Jadwal Kebaktian Utama Dhammasala', \App\Models\Setting::get('routine_schedule_title'));

        $response = $this->get('/kegiatan');
        $response->assertStatus(200);
        $response->assertSee('Penugasan Khusus Sangha & Pandita');
        $response->assertSee('Jadwal Kebaktian Utama Dhammasala');
    }
}




