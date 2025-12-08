<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReportPreset;
use App\Http\Requests\ReportPresetStoreRequest;
use App\Http\Requests\ReportPresetUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ReportPresetController extends Controller
{
    /**
     * List user's saved presets
     */
    public function index(): JsonResponse
    {
        $userId = Auth::id() ?? 1; // Default for demo

        $presets = ReportPreset::where('user_id', $userId)
            ->orderBy('name')
            ->get(['id', 'name', 'report_type', 'filters', 'created_at']);

        return response()->json([
            'data' => $presets,
        ]);
    }

    /**
     * Create a new preset
     */
    public function store(ReportPresetStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $userId = Auth::id() ?? 1;

        // Check if preset with same name exists
        $existing = ReportPreset::where('user_id', $userId)
            ->where('name', $validated['name'])
            ->first();

        if ($existing) {
            return response()->json([
                'error' => 'A preset with this name already exists.',
            ], 422);
        }

        $preset = ReportPreset::create([
            'user_id' => $userId,
            'name' => $validated['name'],
            'report_type' => $validated['report_type'],
            'filters' => $validated['filters'],
        ]);

        // Audit log
        \App\Models\AuditLog::log('preset_created', $preset, [
            'name' => $preset->name,
            'report_type' => $preset->report_type,
        ]);

        return response()->json([
            'message' => 'Preset created successfully',
            'data' => $preset,
        ], 201);
    }

    /**
     * Get a specific preset
     */
    public function show(string $id): JsonResponse
    {
        $userId = Auth::id() ?? 1;

        $preset = ReportPreset::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        return response()->json([
            'data' => $preset,
        ]);
    }

    /**
     * Update a preset
     */
    public function update(ReportPresetUpdateRequest $request, string $id): JsonResponse
    {
        $validated = $request->validated();

        $userId = Auth::id() ?? 1;

        $preset = ReportPreset::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        // Check for name conflicts if name is being updated
        if (isset($validated['name']) && $validated['name'] !== $preset->name) {
            $existing = ReportPreset::where('user_id', $userId)
                ->where('name', $validated['name'])
                ->where('id', '!=', $id)
                ->first();

            if ($existing) {
                return response()->json([
                    'error' => 'A preset with this name already exists.',
                ], 422);
            }
        }

        $preset->update($validated);

        return response()->json([
            'message' => 'Preset updated successfully',
            'data' => $preset,
        ]);
    }

    /**
     * Delete a preset
     */
    public function destroy(string $id): JsonResponse
    {
        $userId = Auth::id() ?? 1;

        $preset = ReportPreset::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $presetName = $preset->name;
        $preset->delete();

        // Audit log
        \App\Models\AuditLog::log('preset_deleted', null, [
            'preset_id' => $id,
            'name' => $presetName,
        ]);

        return response()->json([
            'message' => 'Preset deleted successfully',
        ]);
    }
}
