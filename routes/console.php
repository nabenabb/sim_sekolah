<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('files:secure-existing', function () {
    $public = Storage::disk('public');
    $private = Storage::disk('local');
    $folders = ['surat-masuk', 'surat-keluar', 'kerja-sama', 'pkl', 'sertifikat'];
    $moved = 0;

    foreach ($folders as $folder) {
        foreach ($public->allFiles($folder) as $path) {
            $stream = $public->readStream($path);
            if ($stream === false) {
                $this->error("Tidak bisa membaca {$path}; file publik dibiarkan.");
                continue;
            }

            try {
                $saved = $private->put($path, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            if (! $saved) {
                $this->error("Tidak bisa menulis {$path} ke storage privat; file publik dibiarkan.");
                continue;
            }

            $public->delete($path);
            $moved++;
        }
    }

    $this->info("{$moved} file dipindahkan ke storage privat.");
})->purpose('Pindahkan lampiran lama dari storage publik ke privat');
