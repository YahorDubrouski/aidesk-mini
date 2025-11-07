<?php

declare(strict_types=1);

namespace App\Utils;

final readonly class VectorMath
{
    public static function cosineSimilarity(array $vectorA, array $vectorB): float
    {
        if (count($vectorA) !== count($vectorB)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($vectorA as $i => $valueA) {
            $valueB = $vectorB[$i];
            $dotProduct += $valueA * $valueB;
            $normA += $valueA * $valueA;
            $normB += $valueB * $valueB;
        }

        if ($normA === 0.0 || $normB === 0.0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}
