<?php

return [
    'max_per_booking' => 50,
    'max_per_drop' => 10,
    'max_upload_kb' => 10240,
    'max_pixels' => 24_000_000,
    'max_image_edge' => 12000,
    'decode_bytes_per_pixel' => 12,
    'memory_reserve_bytes' => 32 * 1024 * 1024,
    'decode_memory_ceiling' => 512 * 1024 * 1024,
    'zip_max_bytes' => 100 * 1024 * 1024,
    'temp_directory' => sys_get_temp_dir(),
];
