<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        $settings = Setting::all()->keyBy('key')->map(fn ($setting) => $setting->value);

        $defaultSettings = [
            'platform_name' => 'MENA Devs Assessment',
            'support_email' => 'support@menadevs.com',
            'default_passing_score' => 70,
            'require_camera_verification' => false,
            'enforce_anti_cheat' => true,
        ];

        return Inertia::render('Admin/Settings/Index', [
            'settings' => array_merge($defaultSettings, $settings->toArray()),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'platform_name' => ['required', 'string', 'max:255'],
            'support_email' => ['required', 'email', 'max:255'],
            'default_passing_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'require_camera_verification' => ['required', 'boolean'],
            'enforce_anti_cheat' => ['required', 'boolean'],
        ]);

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'type' => gettype($value)]
            );
        }

        return back()->with('success', 'Platform settings have been updated successfully.');
    }
}
