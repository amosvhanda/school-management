<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateSchoolIsolation
{
    /**
     * Ensure all requests respect school isolation
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (! $user || $user->role === UserRole::SuperAdmin) {
            return $next($request);
        }

        // Ensure user has a school_id
        if (!$user->school_id) {
            return response()->json([
                'message' => 'User must be associated with a school'
            ], 403);
        }

        // Validate school_id in request data matches user's school
        $schoolIdFields = ['school_id', 'student.school_id', 'teacher.school_id', 'class.school_id'];
        
        foreach ($schoolIdFields as $field) {
            if ($request->has($field) && $request->input($field) != $user->school_id) {
                return response()->json([
                    'message' => 'Cannot access resources from another school'
                ], 403);
            }
        }

        return $next($request);
    }
}
