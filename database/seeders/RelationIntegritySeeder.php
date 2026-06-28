<?php

namespace Database\Seeders;

use App\Models\School;
use Database\Seeders\Helpers\SeederRelations;
use Illuminate\Database\Seeder;

/**
 * Backfills and validates relational data used by searchable form/list filters.
 */
class RelationIntegritySeeder extends Seeder
{
    public function run(): void
    {
        foreach (School::all() as $school) {
            SeederRelations::syncClassGradeLevels($school);
            SeederRelations::syncStudentGradeLevels($school);
            SeederRelations::linkDemoParentAccount($school);
        }
    }
}
