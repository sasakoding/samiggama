<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AgendaActivitiesTest extends TestCase
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

    public function test_admin_can_add_agenda_with_dated_activities(): void
    {
        $activities = [
            [
                'date' => '2026-05-10',
                'time' => '08:30 - 10:30 WIB',
                'activity' => 'Kebaktian SPD Minggu I',
                'leader_1' => 'Upasaka Tan',
                'leader_2' => 'Upasika Lin',
                'speaker' => 'Bhikkhu Uttamo Mahathera',
                'topic' => 'Menemukan Kedamaian Batin',
            ],
            [
                'date' => '2026-05-17',
                'time' => '08:30 - 10:30 WIB',
                'activity' => 'Kebaktian SPD Minggu II',
                'leader_1' => 'Sdr. Hendra',
                'leader_2' => '',
                'speaker' => 'Bhikkhu Sri Pannavaro',
                'topic' => 'Cinta Kasih Universal',
            ],
        ];

        Livewire::actingAs($this->admin)
            ->test('admin::agenda')
            ->call('openCreateModal')
            ->set('formTitle', 'Sebulan Penghayatan Dhamma 2570 TB')
            ->set('formCategory', 'Hari Raya Buddhis')
            ->set('formEventDate', '2026-05-01')
            ->set('formStartTime', '08:30')
            ->set('formLocation', 'Dhammasala Utama')
            ->set('formActivities', $activities)
            ->call('saveSchedule');

        $schedule = Schedule::where('title', 'Sebulan Penghayatan Dhamma 2570 TB')->first();
        $this->assertNotNull($schedule);
        $this->assertIsArray($schedule->activities);
        $this->assertCount(2, $schedule->activities);
        $this->assertEquals('Bhikkhu Uttamo Mahathera', $schedule->activities[0]['speaker']);
        $this->assertEquals('Kebaktian SPD Minggu II', $schedule->activities[1]['activity']);
    }

    public function test_admin_can_edit_agenda_activities(): void
    {
        $schedule = Schedule::create([
            'title' => 'Rangkaian Acara Asadha 2570 TB',
            'slug' => 'rangkaian-acara-asadha-2570-tb',
            'category' => 'Hari Raya Buddhis',
            'event_date' => '2026-07-20',
            'start_time' => '08:30:00',
            'location' => 'Dhammasala Utama',
            'status' => 'aktif',
            'activities' => [
                [
                    'date' => '2026-07-20',
                    'time' => '08:30 - 10:30 WIB',
                    'activity' => 'Puja Bakti Asadha',
                    'leader_1' => 'Petugas A',
                    'leader_2' => '',
                    'speaker' => 'Bhante A',
                    'topic' => 'Dhammacakkappavattana Sutta',
                ],
            ],
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin::agenda')
            ->call('openEditModal', $schedule->id)
            ->call('addActivity')
            ->set('formActivities.1.date', '2026-07-21')
            ->set('formActivities.1.time', '18:30 - 20:30 WIB')
            ->set('formActivities.1.activity', 'Meditasi Bersama Asadha')
            ->set('formActivities.1.speaker', 'Bhante B')
            ->set('formActivities.1.topic', 'Praktek Samatha & Vipassana')
            ->call('saveSchedule');

        $schedule->refresh();
        $this->assertCount(2, $schedule->activities);
        $this->assertEquals('Meditasi Bersama Asadha', $schedule->activities[1]['activity']);
    }

    public function test_public_kegiatan_page_loads_with_default_kegiatan_tab(): void
    {
        $response = $this->get('/kegiatan');
        $response->assertStatus(200);
        $response->assertSee('Agenda & Jadwal Kegiatan', false);
        $response->assertSee('Kalender Hari Besar & Uposatha', false);
    }
}
