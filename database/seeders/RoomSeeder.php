<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\School;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $roomTypes = ['classroom', 'laboratory', 'library', 'hall', 'computer_lab', 'science_lab'];
        
        foreach (School::all() as $school) {
            // Create classrooms
            for ($i = 1; $i <= 20; $i++) {
                Room::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'name' => "Room {$i}",
                    ],
                    [
                        'code' => "R{$i}",
                        'type' => 'classroom',
                        'capacity' => rand(30, 45),
                        'location' => "Building " . chr(65 + ($i % 3)) . ", Floor " . (($i % 3) + 1),
                        'is_active' => true,
                    ]
                );
            }

            // Create special rooms
            $specialRooms = [
                ['name' => 'Science Laboratory', 'code' => 'LAB-SCI', 'type' => 'laboratory', 'capacity' => 30],
                ['name' => 'Computer Laboratory', 'code' => 'LAB-COM', 'type' => 'computer_lab', 'capacity' => 25],
                ['name' => 'Library', 'code' => 'LIB', 'type' => 'library', 'capacity' => 50],
                ['name' => 'Assembly Hall', 'code' => 'HALL', 'type' => 'hall', 'capacity' => 200],
            ];

            foreach ($specialRooms as $room) {
                Room::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'name' => $room['name'],
                    ],
                    [
                        'code' => $room['code'],
                        'type' => $room['type'],
                        'capacity' => $room['capacity'],
                        'location' => "Main Building",
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
