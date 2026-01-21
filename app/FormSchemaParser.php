<?php

namespace App;

class FormSchemaParser
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $contents): array
    {
        $sections = [];
        $matches = [];

        preg_match_all('/^##\s+(.*)$/m', $contents, $matches, PREG_OFFSET_CAPTURE);

        $count = count($matches[0]);
        for ($index = 0; $index < $count; $index++) {
            $title = trim($matches[1][$index][0]);
            $start = $matches[0][$index][1] + strlen($matches[0][$index][0]);
            $end = $index + 1 < $count ? $matches[0][$index + 1][1] : strlen($contents);
            $body = trim(substr($contents, $start, $end - $start));

            $summary = [];
            $bullets = [];

            foreach (preg_split('/\r?\n/', $body) as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                if (str_starts_with($line, '- ')) {
                    $bullets[] = trim(substr($line, 2));

                    continue;
                }

                $summary[] = $line;
            }

            $sections[] = [
                'key' => \Illuminate\Support\Str::slug($title),
                'title' => $title,
                'summary' => $summary,
                'bullets' => $bullets,
                'fields' => [],
            ];
        }

        return $sections;
    }
}
