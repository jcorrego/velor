<?php

return [
    'tesseract_path' => env('TESSERACT_PATH', 'tesseract'),
    'language' => env('TESSERACT_LANGUAGE', 'eng'),
    'pdftoppm_path' => env('PDFTOPPM_PATH', 'pdftoppm'),
    'temp_path' => env('OCR_TEMP_PATH', sys_get_temp_dir()),
    'log_output' => env('OCR_LOG_OUTPUT', false),
    'dpi' => env('OCR_PDF_DPI', 200),
];
