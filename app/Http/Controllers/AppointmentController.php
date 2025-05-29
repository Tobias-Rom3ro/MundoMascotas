<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Pet;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view_appointments')->only(['index', 'show']);
        $this->middleware('permission:manage_appointments')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Appointment::with(['client', 'pet', 'service.category', 'employee']);

        // Filtrar por permisos de usuario
        if ($user->isHotelEmployee()) {
            $query->whereHas('service.category', function($q) {
                $q->whereIn('segment', ['hotel', 'clinic']);
            });
        } elseif ($user->isClinicAdmin()) {
            $query->whereHas('service.category', function($q) {
                $q->whereIn('segment', ['clinic', 'spa']);
            });
        } elseif ($user->isSpaAssistant()) {
            $query->whereHas('service.category', function($q) {
                $q->where('segment', 'spa');
            });
        }

        // Filtros
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('client', function($clientQuery) use ($search) {
                    $clientQuery->where('name', 'like', "%{$search}%");
                })->orWhereHas('pet', function($petQuery) use ($search) {
                    $petQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('service_id') && $request->service_id) {
            $query->where('service_id', $request->service_id);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('appointment_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('appointment_date', '<=', $request->date_to);
        }

        $appointments = $query->orderBy('appointment_date', 'desc')->paginate(15);
        $services = Service::active()->orderBy('name')->get();

        return view('appointments.index', compact('appointments', 'services'));
    }

    public function create(Request $request)
    {
        $clients = Client::orderBy('name')->get();
        $services = Service::active()->with('category')->orderBy('name')->get();
        $employees = User::active()->get();

        $selectedClient = $request->get('client_id') ? Client::find($request->get('client_id')) : null;
        $selectedPet = $request->get('pet_id') ? Pet::find($request->get('pet_id')) : null;

        return view('appointments.create', compact('clients', 'services', 'employees', 'selectedClient', 'selectedPet'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'pet_id' => 'required|exists:pets,id',
            'service_id' => 'required|exists:services,id',
            'user_id' => 'required|exists:users,id',
            'appointment_date' => 'required|date|after:now',
            'notes' => 'nullable|string',
            'final_price' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Verificar que la mascota pertenece al cliente
        $pet = Pet::find($request->pet_id);
        if ($pet->client_id != $request->client_id) {
            return back()->withError('La mascota seleccionada no pertenece al cliente seleccionado.');
        }

        $appointment = Appointment::create($request->all());

        return redirect()->route('appointments.show', $appointment)
            ->with('success', 'Cita creada exitosamente.');
    }

    public function show(Appointment $appointment)
    {
        $appointment->load(['client', 'pet', 'service.category', 'employee', 'medicalRecord']);

        return view('appointments.show', compact('appointment'));
    }

    public function edit(Appointment $appointment)
    {
        $clients = Client::orderBy('name')->get();
        $services = Service::active()->with('category')->orderBy('name')->get();
        $employees = User::active()->get();

        return view('appointments.edit', compact('appointment', 'clients', 'services', 'employees'));
    }

    public function update(Request $request, Appointment $appointment)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'pet_id' => 'required|exists:pets,id',
            'service_id' => 'required|exists:services,id',
            'user_id' => 'required|exists:users,id',
            'appointment_date' => 'required|date',
            'status' => 'required|in:scheduled,in_progress,completed,cancelled',
            'notes' => 'nullable|string',
            'final_price' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Verificar que la mascota pertenece al cliente
        $pet = Pet::find($request->pet_id);
        if ($pet->client_id != $request->client_id) {
            return back()->withError('La mascota seleccionada no pertenece al cliente seleccionado.');
        }

        $appointment->update($request->all());

        return redirect()->route('appointments.show', $appointment)
            ->with('success', 'Cita actualizada exitosamente.');
    }

    public function destroy(Appointment $appointment)
    {
        try {
            $appointment->delete();
            return redirect()->route('appointments.index')
                ->with('success', 'Cita eliminada exitosamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'No se puede eliminar la cita.');
        }
    }

    public function updateStatus(Request $request, Appointment $appointment)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:scheduled,in_progress,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $appointment->update(['status' => $request->status]);

        return back()->with('success', 'Estado de la cita actualizado exitosamente.');
    }

    public function calendar(Request $request)
    {
        $appointments = Appointment::with(['client', 'pet', 'service'])
            ->where('appointment_date', '>=', now()->startOfMonth())
            ->where('appointment_date', '<=', now()->endOfMonth())
            ->get();

        return view('appointments.calendar', compact('appointments'));
    }
}
