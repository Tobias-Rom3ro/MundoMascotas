<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos
        $permissions = [
            // Usuarios
            'manage_users',
            'view_users',

            // Servicios y precios
            'manage_services',
            'view_services',
            'manage_prices',
            'view_prices',

            // Clientes y mascotas
            'manage_clients',
            'view_clients',
            'manage_pets',
            'view_pets',

            // Citas
            'manage_appointments',
            'view_appointments',

            // Servicios de clínica
            'manage_clinic_services',
            'view_clinic_services',
            'manage_medical_records',
            'view_medical_records',
            'manage_vaccinations',
            'view_vaccinations',

            // Servicios de hotel
            'manage_hotel_services',
            'view_hotel_services',
            'manage_hotel_stays',
            'view_hotel_stays',

            // Servicios de spa
            'manage_spa_services',
            'view_spa_services',

            // PQRs
            'manage_pqrs',
            'view_pqrs',
            'respond_pqrs',

            // Reportes
            'view_reports',
            'create_reports',
            'view_pqr_reports',
            'view_service_reports',
            'view_breed_reports',
            'view_financial_reports',

            // Consultas
            'view_pet_history',
            'view_client_history',

            // Dashboard
            'view_dashboard',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Crear roles y asignar permisos

        // Gerente General
        $gerenteGeneral = Role::create(['name' => 'gerente_general']);
        $gerenteGeneral->givePermissionTo([
            'manage_users',
            'view_users',
            'manage_services',
            'view_services',
            'manage_prices',
            'view_prices',
            'view_clients',
            'view_pets',
            'view_appointments',
            'view_clinic_services',
            'view_hotel_services',
            'view_spa_services',
            'manage_pqrs',
            'view_pqrs',
            'respond_pqrs',
            'view_reports',
            'create_reports',
            'view_pqr_reports',
            'view_service_reports',
            'view_breed_reports',
            'view_financial_reports',
            'view_pet_history',
            'view_client_history',
            'view_dashboard',
        ]);

        // Empleado del Hotel
        $empleadoHotel = Role::create(['name' => 'empleado_hotel']);
        $empleadoHotel->givePermissionTo([
            'manage_clients',
            'view_clients',
            'manage_pets',
            'view_pets',
            'manage_appointments',
            'view_appointments',
            'manage_hotel_services',
            'view_hotel_services',
            'manage_hotel_stays',
            'view_hotel_stays',
            'view_clinic_services',
            'view_medical_records',
            'view_vaccinations',
            'view_services',
            'view_prices',
            'view_service_reports',
            'view_breed_reports',
            'view_dashboard',
        ]);

        // Administrador de la Clínica
        $adminClinica = Role::create(['name' => 'admin_clinica']);
        $adminClinica->givePermissionTo([
            'manage_clients',
            'view_clients',
            'manage_pets',
            'view_pets',
            'manage_appointments',
            'view_appointments',
            'manage_clinic_services',
            'view_clinic_services',
            'manage_medical_records',
            'view_medical_records',
            'manage_vaccinations',
            'view_vaccinations',
            'view_spa_services',
            'view_services',
            'view_prices',
            'view_service_reports',
            'view_pet_history',
            'view_dashboard',
        ]);

        // Auxiliar del Spa
        $auxiliarSpa = Role::create(['name' => 'auxiliar_spa']);
        $auxiliarSpa->givePermissionTo([
            'manage_clients',
            'view_clients',
            'manage_pets',
            'view_pets',
            'manage_appointments',
            'view_appointments',
            'manage_spa_services',
            'view_spa_services',
            'manage_services',
            'view_services',
            'manage_prices',
            'view_prices',
            'view_service_reports',
            'view_client_history',
            'view_dashboard',
        ]);

        // Usuario Público (sin permisos especiales, solo consultas públicas)
        $publicUser = Role::create(['name' => 'publico']);
        $publicUser->givePermissionTo([
            'view_services',
            'view_prices',
        ]);

        // Crear usuarios de ejemplo
        $this->createSampleUsers();

        // Crear categorías y servicios de ejemplo
        $this->createSampleServices();
    }

    private function createSampleUsers()
    {
        // Gerente General
        $gerente = User::create([
            'name' => 'Gerente General',
            'email' => 'gerente@mundomascotas.com',
            'password' => bcrypt('password'),
            'phone' => '3001234567',
            'position' => 'Gerente General',
            'is_active' => true,
        ]);
        $gerente->assignRole('gerente_general');

        // Empleado del Hotel
        $empleadoHotel = User::create([
            'name' => 'Ana García',
            'email' => 'hotel@mundomascotas.com',
            'password' => bcrypt('password'),
            'phone' => '3007654321',
            'position' => 'Empleado de Hotel',
            'is_active' => true,
        ]);
        $empleadoHotel->assignRole('empleado_hotel');

        // Administrador de Clínica
        $adminClinica = User::create([
            'name' => 'Dr. Carlos Rodríguez',
            'email' => 'clinica@mundomascotas.com',
            'password' => bcrypt('password'),
            'phone' => '3009876543',
            'position' => 'Veterinario Jefe',
            'is_active' => true,
        ]);
        $adminClinica->assignRole('admin_clinica');

        // Auxiliar del Spa
        $auxiliarSpa = User::create([
            'name' => 'María López',
            'email' => 'spa@mundomascotas.com',
            'password' => bcrypt('password'),
            'phone' => '3005432109',
            'position' => 'Auxiliar de Spa',
            'is_active' => true,
        ]);
        $auxiliarSpa->assignRole('auxiliar_spa');
    }

    private function createSampleServices()
    {
        // Categorías de Clínica
        $clinicVaccines = ServiceCategory::create([
            'name' => 'Vacunas',
            'segment' => 'clinic',
            'description' => 'Servicios de vacunación para mascotas',
        ]);

        $clinicGeneral = ServiceCategory::create([
            'name' => 'Medicina General',
            'segment' => 'clinic',
            'description' => 'Consultas y tratamientos médicos generales',
        ]);

        $clinicSurgery = ServiceCategory::create([
            'name' => 'Cirugía',
            'segment' => 'clinic',
            'description' => 'Procedimientos quirúrgicos',
        ]);

        // Categorías de Hotel
        $hotelAccommodation = ServiceCategory::create([
            'name' => 'Hospedaje',
            'segment' => 'hotel',
            'description' => 'Servicios de alojamiento para mascotas',
        ]);

        $hotelRecreation = ServiceCategory::create([
            'name' => 'Recreación',
            'segment' => 'hotel',
            'description' => 'Actividades recreativas y ejercicio',
        ]);

        $hotelFood = ServiceCategory::create([
            'name' => 'Alimentación',
            'segment' => 'hotel',
            'description' => 'Servicios de alimentación especializada',
        ]);

        $hotelTransport = ServiceCategory::create([
            'name' => 'Transporte',
            'segment' => 'hotel',
            'description' => 'Servicios de transporte de mascotas',
        ]);

        // Categorías de Spa
        $spaGrooming = ServiceCategory::create([
            'name' => 'Peluquería',
            'segment' => 'spa',
            'description' => 'Servicios de peluquería y arreglo estético',
        ]);

        $spaProducts = ServiceCategory::create([
            'name' => 'Venta de Artículos',
            'segment' => 'spa',
            'description' => 'Venta de productos para mascotas',
        ]);

        // Servicios de Clínica
        Service::create(['service_category_id' => $clinicVaccines->id, 'name' => 'Vacuna Triple', 'price' => 45000]);
        Service::create(['service_category_id' => $clinicVaccines->id, 'name' => 'Vacuna Antirrábica', 'price' => 35000]);
        Service::create(['service_category_id' => $clinicVaccines->id, 'name' => 'Vacuna Parvovirus', 'price' => 50000]);

        Service::create(['service_category_id' => $clinicGeneral->id, 'name' => 'Consulta General', 'price' => 60000]);
        Service::create(['service_category_id' => $clinicGeneral->id, 'name' => 'Examen de Laboratorio', 'price' => 80000]);
        Service::create(['service_category_id' => $clinicGeneral->id, 'name' => 'Radiografía', 'price' => 120000]);
        Service::create(['service_category_id' => $clinicGeneral->id, 'name' => 'Ecografía', 'price' => 150000]);

        Service::create(['service_category_id' => $clinicSurgery->id, 'name' => 'Esterilización Hembra', 'price' => 250000]);
        Service::create(['service_category_id' => $clinicSurgery->id, 'name' => 'Esterilización Macho', 'price' => 180000]);
        Service::create(['service_category_id' => $clinicSurgery->id, 'name' => 'Cirugía Menor', 'price' => 300000]);

        // Servicios de Hotel
        Service::create(['service_category_id' => $hotelAccommodation->id, 'name' => 'Habitación Estándar (día)', 'price' => 40000]);
        Service::create(['service_category_id' => $hotelAccommodation->id, 'name' => 'Habitación Premium (día)', 'price' => 60000]);
        Service::create(['service_category_id' => $hotelAccommodation->id, 'name' => 'Habitación Deluxe (día)', 'price' => 80000]);

        Service::create(['service_category_id' => $hotelRecreation->id, 'name' => 'Paseo Individual', 'price' => 25000]);
        Service::create(['service_category_id' => $hotelRecreation->id, 'name' => 'Sesión de Juego Grupal', 'price' => 20000]);
        Service::create(['service_category_id' => $hotelRecreation->id, 'name' => 'Entrenamiento Básico', 'price' => 45000]);

        Service::create(['service_category_id' => $hotelFood->id, 'name' => 'Alimentación Estándar', 'price' => 15000]);
        Service::create(['service_category_id' => $hotelFood->id, 'name' => 'Alimentación Premium', 'price' => 25000]);
        Service::create(['service_category_id' => $hotelFood->id, 'name' => 'Dieta Especial', 'price' => 35000]);

        Service::create(['service_category_id' => $hotelTransport->id, 'name' => 'Transporte Local', 'price' => 30000]);
        Service::create(['service_category_id' => $hotelTransport->id, 'name' => 'Transporte Intermunicipal', 'price' => 80000]);

        // Servicios de Spa
        Service::create(['service_category_id' => $spaGrooming->id, 'name' => 'Baño Completo', 'price' => 35000]);
        Service::create(['service_category_id' => $spaGrooming->id, 'name' => 'Corte de Pelo', 'price' => 25000]);
        Service::create(['service_category_id' => $spaGrooming->id, 'name' => 'Limpieza de Oídos', 'price' => 15000]);
        Service::create(['service_category_id' => $spaGrooming->id, 'name' => 'Corte de Uñas', 'price' => 12000]);
        Service::create(['service_category_id' => $spaGrooming->id, 'name' => 'Paquete Completo de Spa', 'price' => 70000]);

        Service::create(['service_category_id' => $spaProducts->id, 'name' => 'Shampoo Medicado', 'price' => 28000]);
        Service::create(['service_category_id' => $spaProducts->id, 'name' => 'Collar Antipulgas', 'price' => 18000]);
        Service::create(['service_category_id' => $spaProducts->id, 'name' => 'Juguete Interactivo', 'price' => 22000]);
        Service::create(['service_category_id' => $spaProducts->id, 'name' => 'Cama para Mascotas', 'price' => 45000]);
        Service::create(['service_category_id' => $spaProducts->id, 'name' => 'Alimento Premium (Kg)', 'price' => 12000]);
    }
}
