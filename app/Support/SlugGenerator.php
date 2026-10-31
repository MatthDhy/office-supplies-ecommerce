<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SlugGenerator
{
    /**
     * Sinh slug DUY NHẤT trong bảng $table (tính cả bản ghi đã soft delete, vì UNIQUE index vẫn giữ chúng).
     * Trùng thì thêm hậu tố: but-viet, but-viet-2, but-viet-3...
     */
    public static function unique(string $table, string $base, ?int $ignoreId = null, int $max = 180): string
    {
        $root = rtrim(Str::limit(Str::slug($base), $max - 8, ''), '-') ?: 'item';
        $slug = $root;
        $i    = 2;

        while (DB::table($table)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $root . '-' . $i++;
        }

        return $slug;
    }
}
