<?php

namespace Tests\Unit;

use App\Facades\MisDoctor;
use Illuminate\Foundation\Testing\TestCase;

class DoctorFacadeTest extends TestCase
{
    public function test_get_base_query(): void
    {
        $relations = ['oms_PRVS'];

        $doctorFacade = resolve(MisDoctor::class);

        $builder = $doctorFacade::getBaseBuilder($relations);

        $this->assertNotEmpty($builder->joins);
    }
}
