<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfDownloadController extends Controller
{
    /**
     * Stream the requested PDF to the browser, then delete it from storage.
     * The route is protected by a signed URL — Laravel validates the signature
     * and expiry before this method is called.
     */
    public function __invoke(Request $request): StreamedResponse
    {
        $path = $request->query('path');

        abort_if(! $path || ! Storage::disk('local')->exists($path), 404);

        $filename = basename($path);

        $response = Storage::disk('local')->download($path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);

        // Delete after the response has been sent
        app()->terminating(fn () => Storage::disk('local')->delete($path));

        return $response;
    }
}
