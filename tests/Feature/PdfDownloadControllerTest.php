<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

it('downloads a PDF file via valid signed URL', function () {
    Storage::fake('local');
    Storage::disk('local')->put('exports/report.pdf', '%PDF-1.4 test content');

    $url = URL::temporarySignedRoute('exports.download', now()->addHours(24), [
        'path' => 'exports/report.pdf',
    ]);

    $this->get($url)
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('content-disposition');
});

it('returns 404 when the file does not exist', function () {
    Storage::fake('local');

    $url = URL::temporarySignedRoute('exports.download', now()->addHours(24), [
        'path' => 'exports/nonexistent.pdf',
    ]);

    $this->get($url)->assertNotFound();
});

it('returns 404 when path query parameter is missing', function () {
    Storage::fake('local');

    $url = URL::temporarySignedRoute('exports.download', now()->addHours(24), [
        'path' => '',
    ]);

    $this->get($url)->assertNotFound();
});

it('returns 403 when signature is invalid', function () {
    Storage::fake('local');
    Storage::disk('local')->put('exports/report.pdf', '%PDF-1.4 test content');

    $this->get('/exports/download?path=exports/report.pdf&signature=invalid')
        ->assertForbidden();
});

it('returns 403 when signed URL has expired', function () {
    Storage::fake('local');
    Storage::disk('local')->put('exports/report.pdf', '%PDF-1.4 test content');

    $url = URL::temporarySignedRoute('exports.download', now()->subSecond(), [
        'path' => 'exports/report.pdf',
    ]);

    $this->get($url)->assertForbidden();
});

it('deletes the file after the response is sent', function () {
    Storage::fake('local');
    Storage::disk('local')->put('exports/cleanup-test.pdf', '%PDF-1.4 test content');

    $url = URL::temporarySignedRoute('exports.download', now()->addHours(24), [
        'path' => 'exports/cleanup-test.pdf',
    ]);

    $this->get($url)->assertOk();

    // Trigger app terminating callbacks
    app()->terminate();

    Storage::disk('local')->assertMissing('exports/cleanup-test.pdf');
});
