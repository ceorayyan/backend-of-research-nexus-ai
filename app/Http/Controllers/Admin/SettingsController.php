<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * Show settings page (admin only).
     */
    public function index()
    {
        $settings = $this->getSettings();
        return Inertia::render('Admin/Settings', ['settings' => $settings]);
    }

    /**
     * Update settings (admin only).
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'website_name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB
        ]);

        $settings = $this->getSettings();

        // Update website name
        $settings['website_name'] = $validated['website_name'];

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($settings['logo_path'] ?? null) {
                Storage::disk('public')->delete($settings['logo_path']);
            }

            // Store new logo
            $path = $request->file('logo')->store('logos', 'public');
            $settings['logo_path'] = $path;
            $settings['logo_url'] = Storage::disk('public')->url($path);
        }

        // Save settings to JSON file
        Storage::disk('local')->put('settings.json', json_encode($settings, JSON_PRETTY_PRINT));

        return redirect()->route('admin.settings.index')->with('success', 'Settings updated successfully');
    }

    /**
     * Remove logo (admin only).
     */
    public function removeLogo()
    {
        $settings = $this->getSettings();

        if ($settings['logo_path'] ?? null) {
            Storage::disk('public')->delete($settings['logo_path']);
        }

        $settings['logo_path'] = null;
        $settings['logo_url'] = null;

        Storage::disk('local')->put('settings.json', json_encode($settings, JSON_PRETTY_PRINT));

        return redirect()->route('admin.settings.index')->with('success', 'Logo removed successfully');
    }

    /**
     * Get settings from JSON file or return defaults.
     */
    private function getSettings()
    {
        $defaultSettings = [
            'website_name' => 'StataNexus.Ai',
            'logo_path' => null,
            'logo_url' => null,
        ];

        try {
            if (Storage::disk('local')->exists('settings.json')) {
                $settings = json_decode(Storage::disk('local')->get('settings.json'), true);
                return array_merge($defaultSettings, $settings);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to read settings: ' . $e->getMessage());
        }

        return $defaultSettings;
    }
}

