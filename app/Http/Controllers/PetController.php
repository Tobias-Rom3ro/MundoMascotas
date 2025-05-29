<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pet;
use App\Models\Client;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class PetController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view_pets')->only(['index', 'show']);
        $this->middleware('permission:manage_pets')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $query = Pet::with(['client']);

        // Filtros
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('species', 'like', "%{$search}%")
                    ->orWhere('breed', 'like', "%{$search}%")
                    ->orWhereHas('client', function($clientQuery) use ($search) {
                        $clientQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('species') && $request->species) {
            $query->where('species', $request->species);
        }

        if ($request->has('client_id') && $request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $pets = $query->paginate(15);
        $clients = Client::orderBy('name')->get(['id', 'name']);
        $species = Pet::distinct()->pluck('species');

        return view('pets.index', compact('pets', 'clients', 'species'));
    }

    public function create(Request $request)
    {
        $clients = Client::orderBy('name')->get(['id', 'name']);
        $selectedClient = $request->get('client_id') ? Client::find($request->get('client_id')) : null;

        return view('pets.create', compact('clients', 'selectedClient'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'species' => 'required|string|max:100',
            'breed' => 'required|string|max:100',
            'birth_date' => 'nullable|date|before:today',
            'gender' => 'required|in:male,female',
            'weight' => 'nullable|numeric|min:0|max:999.99',
            'medical_observations' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->all();

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('pets', 'public');
            $data['photo'] = $path;
        }

        $pet = Pet::create($data);

        return redirect()->route('pets.show', $pet)
            ->with('success', 'Mascota creada exitosamente.');
    }

    public function show(Pet $pet)
    {
        $pet->load(['client', 'appointments.service', 'medicalRecords.veterinarian', 'vaccinations', 'hotelStays']);

        return view('pets.show', compact('pet'));
    }

    public function edit(Pet $pet)
    {
        $clients = Client::orderBy('name')->get(['id', 'name']);

        return view('pets.edit', compact('pet', 'clients'));
    }

    public function update(Request $request, Pet $pet)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'species' => 'required|string|max:100',
            'breed' => 'required|string|max:100',
            'birth_date' => 'nullable|date|before:today',
            'gender' => 'required|in:male,female',
            'weight' => 'nullable|numeric|min:0|max:999.99',
            'medical_observations' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->all();

        // Manejar la subida de foto
        if ($request->hasFile('photo')) {
            // Eliminar foto anterior si existe
            if ($pet->photo) {
                Storage::disk('public')->delete($pet->photo);
            }
            $path = $request->file('photo')->store('pets', 'public');
            $data['photo'] = $path;
        }

        $pet->update($data);

        return redirect()->route('pets.show', $pet)
            ->with('success', 'Mascota actualizada exitosamente.');
    }

    public function destroy(Pet $pet)
    {
        try {
            // Eliminar foto si existe
            if ($pet->photo) {
                Storage::disk('public')->delete($pet->photo);
            }

            $pet->delete();

            return redirect()->route('pets.index')
                ->with('success', 'Mascota eliminada exitosamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'No se puede eliminar la mascota porque tiene registros asociados.');
        }
    }

    public function search(Request $request)
    {
        $search = $request->get('q');
        $clientId = $request->get('client_id');

        $query = Pet::with('client');

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('species', 'like', "%{$search}%")
                ->orWhere('breed', 'like', "%{$search}%");
        }

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        $pets = $query->limit(10)->get(['id', 'name', 'species', 'breed', 'client_id']);

        return response()->json($pets);
    }

    public function medicalHistory(Pet $pet)
    {
        $this->authorize('view_medical_records');

        $pet->load([
            'client',
            'medicalRecords.veterinarian',
            'medicalRecords.appointment.service',
            'vaccinations'
        ]);

        return view('pets.medical-history', compact('pet'));
    }
}
