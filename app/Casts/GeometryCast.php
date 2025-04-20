<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;


class GeometryCast implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        if (is_null($value)) {
            return null;
        }

        $result = DB::selectOne("SELECT ST_AsGeoJSON(?) as geojson", [$value]);
        return json_decode($result->geojson, true);
    }

    public function set($model, $key, $value, $attributes)
    {
        if (empty($value)) {
            throw new InvalidArgumentException("Geometri tidak boleh kosong");
        }

        try {
            // Jika berupa string, decode ke array
            if (is_string($value)) {
                $value = json_decode($value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new InvalidArgumentException("Format GeoJSON tidak valid");
                }
            }

            // Validasi struktur GeoJSON
            if (!isset($value['type']) || !isset($value['coordinates'])) {
                throw new InvalidArgumentException("Struktur GeoJSON tidak lengkap");
            }

            // Konversi ke WKB
            $result = DB::selectOne(
                "SELECT ST_GeomFromGeoJSON(?) as geom",
                [json_encode($value)]
            );

            if (empty($result->geom)) {
                throw new \RuntimeException("Gagal mengkonversi GeoJSON ke geometri");
            }

            return $result->geom;
        } catch (\Exception $e) {
            Log::error('GeometryCast Conversion Error', [
                'input' => $value,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}