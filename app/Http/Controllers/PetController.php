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

        // Manejar la subida de foto
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('pets', 'public');
