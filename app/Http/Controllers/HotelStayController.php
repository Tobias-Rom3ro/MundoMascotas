<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HotelStay;
use App\Models\Pet;
use App\Models\Client;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class HotelStayController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view_hotel_services')->only(['index', 'show']);
        $this->middleware('permission:manage_hotel_services')->only(['create', 'store', 'edit', 'update', 'destroy']);
        $this->middleware('permission:manage_hotel_checkin')->only(['checkIn', 'checkOut']);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = HotelStay::with(['pet', 'client']);

        // Filtrar por permisos de usuario
        if (!$user->isGeneralManager() && !$user->isHotelEmployee()) {
            abort(403, 'No tienes permisos para ver los registros de hospedaje.');
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

        if ($request->has('room_type') && $request->room_type) {
            $query->where('room_type', $request->room_type);
        }

        if ($request->has('check_in_from') && $request->check_in_from) {
            $query->whereDate('check_in_date', '>=', $request->check_in_from);
        }

        if ($request->has('check_in_to') && $request->check_in_to) {
            $query->whereDate('check_in_date', '<=', $request->check_in_to);
        }

        $hotelStays = $query->orderBy('check_in_date', 'desc')->paginate(15);

        return view('hotel-stays.index', compact('hotelStays'));
    }

    public function create(Request $request)
    {
        $clients = Client::orderBy('name')->get();
        $selectedClient = $request->get('client_id') ? Client::find($request->get('client_id')) : null;
        $selectedPet = $request->get('pet_id') ? Pet::find($request->get('pet_id')) : null;

        return view('hotel-stays.create', compact('clients', 'selectedClient', 'selectedPet'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'pet_id' => 'required|exists:pets,id',
            'check_in_date' => 'required|date|after:now',
            'check_out_date' => 'required|date|after:check_in_date',
            'room_type' => 'required|in:standard,premium,deluxe',
            'daily_rate' => 'required|numeric|min:0',
            'special_requirements' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Verificar que la mascota pertenece al cliente
        $pet = Pet::find($request->pet_id);
        if ($pet->client_id != $request->client_id) {
            return back()->withError('La mascota seleccionada no pertenece al cliente seleccionado.');
        }

        // Calcular costo total
        $checkIn = Carbon::parse($request->check_in_date);
        $checkOut = Carbon::parse($request->check_out_date);
        $days = $checkIn->diffInDays($checkOut);
        $totalCost = $days * $request->daily_rate;

        $data = $request->all();
        $data['total_cost'] = $totalCost;

        $hotelStay = HotelStay::create($data);

        return redirect()->route('hotel-stays.show', $hotelStay)
            ->with('success', 'Reserva de hospedaje creada exitosamente.');
    }

    public function show(HotelStay $hotelStay)
    {
        $hotelStay->load(['pet', 'client']);
        return view('hotel-stays.show', compact('hotelStay'));
    }

    public function edit(HotelStay $hotelStay)
    {
        $clients = Client::orderBy('name')->get();
        return view('hotel-stays.edit', compact('hotelStay', 'clients'));
    }

    public function update(Request $request, HotelStay $hotelStay)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'pet_id' => 'required|exists:pets,id',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
            'room_type' => 'required|in:standard,premium,deluxe',
            'daily_rate' => 'required|numeric|min:0',
            'status' => 'required|in:reserved,active,completed,cancelled',
            'special_requirements' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Verificar que la mascota pertenece al cliente
        $pet = Pet::find($request->pet_id);
        if ($pet->client_id != $request->client_id) {
            return back()->withError('La mascota seleccionada no pertenece al cliente seleccionado.');
        }

        // Recalcular costo total si cambiaron las fechas o tarifa
        $checkIn = Carbon::parse($request->check_in_date);
        $checkOut = Carbon::parse($request->check_out_date);
        $days = $checkIn->diffInDays($checkOut);
        $totalCost = $days * $request->daily_rate;

        $data = $request->all();
        $data['total_cost'] = $totalCost;

        $hotelStay->update($data);

        return redirect()->route('hotel-stays.show', $hotelStay)
            ->with('success', 'Reserva de hospedaje actualizada exitosamente.');
    }

    public function destroy(HotelStay $hotelStay)
    {
        try {
            $hotelStay->delete();
            return redirect()->route('hotel-stays.index')
                ->with('success', 'Reserva de hospedaje eliminada exitosamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'No se puede eliminar la reserva de hospedaje.');
        }
    }

    public function checkIn(HotelStay $hotelStay)
    {
        if ($hotelStay->status !== 'reserved') {
            return back()->with('error', 'Solo se puede hacer check-in a reservas confirmadas.');
        }

        $hotelStay->update(['status' => 'active']);

        return back()->with('success', 'Check-in realizado exitosamente.');
    }

    public function checkOut(HotelStay $hotelStay)
    {
        if ($hotelStay->status !== 'active') {
            return back()->with('error', 'Solo se puede hacer check-out a hospedajes activos.');
        }

        $hotelStay->update(['status' => 'completed']);

        return back()->with('success', 'Check-out realizado exitosamente.');
    }

    public function calendar(Request $request)
    {
        $hotelStays = HotelStay::with(['pet', 'client'])
            ->where('check_in_date', '>=', now()->startOfMonth())
            ->where('check_out_date', '<=', now()->endOfMonth())
            ->get();

        return view('hotel-stays.calendar', compact('hotelStays'));
    }
}
