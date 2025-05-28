<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Pet;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Pqr;
use App\Models\HotelStay;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view_dashboard');
    }

    public function index()
    {
        $user = auth()->user();

        // Estadísticas generales
        $stats = [
            'total_clients' => Client::count(),
            'total_pets' => Pet::count(),
            'appointments_today' => Appointment::whereDate('appointment_date', today())->count(),
            'pending_pqrs' => Pqr::where('status', 'pending')->count(),
        ];

        // Estadísticas específicas por rol
        if ($user->isGeneralManager()) {
            $stats = array_merge($stats, $this->getManagerStats());
        } elseif ($user->isHotelEmployee()) {
            $stats = array_merge($stats, $this->getHotelStats());
        } elseif ($user->isClinicAdmin()) {
            $stats = array_merge($stats, $this->getClinicStats());
        } elseif ($user->isSpaAssistant()) {
            $stats = array_merge($stats, $this->getSpaStats());
        }

        // Próximas citas
        $upcoming_appointments = Appointment::with(['client', 'pet', 'service'])
            ->where('appointment_date', '>=', now())
            ->orderBy('appointment_date')
            ->limit(5)
            ->get();

        // Servicios más solicitados (últimos 30 días)
        $popular_services = Service::select('services.*', DB::raw('COUNT(appointments.id) as appointment_count'))
            ->leftJoin('appointments', 'services.id', '=', 'appointments.service_id')
            ->where('appointments.created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('services.id')
            ->orderBy('appointment_count', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard', compact('stats', 'upcoming_appointments', 'popular_services'));
    }

    private function getManagerStats()
    {
        return [
            'monthly_revenue' => Appointment::where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->sum('final_price'),
            'active_hotel_stays' => HotelStay::where('status', 'active')->count(),
            'total_services' => Service::where('is_active', true)->count(),
        ];
    }

    private function getHotelStats()
    {
        return [
            'active_stays' => HotelStay::where('status', 'active')->count(),
            'checkins_today' => HotelStay::whereDate('check_in_date', today())->count(),
            'checkouts_today' => HotelStay::whereDate('check_out_date', today())->count(),
        ];
    }

    private function getClinicStats()
    {
        return [
            'clinic_appointments_today' => Appointment::whereHas('service.category', function($q) {
                $q->where('segment', 'clinic');
            })->whereDate('appointment_date', today())->count(),
            'pending_medical_records' => Appointment::whereDoesntHave('medicalRecord')
                ->where('status', 'completed')
                ->whereHas('service.category', function($q) {
                    $q->where('segment', 'clinic');
                })->count(),
        ];
    }

    private function getSpaStats()
    {
        return [
            'spa_appointments_today' => Appointment::whereHas('service.category', function($q) {
                $q->where('segment', 'spa');
            })->whereDate('appointment_date', today())->count(),
            'spa_revenue_month' => Appointment::whereHas('service.category', function($q) {
                $q->where('segment', 'spa');
            })->where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->sum('final_price'),
        ];
    }
}
