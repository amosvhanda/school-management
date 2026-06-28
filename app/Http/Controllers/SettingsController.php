<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    /**
     * Get all settings grouped by category (per-school).
     */
    public function index(Request $request)
    {
        $settings = Setting::all()->groupBy('category');

        // Transform to frontend format
        $result = [
            'general' => [],
            'academic' => [],
            'notifications' => [],
            'security' => [],
            'appearance' => []
        ];

        foreach ($settings as $category => $items) {
            foreach ($items as $setting) {
                // Handle nested keys
                $key = $setting->key;
                if (strpos($key, '.') !== false) {
                    $parts = explode('.', $key, 2);
                    $category = $parts[0];
                    $key = $parts[1];
                }
                if (isset($result[$category])) {
                    $result[$category][$key] = $setting->value;
                }
            }
        }

        $school = $request->user()?->school;

        // Add default school info if not set (use actual school when available)
        if (empty($result['general'])) {
            $result['general'] = [
                'name' => $school?->name ?? 'School',
                'address' => $school?->address ?? '',
                'phone' => $school?->phone ?? '',
                'email' => $school?->email ?? '',
                'website' => '',
                'principal' => '',
                'motto' => '',
                'founded' => '',
                'studentCapacity' => 0,
                'currentEnrollment' => 0,
                'logo' => ''
            ];
        }

        // Add default academic settings if not set (use school when available)
        if (empty($result['academic'])) {
            $result['academic'] = [
                'currentTerm' => $school?->current_term ?? 'Term 1',
                'academicYear' => $school?->academic_year ?? date('Y'),
                'termStartDate' => '',
                'termEndDate' => '',
                'schoolStartTime' => '07:30',
                'schoolEndTime' => '16:30',
                'periodsPerDay' => 8,
                'periodDuration' => 40,
                'breakDuration' => 20,
                'lunchDuration' => 60
            ];
        }

        // Add default notification settings if not set
        if (empty($result['notifications'])) {
            $result['notifications'] = [
                'emailNotifications' => true,
                'smsNotifications' => false,
                'pushNotifications' => true,
                'parentNotifications' => true,
                'staffNotifications' => true,
                'feeReminders' => true,
                'attendanceAlerts' => true,
                'gradeNotifications' => true
            ];
        }

        // Add default security settings if not set
        if (empty($result['security'])) {
            $result['security'] = [
                'twoFactorAuth' => false,
                'sessionTimeout' => 60,
                'passwordExpiry' => 90,
                'loginAttempts' => 5,
                'dataBackupFrequency' => 'Daily',
                'auditLogging' => true
            ];
        }

        // Add default appearance settings if not set
        if (empty($result['appearance'])) {
            $result['appearance'] = [
                'theme' => 'light',
                'primaryColor' => '#FF7A00',
                'schoolLogo' => true,
                'customBranding' => true,
                'language' => 'English',
                'currency' => $school?->currency_default ?? 'USD',
                'dateFormat' => 'DD/MM/YYYY'
            ];
        }

        return response()->json([
            'data' => $result
        ]);
    }

    /**
     * Update settings (per-school; requires school context).
     */
    public function update(Request $request)
    {
        $user = $request->user();
        if ($user->school_id === null) {
            return response()->json([
                'message' => 'Settings require a school context. User must belong to a school to update settings.',
            ], 403);
        }

        $data = $request->all();

        DB::beginTransaction();
        try {
            // Handle both single category updates and full updates
            if (isset($data['general']) || isset($data['academic']) || isset($data['notifications']) || isset($data['security']) || isset($data['appearance'])) {
                // Full update with categories
                foreach ($data as $category => $settings) {
                    if (!is_array($settings)) continue;

                    foreach ($settings as $key => $value) {
                        $type = $this->determineType($value);
                        Setting::set("{$category}.{$key}", $value, $type, $category);
                    }
                }
            } else {
                // Single category update (e.g., just 'academic' or 'general')
                $category = $request->input('category', 'general');
                foreach ($data as $key => $value) {
                    if ($key === 'category') continue;
                    $type = $this->determineType($value);
                    Setting::set("{$category}.{$key}", $value, $type, $category);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Settings updated successfully',
                'data' => $this->index($request)->getData()->data
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get academic calendar
     */
    public function academicCalendar()
    {
        $academic = Setting::where('category', 'academic')->get();
        $result = [];
        
        foreach ($academic as $setting) {
            $result[$setting->key] = $setting->value;
        }
        
        return response()->json([
            'data' => $result
        ]);
    }

    /**
     * Update academic calendar
     */
    public function updateAcademicCalendar(Request $request)
    {
        $data = $request->all();
        
        DB::beginTransaction();
        try {
            foreach ($data as $key => $value) {
                $type = $this->determineType($value);
                Setting::set("academic.{$key}", $value, $type, 'academic');
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Academic calendar updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update academic calendar',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get config
     */
    public function config()
    {
        $settings = Setting::all();
        $result = [];
        
        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->value;
        }
        
        return response()->json([
            'data' => $result
        ]);
    }

    /**
     * Determine value type
     */
    private function determineType($value)
    {
        if (is_bool($value)) {
            return 'boolean';
        } elseif (is_numeric($value)) {
            return 'number';
        } elseif (is_array($value)) {
            return 'json';
        } else {
            return 'string';
        }
    }
}
