<?php

declare(strict_types=1);

namespace App\Utils;

final readonly class VectorMath
{
    /**
     * Calculate cosine similarity between two embedding vectors.
     * 
     * This function compares two lists of numbers (embeddings).
     * Think of it like comparing two fingerprints - similar patterns = similar meaning.
     * 
     * @param array $vectorA First embedding vector
     * @param array $vectorB Second embedding vector
     * @return float Similarity score between -1.0 and 1.0 (typically 0.0 to 1.0)
     */
    public static function cosineSimilarity(array $vectorA, array $vectorB): float
    {
        // Safety check: both lists must have the same length
        if (count($vectorA) !== count($vectorB)) {
            return 0.0; // Can't compare if lengths don't match
        }

        if (empty($vectorA) || empty($vectorB)) {
            return 0.0; // Can't compare if one list is empty
        }

        // We'll calculate three things:
        // 1. How much the numbers align (dot product)
        // 2. How "big" the first list is (norm A)
        // 3. How "big" the second list is (norm B)
        $dotProduct = 0.0; // Sum of: number1 * number2 for each position
        $normA = 0.0;      // Sum of: number1 * number1 (squared values)
        $normB = 0.0;      // Sum of: number2 * number2 (squared values)

        // Go through each position in both lists
        foreach ($vectorA as $i => $valueA) {
            $valueB = $vectorB[$i]; // Get the number at the same position in list B

            // Multiply numbers at same position and add to total
            // If both are positive or both negative → adds to similarity
            // If one positive, one negative → reduces similarity
            $dotProduct += $valueA * $valueB;

            // Square each number and add to its "size" total
            // This helps us normalize (make the comparison fair regardless of list size)
            $normA += $valueA * $valueA;
            $normB += $valueB * $valueB;
        }

        // Safety check: avoid division by zero
        if ($normA === 0.0 || $normB === 0.0) {
            return 0.0; // Can't calculate similarity if one list is empty
        }

        // Final calculation: divide the alignment by the "size" of both lists
        // This gives us a score between -1.0 and 1.0
        // We typically get values between 0.0 (completely different) and 1.0 (identical)
        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}
