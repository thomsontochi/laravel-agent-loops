<?php

use Developia\AgentLoops\Tests\TestCase;

// Every test in the tests/ folder runs inside the Testbench Laravel app.
pest()->extend(TestCase::class)->in(__DIR__);
