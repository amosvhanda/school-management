<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            return;
        }

        $items = [
            ['title' => 'Welcome to the new academic year', 'message' => 'We welcome all students and parents. Please ensure fees are paid by end of Week 2.', 'type' => 'info', 'audience' => 'all'],
            ['title' => 'Sports day – next Friday', 'message' => 'Annual sports day next Friday. All students to participate. Parents welcome.', 'type' => 'success', 'audience' => 'all'],
            ['title' => 'Fee payment reminder', 'message' => 'Term 1 fees due soon. Contact accounts office for payment plans.', 'type' => 'warning', 'audience' => 'parents'],
            ['title' => 'Staff meeting – Tuesday 14:00', 'message' => 'All teaching staff: curriculum planning meeting in staff room.', 'type' => 'important', 'audience' => 'teachers'],
        ];

        foreach (School::all() as $school) {
            foreach ($items as $item) {
                $now = now();
                $exists = DB::table('announcements')
                    ->where('school_id', $school->id)
                    ->where('title', $item['title'])
                    ->exists();
                if ($exists) {
                    DB::table('announcements')
                        ->where('school_id', $school->id)
                        ->where('title', $item['title'])
                        ->update([
                            'message' => $item['message'],
                            'type' => $item['type'],
                            'target_audience' => $item['audience'],
                            'date' => $now->format('Y-m-d'),
                            'is_active' => true,
                            'updated_at' => $now,
                        ]);
                } else {
                    DB::table('announcements')->insert([
                        'school_id' => $school->id,
                        'title' => $item['title'],
                        'message' => $item['message'],
                        'type' => $item['type'],
                        'target_audience' => $item['audience'],
                        'date' => $now->format('Y-m-d'),
                        'is_active' => true,
                        'created_by' => $admin->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }
}
