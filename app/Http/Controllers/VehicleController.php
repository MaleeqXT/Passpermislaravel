<?php
namespace App\Http\Controllers;
use App\Models\Vehicle;
use App\Models\Roles\Monitor\User\Monitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class VehicleController extends Controller {
 public function index(Request $request): JsonResponse {
  $request->validate(['zone_id' => ['nullable', 'exists:zones,id']]);
  $zoneId = $request->input('zone_id') ?: $request->user()?->zone_id;
  $vehicles = Vehicle::with(['agency:id,name','monitor.user:id,name,first_name,last_name','documents','maintenance'])
    ->when($zoneId, fn ($query) => $query->where('agency_id', $zoneId))
    ->latest()
    ->get();
  return response()->json(['data' => $vehicles]);
 }

 public function monitors(Request $request): JsonResponse {
  $request->validate(['zone_id' => ['required', 'exists:zones,id']]);

  $monitors = Monitor::query()
    ->where('status', 1)
    ->where(function ($query) use ($request) {
      $query->whereHas('user', fn ($userQuery) => $userQuery->where('zone_id', $request->input('zone_id')))
        // Existing monitor records without users.zone_id remain available
        // through their already assigned locations, exactly like the monitor
        // administration page.
        ->orWhere(function ($legacyQuery) use ($request) {
          $legacyQuery
            ->whereHas('user', fn ($userQuery) => $userQuery->whereNull('zone_id'))
            ->whereHas('lieux', fn ($locationQuery) => $locationQuery->where('zone_id', $request->input('zone_id')));
        });
    })
    ->with('user:id,first_name,last_name,name,zone_id')
    ->get()
    ->map(fn (Monitor $monitor) => [
      'id' => $monitor->id,
      'name' => trim(($monitor->user->first_name ?? '').' '.($monitor->user->last_name ?? '')) ?: $monitor->user->name,
    ])
    ->sortBy('name')
    ->values();

  return response()->json(['data' => $monitors]);
 }

 public function store(Request $request): JsonResponse {
  $data = $request->validate($this->rules());
  $storedPaths = [];

  DB::beginTransaction();
  try {
    $data['agency_id'] = $data['agency_id'] ?? $request->user()?->zone_id;
    if (! empty($data['monitor_id']) && ! Monitor::query()
      ->whereKey($data['monitor_id'])
      ->where('status', 1)
      ->where(function ($query) use ($data) {
        $query->whereHas('user', fn ($userQuery) => $userQuery->where('zone_id', $data['agency_id']))
          ->orWhere(function ($legacyQuery) use ($data) {
            $legacyQuery
              ->whereHas('user', fn ($userQuery) => $userQuery->whereNull('zone_id'))
              ->whereHas('lieux', fn ($locationQuery) => $locationQuery->where('zone_id', $data['agency_id']));
          });
      })
      ->exists()) {
      throw ValidationException::withMessages(['monitor_id' => "Le moniteur sélectionné n'est pas actif dans cette zone."]);
    }
    if($request->hasFile('photo')) {
      $data['photo'] = $request->file('photo')->store('vehicles', 'public');
      $storedPaths[] = $data['photo'];
    }
    $validityDates = $request->input('document_validity_dates', []);
    unset($data['documents'], $data['document_validity_dates'], $data['maintenance']);
    $vehicle = Vehicle::create($data);
    $required = ['carte_grise', 'assurance', 'controle_technique'];
    foreach ($request->file('documents', []) as $type => $file) {
      $path = $file->store("vehicles/{$vehicle->id}/documents", 'public');
      $storedPaths[] = $path;
      $validityDate = !empty($validityDates[$type]) ? $validityDates[$type] : null;
      $vehicle->documents()->create([
        'document_type' => $type,
        'file_name' => $file->getClientOriginalName(),
        'file_path' => $path,
        'file_type' => $file->getClientMimeType(),
        'file_size' => $file->getSize(),
        'is_required' => in_array($type, $required, true),
        'validity_date' => $validityDate,
      ]);
    }
    // Also handle case where document already exists or validity_date is provided without a new file
    if (!empty($validityDates)) {
      foreach ($validityDates as $type => $vDate) {
        if ($vDate && !$request->hasFile("documents.{$type}")) {
          $vehicle->documents()->where('document_type', $type)->update(['validity_date' => $vDate]);
        }
      }
    }
    if ($request->filled('maintenance')) $vehicle->maintenance()->create($request->input('maintenance'));
    DB::commit();
    return response()->json(['message' => 'Véhicule ajouté avec succès.', 'data' => $vehicle->load(['agency:id,name', 'monitor.user:id,name', 'documents', 'maintenance'])], 201);
  } catch (Throwable $exception) {
    DB::rollBack();
    foreach ($storedPaths as $path) Storage::disk('public')->delete($path);
    throw $exception;
  }
 }

 public function update(Request $request, Vehicle $vehicle): JsonResponse {
  $data = $request->validate($this->rules($vehicle));
  $data['agency_id'] = $data['agency_id'] ?? $vehicle->agency_id;
  // Keeping the currently assigned monitor must not fail just because that
  // historic assignment is no longer listed as active for the zone.
  if (! empty($data['monitor_id']) && $data['monitor_id'] !== $vehicle->monitor_id && ! $this->monitorBelongsToAgency($data['monitor_id'], $data['agency_id'])) {
    throw ValidationException::withMessages(['monitor_id' => "Le moniteur sélectionné n'est pas actif dans cette zone."]);
  }
  DB::transaction(function () use ($request, $vehicle, &$data) {
    if ($request->hasFile('photo')) {
      if ($vehicle->photo) Storage::disk('public')->delete($vehicle->photo);
      $data['photo'] = $request->file('photo')->store('vehicles', 'public');
    }
    $validityDates = $request->input('document_validity_dates', []);
    unset($data['documents'], $data['document_validity_dates'], $data['maintenance']);
    $vehicle->update($data);
    foreach ($request->file('documents', []) as $type => $file) {
      $existing = $vehicle->documents()->where('document_type', $type)->first();
      if ($existing) Storage::disk('public')->delete($existing->file_path);
      $path = $file->store("vehicles/{$vehicle->id}/documents", 'public');
      $validityDate = !empty($validityDates[$type]) ? $validityDates[$type] : ($existing?->validity_date);
      $vehicle->documents()->updateOrCreate(
        ['document_type' => $type],
        [
          'file_name' => $file->getClientOriginalName(),
          'file_path' => $path,
          'file_type' => $file->getClientMimeType(),
          'file_size' => $file->getSize(),
          'is_required' => in_array($type, ['carte_grise','assurance','controle_technique'], true),
          'validity_date' => $validityDate,
        ]
      );
    }
    // Update validity_date for existing documents without re-uploading file
    if (!empty($validityDates)) {
      foreach ($validityDates as $type => $vDate) {
        if ($vDate) {
          $vehicle->documents()->where('document_type', $type)->update(['validity_date' => $vDate]);
        }
      }
    }
    if ($request->filled('maintenance')) $vehicle->maintenance()->updateOrCreate([], $request->input('maintenance'));
  });
  return response()->json(['message' => 'Véhicule modifié avec succès.', 'data' => $vehicle->fresh()->load(['agency:id,name','monitor.user:id,name,first_name,last_name','documents','maintenance'])]);
 }

 public function destroy(Vehicle $vehicle): JsonResponse {
  DB::transaction(function () use ($vehicle) {
    if ($vehicle->photo) Storage::disk('public')->delete($vehicle->photo);
    foreach ($vehicle->documents as $document) Storage::disk('public')->delete($document->file_path);
    $vehicle->delete();
  });
  return response()->json(['message' => 'Véhicule supprimé avec succès.']);
 }

 private function monitorBelongsToAgency(string $monitorId, string $agencyId): bool {
  return Monitor::query()->whereKey($monitorId)->where('status', 1)->whereHas('user', fn ($query) => $query->where('zone_id', $agencyId))->exists();
 }

 private function rules(?Vehicle $vehicle = null): array {
  return [
    'brand'=>'required|string|max:100','model'=>'required|string|max:100','trim'=>'nullable|string|max:100',
    'registration_number'=>['required','string','max:30', Rule::unique('vehicles', 'registration_number')->ignore($vehicle?->id)],'registration_date'=>'required|date',
    'fuel_type'=>'required|string|max:50','transmission'=>'required|string|max:50','power_cv'=>'nullable|integer|min:0',
    'current_mileage'=>'required|integer|min:0','color'=>'nullable|string|max:50','agency_id'=>'nullable|exists:zones,id',
    'monitor_id'=>'nullable|exists:monitors,id',
    'notes'=>'nullable|string','photo'=>'nullable|image|max:5120',
    'documents'=>'nullable|array','documents.*'=>'file|max:5120',
    'document_validity_dates'=>'nullable|array',
    'document_validity_dates.*'=>'nullable|date',
    'maintenance'=>'nullable|array',
    'maintenance.last_maintenance_date'=>'nullable|date','maintenance.next_maintenance_date'=>'nullable|date',
    'maintenance.last_maintenance_mileage'=>'nullable|integer|min:0',
    'maintenance.maintenance_kilometer'=>'nullable|integer|min:1',
    'maintenance.maintenance_type'=>['nullable', Rule::in([
      'Vidange moteur', 'Freins (plaquettes / disques)', 'Pneumatiques', 'Contrôle technique',
      'Distribution', 'Embrayage', 'Batterie', 'Climatisation', 'Éclairage / ampoules',
      'Essuie-glaces / lave-glace', 'Niveaux / liquides', 'Carrosserie / peinture',
      'Pare-brise / vitrage', 'Mécanique / réparation', 'Électronique / diagnostic',
      'Nettoyage / préparation', 'Équipement auto-école / double commande', 'Autre',
    ])],
    'maintenance.garage'=>'nullable|string|max:150','maintenance.cost'=>'nullable|numeric|min:0',
    'maintenance.tire_front_condition'=>['nullable', Rule::in(['good', 'monitor', 'replace', 'unsafe'])],
    'maintenance.tire_rear_condition'=>['nullable', Rule::in(['good', 'monitor', 'replace', 'unsafe'])],
    'maintenance.tire_change_date'=>'nullable|date',
    'maintenance.tire_change_mileage'=>'nullable|integer|min:0','maintenance.observations'=>'nullable|string',
  ];
 }
}
