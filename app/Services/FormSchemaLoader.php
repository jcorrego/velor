<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class FormSchemaLoader
{
    /**
     * Load the schema for the given form and year.
     *
     * @return array<string, mixed>|null
     */
    public function load(string $formCode, int $year): ?array
    {
        $path = $this->path($formCode, $year);

        if (! File::exists($path)) {
            return null;
        }

        $contents = File::get($path);

        return json_decode($contents, true);
    }

    private function path(string $formCode, int $year): string
    {
        return resource_path("forms/{$formCode}/{$formCode}-{$year}.json");
    }
}
