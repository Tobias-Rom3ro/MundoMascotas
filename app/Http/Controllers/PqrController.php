<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pqr;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class PqrController extends Controller
{
    public function __construct()
    {
        // Solo las rutas públicas no requieren autenticación
        $this->middleware('auth')->except(['create', 'store', 'publicForm']);
        $this->middleware('permission:view_pqrs')->only(['index', 'show']);
        $this->middleware('permission:manage_pqrs')->only(['edit', 'update', 'destroy', 'assign', 'respond']);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Pqr::with('assignedEmployee');

        // Los usuarios solo pueden ver PQRs asignadas a ellos, excepto el gerente general
        if (!$user->isGeneralManager()) {
            $query->where('assigned_to', $user->id);
        }

        // Filtros
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                    ->orWhere('client_email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('assigned_to') && $request->assigned_to) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $pqrs = $query->orderBy('created_at', 'desc')->paginate(15);
        $employees = User::active()->orderBy('name')->get();

        return view('pqrs.index', compact('pqrs', 'employees'));
    }

    public function create()
    {
        // Formulario público para crear PQRs
        return view('pqrs.public-form');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_name' => 'required|string|max:255',
            'client_email' => 'required|email|max:255',
            'client_phone' => 'nullable|string|max:20',
            'type' => 'required|in:peticion,queja,reclamo,sugerencia',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        Pqr::create($request->all());

        return redirect()->route('pqrs.public-form')
            ->with('success', 'Su PQR ha sido registrada exitosamente. Nos comunicaremos con usted pronto.');
    }

    public function show(Pqr $pqr)
    {
        $user = auth()->user();

        // Verificar permisos de acceso
        if (!$user->isGeneralManager() && $pqr->assigned_to !== $user->id) {
            abort(403, 'No tienes permisos para ver esta PQR.');
        }

        return view('pqrs.show', compact('pqr'));
    }

    public function edit(Pqr $pqr)
    {
        $user = auth()->user();

        // Verificar permisos de acceso
        if (!$user->isGeneralManager() && $pqr->assigned_to !== $user->id) {
            abort(403, 'No tienes permisos para editar esta PQR.');
        }

        $employees = User::active()->orderBy('name')->get();
        return view('pqrs.edit', compact('pqr', 'employees'));
    }

    public function update(Request $request, Pqr $pqr)
    {
        $user = auth()->user();

        // Verificar permisos de acceso
        if (!$user->isGeneralManager() && $pqr->assigned_to !== $user->id) {
            abort(403, 'No tienes permisos para actualizar esta PQR.');
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,in_process,resolved,closed',
            'response' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->only(['status', 'response', 'assigned_to']);

        // Si se marca como resuelto, agregar timestamp
        if ($request->status === 'resolved' && $pqr->status !== 'resolved') {
            $data['resolved_at'] = now();
        }

        $pqr->update($data);

        return redirect()->route('pqrs.show', $pqr)
            ->with('success', 'PQR actualizada exitosamente.');
    }

    public function destroy(Pqr $pqr)
    {
        $this->authorize('manage_pqrs');

        try {
            $pqr->delete();
            return redirect()->route('pqrs.index')
                ->with('success', 'PQR eliminada exitosamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'No se puede eliminar la PQR.');
        }
    }

    public function assign(Request $request, Pqr $pqr)
    {
        $this->authorize('manage_pqrs');

        $validator = Validator::make($request->all(), [
            'assigned_to' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $pqr->update([
            'assigned_to' => $request->assigned_to,
            'status' => 'in_process'
        ]);

        return back()->with('success', 'PQR asignada exitosamente.');
    }

    public function respond(Request $request, Pqr $pqr)
    {
        $user = auth()->user();

        // Verificar permisos de acceso
        if (!$user->isGeneralManager() && $pqr->assigned_to !== $user->id) {
            abort(403, 'No tienes permisos para responder esta PQR.');
        }

        $validator = Validator::make($request->all(), [
            'response' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $pqr->update([
            'response' => $request->response,
            'status' => 'resolved',
            'resolved_at' => now()
        ]);

        return back()->with('success', 'Respuesta enviada exitosamente.');
    }

    public function publicForm()
    {
        // Vista pública para que cualquier persona pueda crear una PQR
        return view('pqrs.public-form');
    }

    public function myPqrs(Request $request)
    {
        $user = auth()->user();

        $query = Pqr::where('assigned_to', $user->id)
            ->orWhere('assigned_to', null); // PQRs sin asignar

        // Filtros
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        $pqrs = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('pqrs.my-pqrs', compact('pqrs'));
    }
}
