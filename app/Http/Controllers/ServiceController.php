<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view_services')->only(['index', 'show']);
        $this->middleware('permission:manage_services')->only(['create', 'store', 'edit', 'update', 'destroy']);
        $this->middleware('permission:manage_prices')->only(['updatePrice']);
    }

    public function index(Request $request)
    {
        $query = Service::with('category');

        // Filtros
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('category_id') && $request->category_id) {
            $query->where('service_category_id', $request->category_id);
        }

        if ($request->has('segment') && $request->segment) {
            $query->whereHas('category', function($q) use ($request) {
                $q->where('segment', $request->segment);
            });
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active);
        }

        $services = $query->paginate(15);
        $categories = ServiceCategory::orderBy('name')->get();

        return view('services.index', compact('services', 'categories'));
    }

    public function create()
    {
        $categories = ServiceCategory::orderBy('name')->get();
        return view('services.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_category_id' => 'required|exists:service_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $service = Service::create($request->all());

        return redirect()->route('services.show', $service)
            ->with('success', 'Servicio creado exitosamente.');
    }

    public function show(Service $service)
    {
        $service->load(['category', 'appointments.client', 'appointments.pet']);

        return view('services.show', compact('service'));
    }

    public function edit(Service $service)
    {
        $categories = ServiceCategory::orderBy('name')->get();
        return view('services.edit', compact('service', 'categories'));
    }

    public function update(Request $request, Service $service)
    {
        $validator = Validator::make($request->all(), [
            'service_category_id' => 'required|exists:service_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $service->update($request->all());

        return redirect()->route('services.show', $service)
            ->with('success', 'Servicio actualizado exitosamente.');
    }

    public function destroy(Service $service)
    {
        try {
            $service->delete();
            return redirect()->route('services.index')
                ->with('success', 'Servicio eliminado exitosamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'No se puede eliminar el servicio porque tiene citas asociadas.');
        }
    }

    public function updatePrice(Request $request, Service $service)
    {
        $this->authorize('manage_prices');

        $validator = Validator::make($request->all(), [
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $service->update(['price' => $request->price]);

        return back()->with('success', 'Precio actualizado exitosamente.');
    }

    public function publicCatalog(Request $request)
    {
        $query = Service::active()->with('category');

        if ($request->has('segment') && $request->segment) {
            $query->bySegment($request->segment);
        }

        $services = $query->orderBy('name')->get();
        $segments = ServiceCategory::distinct()->pluck('segment');

        return view('services.public-catalog', compact('services', 'segments'));
    }
}
