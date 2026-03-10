<?php

namespace App\Support;

use App\Models\MediaAsset;

class MediaLookup
{
    /**
     * @param  array<int, int|string>  $targetIds
     * @return array<int, string>
     */
    public static function primaryUrlMap(string $targetType, array $targetIds): array
    {
        $ids = array_values(array_unique(array_map(fn ($id) => (int) $id, $targetIds)));
        if ($ids === []) {
            return [];
        }

        $assets = MediaAsset::query()
            ->where('target_type', $targetType)
            ->whereIn('target_id', $ids)
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get(['target_id', 'file_path']);

        $map = [];
        foreach ($assets as $asset) {
            if (! isset($map[$asset->target_id])) {
                $map[$asset->target_id] = MediaUrl::toUrl($asset->file_path);
            }
        }

        return $map;
    }
}

