<?php

namespace App\Http\Controllers;

use App\Models\AppInstall;
use App\Support\Impersonation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PwaController extends Controller
{
    /**
     * The web app manifest, served by Laravel (not a static file) so it can point at ITSELF with an absolute
     * URL: `related_applications` is what lets Chrome answer navigator.getInstalledRelatedApps() from a plain
     * browser tab ("is this PWA installed?"), whatever the domain (local, homologação, produção).
     */
    public function manifest(): JsonResponse
    {
        $manifest = json_decode((string) file_get_contents(resource_path('pwa/manifest.json')), true);

        $manifest['related_applications'] = [
            ['platform' => 'webapp', 'url' => url('/manifest.webmanifest')],
        ];

        return response()->json($manifest, 200, [
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'public, max-age=3600',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * The browser tells us the app is installed on this device (standalone window, `appinstalled`, accepted
     * prompt or getInstalledRelatedApps). One row per user + device; used for the install metrics in the panel.
     */
    public function installed(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device' => ['required', 'string', 'min:8', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
            'platform' => ['required', 'in:android,ios,desktop'],
            'standalone' => ['boolean'],
        ]);

        // An admin inside a user's account must not register the admin's own device as the user's.
        if (Impersonation::isActive()) {
            return response()->json(['recorded' => false]);
        }

        $install = AppInstall::firstOrNew(['user_id' => $request->user()->id, 'device_key' => $data['device']]);
        $install->platform = $data['platform'];
        $install->installed_at ??= now();
        $install->last_seen_at = now();
        if ($request->boolean('standalone')) {
            $install->last_standalone_at = now();
        }
        $install->save();

        return response()->json(['recorded' => true]);
    }
}
