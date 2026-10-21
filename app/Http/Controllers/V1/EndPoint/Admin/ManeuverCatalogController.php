<?php

namespace App\Http\Controllers\V1\EndPoint\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManeuverCatalog;
use App\Services\Monitor\Training\ManeuverOccurrenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ManeuverCatalogController extends Controller
{
    public function index(ManeuverOccurrenceService $catalog): JsonResponse
    {
        $catalog->ensureCatalog();
        return response()->json(['data' => $catalog->allCatalog()]);
    }

    public function store(Request $request, ManeuverOccurrenceService $catalog): JsonResponse
    {
        $catalog->ensureCatalog();
        $values = $this->validated($request);
        $base = Str::slug($values['name']);
        $slug = $base;
        $suffix = 2;
        while (ManeuverCatalog::query()->where('slug', $slug)->exists()) $slug = $base.'-'.$suffix++;
        $item = ManeuverCatalog::create($values + ['slug' => $slug, 'is_active' => true]);
        return response()->json(['data' => $this->payload($item)], 201);
    }

    public function update(Request $request, ManeuverCatalog $maneuver): JsonResponse
    {
        $values = $this->validated($request, false);
        $maneuver->update($values);
        return response()->json(['data' => $this->payload($maneuver->fresh())]);
    }

    /** A catalogue entry is archived, never physically deleted, to preserve lesson history. */
    public function destroy(ManeuverCatalog $maneuver): JsonResponse
    {
        $maneuver->update(['is_active' => false]);
        return response()->json(['message' => 'Manœuvre désactivée.', 'data' => $this->payload($maneuver->fresh())]);
    }

    private function validated(Request $request, bool $creating = true): array
    {
        $rules = [
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:160'],
            'group_name' => [$creating ? 'required' : 'sometimes', 'string', 'max:80'],
            'icon' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'between:0,9999'],
            'is_active' => [$creating ? 'prohibited' : 'sometimes', 'boolean'],
        ];
        $values = $request->validate($rules);
        if (array_key_exists('name', $values)) $values['name'] = trim($values['name']);
        if (array_key_exists('group_name', $values)) $values['group_name'] = trim($values['group_name']);
        return $values;
    }

    private function payload(ManeuverCatalog $item): array
    {
        return ['id' => $item->id, 'slug' => $item->slug, 'name' => $item->name, 'group' => $item->group_name,
            'icon' => $item->icon, 'sort_order' => $item->sort_order, 'is_active' => $item->is_active];
    }
}
