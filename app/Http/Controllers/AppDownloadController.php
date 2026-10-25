<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AppDownloadController extends Controller
{
    /**
     * The Android app, stored in the public folder.
     */
    public const APK_FILE = 'Monitoring Tanaman Realtime.apk';

    /**
     * Display the Android app download page.
     */
    public function show(): View
    {
        $path = public_path(self::APK_FILE);
        $isAvailable = is_file($path);

        return view('app-download', [
            'isAvailable' => $isAvailable,
            'fileName' => self::APK_FILE,
            'fileSize' => $isAvailable ? number_format(filesize($path) / 1024 / 1024, 1, ',', '.').' MB' : null,
            'updatedAt' => $isAvailable ? Carbon::createFromTimestamp(filemtime($path), config('app.timezone')) : null,
        ]);
    }

    /**
     * Download the APK with the Android package content type so phones offer to install it.
     */
    public function download(): BinaryFileResponse
    {
        $path = public_path(self::APK_FILE);

        abort_unless(is_file($path), 404);

        return response()->download($path, self::APK_FILE, [
            'Content-Type' => 'application/vnd.android.package-archive',
        ]);
    }
}
