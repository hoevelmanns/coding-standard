<?php

namespace Zooroyal\CodingStandard\Tests\System\Eslint;

use Hamcrest\MatcherAssert;
use Hamcrest\Matchers as H;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class RunStylelintWithConfig extends TestCase
{
    /**
     * @test
     */
    public function findViolationsByEslint()
    {
        $process = new Process(
            [
                __DIR__ . '/../../../node_modules/.bin/stylelint',
                '--config=' . __DIR__ . '/../../../config/stylelint/.stylelintrc',
                __DIR__ . '/../fixtures/stylelint/BadCode.less',
            ]
        );

        $process->run();
        $process->wait();
        $errorCode = $process->getExitCode();
        $output = $process->getOutput();

        self::assertSame(2, $errorCode);
        MatcherAssert::assertThat($output, H::containsString('Expected no more than 1 empty line'));
    }
}
