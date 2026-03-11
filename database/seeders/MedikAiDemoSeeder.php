<?php

namespace Database\Seeders;

use App\Enums\IndustryType;
use App\Enums\OperationType;
use App\Enums\SchedulableType;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\TenantLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedikAiDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // ---------------------------
            // TENANT
            // ---------------------------
            $tenant = Tenant::updateOrCreate(
                ['jid' => '5217711986426@s.whatsapp.net'],
                [
                    'name'          => 'MedikAI',
                    'is_active'     => true,
                    'espocrm_id'    => '693092558ef46764a',
                    'industry_type' => IndustryType::Healthcare->value,
                    'operation_type' => OperationType::SingleStaff->value,
                    'description'   => "Pediatra en Pachuca, Hidalgo. Horario: lun–vie 9:00 a.m.–6:00 p.m.; sáb 9:00 a.m.–2:00 p.m.\n\nEn el consultorio se ofrecen dos tipos de citas.\nLa cita de primera vez tiene una duración aproximada de 45 minutos y está destinada a realizar la primera valoración del menor.\nPosteriormente, se pueden agendar citas subsecuentes, con una duración de 30 minutos, enfocadas en el seguimiento posterior a la consulta inicial.",
                    'settings'      => [
                        'assistantName' => 'Sofía',
                        'urlReviewPlatform' => null,
                        'calCom' => [
                            'user' => 'iagent',
                            'token' => env('MEDIKAI_DEMO_CALCOM_TOKEN', 'cal_live_...'),
                        ],
                        'features' => [
                            'surveysEnabled' => false,
                            'billingEnabled' => false,
                        ],
                    ],
                ]
            );

            $tenantLocation = TenantLocation::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'name' => 'Matriz',
                ],
                [
                    'address' => 'Boulevard Nuevo Hidalgo, Vía Madeiras 207, local 13. Pachuca de Soto, Hidalgo. México C.P. 42083',
                    'time_zone' => 'America/Mexico_City',
                    'url_map' => 'https://maps.app.goo.gl/tQtCJ6BHx1WBFjwJ6',
                    'is_primary' => true,
                    'is_active' => true,
                    'settings' => [],
                ]
            );

            // ---------------------------
            // SERVICE CATEGORY
            // ---------------------------
            $category = ServiceCategory::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'name'      => 'Consultas',
                ],
                []
            );

            // ---------------------------
            // SERVICES
            // ---------------------------
            $serviceFirst = Service::updateOrCreate(
                [
                    'tenant_id'         => $tenant->id,
                    'name'              => 'Consulta Primera Vez',
                    'duration_minutes'  => 45,
                ],
                [
                    'espocrm_id'    => '693093e4261603c9b',
                    'description'   => 'Primera valoración integral del menor, historia clínica completa, exploración física detallada y plan de tratamiento personalizado.',
                    'price'         => '900.00',
                    'category_id'   => $category->id,
                    'is_active'     => true,
                    'settings'      => [],
                ]
            );

            $serviceFollow = Service::updateOrCreate(
                [
                    'tenant_id'         => $tenant->id,
                    'name'              => 'Consulta Subsecuente',
                    'duration_minutes'  => 30,
                ],
                [
                    'espocrm_id'    => '69309523411d4a033',
                    'description'   => 'Seguimiento después de la primera consulta, evaluación de evolución, ajuste de tratamientos, revisiones por edad y orientación preventiva.',
                    'price'         => '700.00',
                    'category_id'   => $category->id,
                    'is_active'     => true,
                    'settings'      => [],
                ]
            );

            // ---------------------------
            // STAFF
            // ---------------------------
            $staff = Staff::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'email'     => 'sivegm@gmail.com',
                ],
                [
                    'espocrm_id' => '693095ebddc3817e4',
                    'name'       => 'Dr. Edgar Gomez Moctezuma',
                    'role'       => 'doctor',
                    'phone'      => '7711986426',
                    'is_active'  => true,
                    'settings'   => [
                        'about'      => 'Pediatra especializado dedicado al cuidado integral de la salud infantil desde nacimiento hasta los 16 años',
                        'specialty'  => 'Pediatría',
                    ],
                ]
            );

            // ---------------------------
            // SCHEDULES (staff)
            // ---------------------------
            Schedule::query()
                ->where('tenant_id', $tenant->id)
                ->where('schedulable_type', SchedulableType::Staff->value)
                ->where('schedulable_id', $staff->id)
                ->delete();

            $schedules = [
                ['day_of_week' => 1, 'start_time' => '09:00:00', 'end_time' => '18:00:00'],
                ['day_of_week' => 2, 'start_time' => '09:00:00', 'end_time' => '18:00:00'],
                ['day_of_week' => 3, 'start_time' => '09:00:00', 'end_time' => '18:00:00'],
                ['day_of_week' => 4, 'start_time' => '09:00:00', 'end_time' => '18:00:00'],
                ['day_of_week' => 5, 'start_time' => '09:00:00', 'end_time' => '18:00:00'],
                ['day_of_week' => 6, 'start_time' => '09:00:00', 'end_time' => '14:00:00'],
            ];

            foreach ($schedules as $s) {
                Schedule::create([
                    'tenant_id' => $tenant->id,
                    'schedulable_type' => SchedulableType::Staff->value,
                    'schedulable_id' => $staff->id,
                    'tenant_location_id' => $tenantLocation->id,
                    'day_of_week' => $s['day_of_week'],
                    'start_time'  => $s['start_time'],
                    'end_time'    => $s['end_time'],
                    'is_active'   => true,
                ]);
            }

            // ---------------------------
            // STAFF_SERVICES (pivot)
            // ---------------------------
            $staff->services()->sync([
                $serviceFollow->id,
                $serviceFirst->id,
            ]);
        });
    }
}
