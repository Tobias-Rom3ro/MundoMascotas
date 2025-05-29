<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class MedicalRecordController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view_medical_records')->only(['index', 'show']);
        $this->middleware('permission:manage_medical_records')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = MedicalRecord::with(['pet', 'appointment', 'veterinarian']);

        // Filtrar por permisos de usuario - solo clínica puede ver registros médicos
        if (!$user->isGeneralManager() && !$user->isClinicAdmin()) {
            abort(403, 'No tienes permisos para ver los registros médicos.');
        }

        // Filtros
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('pet', function($petQuery) use ($search) {
                    $petQuery->where('name', 'like', "%{$search}%")
                        ->orWhereHas('client', function($clientQuery) use ($search) {
                            $clientQuery->where('name', 'like', "%{$search}%");
                        });
                })->orWhere('diagnosis', 'like', "%{$search}%");
            });
        }

        if ($request->has('pet_id') && $request->pet_id) {
            $query->where('pet_id', $request->pet_id);
        }

        if ($request->has('veterinarian_id') && $request->veterinarian_id) {
            $query->where('veterinarian_id', $request->veterinarian_id);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $medicalRecords = $query->orderBy('created_at', 'desc')->paginate(15);
        $pets = Pet::with('client')->orderBy('name')->get();
        $veterinarians = User::active()->whereHas('roles', function($q) {
            $q->whereIn('name', ['admin_clinica', 'gerente_general']);
        })->orderBy('name')->get();

        return view('medical-records.index', compact('medicalRecords', 'pets', 'veterinarians'));
    }

    public function create(Request $request)
    {
        $pets = Pet::with('client')->orderBy('name')->get();
        $veterinarians = User::active()->whereHas('roles', function($q) {
            $q->whereIn('name', ['admin_clinica', 'gerente_general']);
        })->orderBy('name')->get();

        $selectedAppointment = $request->get('appointment_id') ?
            Appointment::with(['pet', 'client'])->find($request->get('appointment_id')) : null;

        return view('medical-records.create', compact('pets', 'veterinarians', 'selectedAppointment'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pet_id' => 'required|exists:pets,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'veterinarian_id' => 'required|exists:users,id',
            'diagnosis' => 'required|string',
            'treatment' => 'required|string',
            'medications' => 'nullable|string',
            'observations' => 'nullable|string',
            'next_visit' => 'nullable|date|after:today',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Verificar que el veterinario tiene permisos
        $veterinarian = User::find($request->veterinarian_id);
        if (!$veterinarian->isClinicAdmin() && !$veterinarian->isGeneralManager()) {
            return back()->withError('El usuario seleccionado no tiene permisos de veterinario.');
        }

        $medicalRecord = MedicalRecord::create($request->all());

        return redirect()->route('medical-records.show', $medicalRecord)
            ->with('success', 'Registro médico creado exitosamente.');
    }

    public function show(MedicalRecord $medicalRecord)
    {
        $medicalRecord->load(['pet.client', 'appointment.service', 'veterinarian']);
        return view('medical-records.show', compact('medicalRecord'));
    }

    public function edit(MedicalRecord $medicalRecord)
    {
        $pets = Pet::with('client')->orderBy('name')->get();
        $veterinarians = User::active()->whereHas('roles', function($q) {
            $q->whereIn('name', ['admin_clinica', 'gerente_general']);
        })->orderBy('name')->get();

        return view('medical-records.edit', compact('medicalRecord', 'pets', 'veterinarians'));
    }

    public function update(Request $request, MedicalRecord $medicalRecord)
    {
        $validator = Validator::make($request->all(), [
            'pet_id' => 'required|exists:pets,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'veterinarian_id' => 'required|exists:users,id',
            'diagnosis' => 'required|string',
            'treatment' => 'required|string',
            'medications' => 'nullable|string',
            'observations' => 'nullable|string',
            'next_visit' => 'nullable|date|after:today',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Verificar que el veterinario tiene permisos
        $veterinarian = User::find($request->veterinarian_id);
        if (!$veterinarian->isClinicAdmin() && !$veterinarian->isGeneralManager()) {
            return back()->withError('El usuario seleccionado no tiene permisos de veterinario.');
        }

        $medicalRecord->update($request->all());

        return redirect()->route('medical-records.show', $medicalRecord)
            ->with('success', 'Registro médico actualizado exitosamente.');
    }

    public function destroy(MedicalRecord $medicalRecord)
    {
        try {
            $medicalRecord->delete();
            return redirect()->route('medical-records.index')
                ->with('success', 'Registro médico eliminado exitosamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'No se puede eliminar el registro médico.');
        }
    }

    public function byPet(Pet $pet)
    {
        $this->authorize('view_medical_records');

        $medicalRecords = $pet->medicalRecords()
            ->with(['appointment.service', 'veterinarian'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('medical-records.by-pet', compact('pet', 'medicalRecords'));
    }
}
