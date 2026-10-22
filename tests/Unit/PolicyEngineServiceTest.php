<?php

namespace Tests\Unit;

use App\Models\PolicyRule;
use App\Models\School;
use App\Services\Platform\PolicyEngineService;
use Tests\TestCase;

class PolicyEngineServiceTest extends TestCase
{
    public function test_stop_on_match_prevents_later_rules_from_running(): void
    {
        $school = School::factory()->create();

        PolicyRule::query()->create([
            'school_id' => $school->id,
            'code' => 'block-first',
            'name' => 'Block first',
            'module' => 'finance',
            'trigger_event' => 'invoice.created',
            'conditions' => [],
            'actions' => [['type' => 'block', 'reason' => 'Stopped']],
            'priority' => 10,
            'is_active' => true,
            'stop_on_match' => true,
        ]);

        PolicyRule::query()->create([
            'school_id' => $school->id,
            'code' => 'notify-second',
            'name' => 'Notify second',
            'module' => 'finance',
            'trigger_event' => 'invoice.created',
            'conditions' => [],
            'actions' => [['type' => 'notify_role', 'role' => 'admin', 'message' => 'Should not run']],
            'priority' => 20,
            'is_active' => true,
            'stop_on_match' => false,
        ]);

        $results = app(PolicyEngineService::class)->evaluate(
            $school->id,
            'invoice.created',
            [],
        );

        $this->assertCount(1, $results);
        $this->assertSame('block-first', $results[0]['rule_code']);
    }

    public function test_rules_continue_when_stop_on_match_is_false(): void
    {
        $school = School::factory()->create();

        PolicyRule::query()->create([
            'school_id' => $school->id,
            'code' => 'flag',
            'name' => 'Flag',
            'module' => 'finance',
            'trigger_event' => 'invoice.created',
            'conditions' => [],
            'actions' => [['type' => 'set_flag', 'key' => 'reviewed', 'value' => true]],
            'priority' => 10,
            'is_active' => true,
            'stop_on_match' => false,
        ]);

        PolicyRule::query()->create([
            'school_id' => $school->id,
            'code' => 'notify',
            'name' => 'Notify',
            'module' => 'finance',
            'trigger_event' => 'invoice.created',
            'conditions' => [],
            'actions' => [['type' => 'notify_role', 'role' => 'admin']],
            'priority' => 20,
            'is_active' => true,
            'stop_on_match' => false,
        ]);

        $results = app(PolicyEngineService::class)->evaluate(
            $school->id,
            'invoice.created',
            [],
        );

        $this->assertCount(2, $results);
    }
}
