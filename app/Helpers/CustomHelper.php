<?php


if (!function_exists('getMonitorId')) {
    /**
     * Get the monitor_id either from attributes or the authenticated user.
     *
     * @param array|null $attributes
     * @return string|null
     */
    function getMonitorId(array $attributes = null): ?string
    {
        return isset($attributes['monitor_id']) ? $attributes['monitor_id'] : auth()->user()->monitor?->id;
    }
}

if (!function_exists('getStudentId')) {
    /**
     * Get the monitor_id either from attributes or the authenticated user.
     *
     * @param array|null $attributes
     * @return string|null
     */
    function getStudentId(array $attributes = null): ?string
    {
        return isset($attributes['student_id']) ? $attributes['student_id'] : auth()->user()->student?->id;
    }
}


if (!function_exists('flashMessage')) {
    /**
     * Flash a message to the session.
     *
     * @param string $status
     * @param string|null $message
     * @return string|null
     */
    function flashMessage(string $status = 'success', string $message = null): string|null
    {
        return match ($status) {
            'success' => "L'action a été réalisée avec succès.",
            'error' => app()->environment('local') ? $message : "Une erreur est survenue lors de l'exécution de l'action.",
            'perso' => $message,
            default => 'Action terminée.',
        };
    }
}
