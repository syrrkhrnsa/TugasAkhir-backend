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

        try {
            // Handle both raw database geometry and WKT strings
            if (is_resource($value) || is_string($value)) {
                $result = DB::selectOne("SELECT ST_AsGeoJSON(?) as geojson", [$value]);
                return json_decode($result->geojson, true);
            }

            throw new InvalidArgumentException("Unsupported geometry format");
        } catch (\Exception $e) {
            Log::error('GeometryCast Get Error', [
                'error' => $e->getMessage(),
                'value' => $value
            ]);
            return null;
        }
    }

    public function set($model, $key, $value, $attributes)
    {
        if (empty($value)) {
            return null;
        }

        try {
            // If it's already a WKT string (from geojsonToWkt conversion)
            if (is_string($value) && preg_match('/^(POINT|LINESTRING|POLYGON)/i', $value)) {
                return DB::raw("ST_GeomFromText('$value', 4326)");
            }

            // Handle GeoJSON input (string or array)
            $geojson = is_string($value) ? json_decode($value, true) : $value;

            if (!is_array($geojson) || !isset($geojson['type']) || !isset($geojson['coordinates'])) {
                throw new InvalidArgumentException("Invalid GeoJSON structure");
            }

            // Convert GeoJSON to WKB
            $result = DB::selectOne(
                "SELECT ST_GeomFromGeoJSON(?) as geom",
                [json_encode($geojson)]
            );

            if (empty($result->geom)) {
                throw new \RuntimeException("Failed to convert GeoJSON to geometry");
            }

            return $result->geom;
        } catch (\Exception $e) {
            Log::error('GeometryCast Set Error', [
                'input' => $value,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}