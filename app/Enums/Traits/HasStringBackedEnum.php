<?php

namespace App\Enums\Traits;

trait HasStringBackedEnum
{
    /**
     * Permite llamar al case estático por nombre y devolver su valor.
     *
     * @param string $name
     * @param array  $arguments
     * @return string
     *
     * @throws \BadMethodCallException
     */
    public static function __callStatic(string $name, array $arguments): string
    {
        foreach (self::cases() as $case) {
            if ($case->name === $name) {
                return $case->value;
            }
        }

        throw new \BadMethodCallException("No existe el case o método estático '{$name}' en " . static::class);
    }

    /**
     * Devuelve un array con todos los valores del enum.
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }
}
