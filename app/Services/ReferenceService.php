<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReferenceService
{
    /**
     * Step 1: Base generator
     */
    private function generateReference(string $prefix = 'REF'): string
    {
        return $prefix . '-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(5));
    }

    /**
     * Step 2: Collision-proof wrapper (dynamic for any model/column)
     */
    private function createUniqueReference(string $modelClass, string $column = 'reference', string $prefix = 'REF'): string
    {
        do {
            $reference = $this->generateReference($prefix);
        } while ($modelClass::where($column, $reference)->exists());

        return $reference;
    }

    /**
     * Step 3: Public method to be used in controllers
     */
    public function getReference(string $modelClass, string $column = 'reference', string $prefix = 'REF'): string
    {
        return $this->createUniqueReference($modelClass, $column, $prefix);
    }
}
