<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Pet;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view_clients')->only(['index', 'show']);
        $this->middleware('permission:manage_clients')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $query = Client::with('pets');

        // Filtros
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('identification_number', 'like', "%{$search}%");
            });
        }

        if ($request->has('identification_type') && $request->identification_type) {
            $query->where('identification_type', $request->identification_type);
        }

        $clients = $query->paginate(15);

        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        return view('clients.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'identification_type' => 'required|in:CC,CE,NIT,PP',
            'identification_number' => 'required|string|unique:clients,identification_number',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $client = Client::create($request->all());

        return redirect()->route('clients.show', $client)
            ->with('success', 'Cliente creado exitosamente.');
    }

    public function show(Client $client)
    {
        $client->load(['pets', 'appointments.service', 'hotelStays']);

        return view('clients.show', compact('client'));
    }

    public function edit(Client $client)
    {
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email,' . $client->id,
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'identification_type' => 'required|in:CC,CE,NIT,PP',
            'identification_number' => 'required|string|unique:clients,identification_number,' . $client->id,
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $client->update($request->all());

        return redirect()->route('clients.show', $client)
            ->with('success', 'Cliente actualizado exitosamente.');
    }

    public function destroy(Client $client)
    {
        try {
            $client->delete();
            return redirect()->route('clients.index')
                ->with('success', 'Cliente eliminado exitosamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'No se puede eliminar el cliente porque tiene registros asociados.');
        }
    }

    public function history(Client $client)
    {
        $this->authorize('view_client_history');

        $client->load([
            'pets.appointments.service',
            'pets.medicalRecords.veterinarian',
            'pets.vaccinations',
            'hotelStays',
            'appointments.service'
        ]);

        return view('clients.history', compact('client'));
    }

    public function search(Request $request)
    {
        $search = $request->get('q');

        $clients = Client::where('name', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->orWhere('identification_number', 'like', "%{$search}%")
            ->limit(10)
            ->get(['id', 'name', 'email', 'identification_number']);

        return response()->json($clients);
    }
}
