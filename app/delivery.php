<?php

declare(strict_types=1);

/**
 * Menghitung jarak garis lurus antara dua koordinat.
 * Hasil dalam kilometer.
 */
function delivery_distance_km(
    float $lat1,
    float $lon1,
    float $lat2,
    float $lon2
): float {
    $earthRadius = 6371.0;

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) ** 2
        + cos(deg2rad($lat1))
        * cos(deg2rad($lat2))
        * sin($dLon / 2) ** 2;

    // Mencegah kesalahan pembulatan floating-point.
    $a = max(0.0, min(1.0, $a));

    return $earthRadius * 2 * atan2(
        sqrt($a),
        sqrt(1.0 - $a)
    );
}

/**
 * Ongkir awal sebelum diskon.
 */
const DELIVERY_BASE_FEE = 2000;

/**
 * Biaya tambahan berdasarkan jarak:
 * Setiap 500 meter = Rp1.000.
 * Perhitungan dimulai dari 0 meter.
 */
function delivery_fee_from_distance(float $km): int
{
    if (!is_finite($km) || $km < 0) {
        throw new InvalidArgumentException(
            'Jarak pengantaran tidak valid.'
        );
    }

    return (int) floor(($km / 0.5) + 1e-9) * 1000;
}

