<?php

if (!function_exists('msg')) {
    /**
     * Get message from MessagesHelper
     */
    function msg(string $key, array $replace = [], ?string $locale = null): string
    {
        return \App\Helpers\MessagesHelper::replace($key, $replace, $locale);
    }
}

if (!function_exists('getCompatibleBloodTypes')) {
    /**
     * Get compatible blood types for donation
     * Returns array of blood types that can donate to the given blood type
     *
     * @param string $patientBloodType
     * @return array
     */
    function getCompatibleBloodTypes(string $patientBloodType): array
    {
        $compatibilityMap = [
            'O-' => ['O-'],
            'O+' => ['O-', 'O+'],
            'A-' => ['O-', 'A-'],
            'A+' => ['O-', 'O+', 'A-', 'A+'],
            'B-' => ['O-', 'B-'],
            'B+' => ['O-', 'O+', 'B-', 'B+'],
            'AB-' => ['O-', 'A-', 'B-', 'AB-'],
            'AB+' => ['O-', 'O+', 'A-', 'A+', 'B-', 'B+', 'AB-', 'AB+'],
        ];

        return $compatibilityMap[$patientBloodType] ?? [];
    }
}

