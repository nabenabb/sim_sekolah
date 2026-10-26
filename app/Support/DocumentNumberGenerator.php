<?php

namespace App\Support;

use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use Illuminate\Support\Facades\DB;

class DocumentNumberGenerator
{
    public function incomingAgenda(): string
    {
        $now = now();
        $year = $now->format('y');
        $sequenceName = 'incoming-letter:' . $year;
        $maximum = DB::table('document_number_sequences')->where('name', $sequenceName)->exists()
            ? 0
            : (IncomingLetter::withTrashed()->where('no_agenda', 'like', "%/{$year}")->pluck('no_agenda')
                ->map(fn (string $number) => (int) explode('/', $number)[0])->max() ?? 0);

        $sequence = $this->nextSequence($sequenceName, $maximum);

        return sprintf('%03d/AGD/%s/%s', $sequence, $now->format('m'), $year);
    }

    public function outgoingLetter(): string
    {
        $now = now();
        $year = $now->format('y');
        $sequenceName = 'outgoing-letter:' . $year;
        $maximum = DB::table('document_number_sequences')->where('name', $sequenceName)->exists()
            ? 0
            : (OutgoingLetter::withTrashed()->where('no_surat', 'like', "%/{$year}")->pluck('no_surat')
                ->map(fn (string $number) => (int) explode('/', $number)[0])->max() ?? 0);

        $sequence = $this->nextSequence($sequenceName, $maximum);

        return sprintf('%03d/SMKN4/%s/%s', $sequence, $now->format('m'), $year);
    }

    private function nextSequence(string $name, int $existingMaximum): int
    {
        return DB::transaction(function () use ($name, $existingMaximum): int {
            DB::table('document_number_sequences')->insertOrIgnore([
                'name' => $name,
                'last_number' => $existingMaximum,
            ]);

            DB::table('document_number_sequences')
                ->where('name', $name)
                ->where('last_number', '<', $existingMaximum)
                ->update(['last_number' => $existingMaximum]);

            DB::table('document_number_sequences')->where('name', $name)->increment('last_number');

            return (int) DB::table('document_number_sequences')->where('name', $name)->value('last_number');
        });
    }
}
