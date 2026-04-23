<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    /**
     * Get website settings (public endpoint).
     */
    public function getSettings()
    {
        $defaultSettings = [
            'website_name' => 'StataNexus.Ai',
            'logo_url' => null,
        ];

        try {
            if (Storage::disk('local')->exists('settings.json')) {
                $settings = json_decode(Storage::disk('local')->get('settings.json'), true);
                return response()->json(array_merge($defaultSettings, $settings));
            }
        } catch (\Exception $e) {
            \Log::error('Failed to read settings: ' . $e->getMessage());
        }

        return response()->json($defaultSettings);
    }
}
