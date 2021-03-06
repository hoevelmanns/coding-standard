<?php

namespace Zooroyal\CodingStandard\Tests\Unit\CommandLine\ToolAdapters;

use Hamcrest\MatcherAssert;
use Hamcrest\Matchers as H;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Zooroyal\CodingStandard\CommandLine\Library\Environment;
use Zooroyal\CodingStandard\CommandLine\Library\GenericCommandRunner;
use Zooroyal\CodingStandard\CommandLine\ToolAdapters\FixerSupportInterface;
use Zooroyal\CodingStandard\CommandLine\ToolAdapters\JSESLintAdapter;
use Zooroyal\CodingStandard\CommandLine\ToolAdapters\ToolAdapterInterface;

/**
 * Class JSESLintAdapterTest
 */
class JSESLintAdapterTest extends TestCase
{
    /** @var MockInterface|Environment */
    private $mockedEnvironment;
    /** @var MockInterface|GenericCommandRunner */
    private $mockedGenericCommandRunner;
    /** @var MockInterface|OutputInterface */
    private $mockedOutputInterface;
    /** @var MockInterface|JSESLintAdapter */
    private $partialSubject;
    /** @var string */
    private $mockedPackageDirectory;
    /** @var string */
    private $mockedRootDirectory;
    /** @var string */
    private $filter = '--ext .js';
    /** @var string */
    private $mockedNodeModulesDirectory;

    protected function setUp()
    {
        $this->mockedEnvironment = Mockery::mock(Environment::class);
        $this->mockedGenericCommandRunner = Mockery::mock(GenericCommandRunner::class);
        $this->mockedOutputInterface = Mockery::mock(OutputInterface::class);

        $this->mockedPackageDirectory = '/package/directory';
        $this->mockedRootDirectory = '/root/directory';
        $this->mockedNodeModulesDirectory = $this->mockedRootDirectory . '/node_modules';

        $this->mockedEnvironment->shouldReceive('getPackageDirectory')
            ->withNoArgs()->andReturn('' . $this->mockedPackageDirectory);
        $this->mockedEnvironment->shouldReceive('getRootDirectory')
            ->withNoArgs()->andReturn($this->mockedRootDirectory);
        $this->mockedEnvironment->shouldReceive('getNodeModulesDirectory')
            ->withNoArgs()->andReturn($this->mockedNodeModulesDirectory);

        $this->partialSubject = Mockery::mock(
            JSESLintAdapter::class,
            [$this->mockedEnvironment, $this->mockedOutputInterface, $this->mockedGenericCommandRunner]
        )->shouldAllowMockingProtectedMethods()->makePartial();
    }

    protected function tearDown()
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function constructSetsUpSubjectCorrectly()
    {
        $configFile = '/config/eslint/.eslintrc.js';

        self::assertSame('.dontSniffJS', $this->partialSubject->getBlacklistToken());
        self::assertSame($this->filter, $this->partialSubject->getFilter());
        self::assertSame('--ignore-pattern=', $this->partialSubject->getBlacklistPrefix());
        self::assertSame(' ', $this->partialSubject->getBlacklistGlue());
        self::assertSame(' ', $this->partialSubject->getWhitelistGlue());

        MatcherAssert::assertThat(
            $this->partialSubject->getCommands(),
            H::allOf(
                H::hasKeyValuePair(
                    'ESLINTBL',
                    $this->mockedNodeModulesDirectory . '/.bin/eslint --config='
                    . $this->mockedPackageDirectory . $configFile . ' ' . $this->filter . ' %1$s '
                    . $this->mockedRootDirectory
                ),
                H::hasKeyValuePair(
                    'ESLINTWL',
                    $this->mockedNodeModulesDirectory . '/.bin/eslint --config='
                    . $this->mockedPackageDirectory . $configFile . ' ' . $this->filter . ' %1$s'
                ),
                H::hasKeyValuePair(
                    'ESLINTFIXBL',
                    $this->mockedNodeModulesDirectory . '/.bin/eslint --config='
                    . $this->mockedPackageDirectory . $configFile . ' ' . $this->filter . ' --fix %1$s '
                    . $this->mockedRootDirectory
                ),
                H::hasKeyValuePair(
                    'ESLINTFIXWL',
                    $this->mockedNodeModulesDirectory . '/.bin/eslint --config='
                    . $this->mockedPackageDirectory . $configFile . ' ' . $this->filter . ' --fix %1$s'
                )
            )
        );
    }

    /**
     * Data Provider for callMethodsWithParametersCallsRunToolAndReturnsResult.
     *
     * @return array
     */
    public function callMethodsWithParametersCallsRunToolAndReturnsResultDataProvider() : array
    {
        return [
            'find Violations' => [
                'tool' => 'ESLINT',
                'fullMessage' => 'ESLINT : Running full check',
                'diffMessage' => 'ESLINT : Running check on diff',
                'method' => 'writeViolationsToOutput',
                'toolResult' => 123123123,
                'expectedResult' => 123123123,
            ],
            'fix Violations' => [
                'tool' => 'ESLINTFIX',
                'fullMessage' => 'ESLINTFIX : Fix all Files',
                'diffMessage' => 'ESLINTFIX : Fix Files in diff',
                'method' => 'fixViolations',
                'toolResult' => 123123123,
                'expectedResult' => 123123123,
            ],
            'find Violations without files to lint' => [
                'tool' => 'ESLINT',
                'fullMessage' => 'ESLINT : Running full check',
                'diffMessage' => 'ESLINT : Running check on diff',
                'method' => 'writeViolationsToOutput',
                'toolResult' => 2,
                'expectedResult' => 0,
            ],
            'fix Violations  without files to lint' => [
                'tool' => 'ESLINTFIX',
                'fullMessage' => 'ESLINTFIX : Fix all Files',
                'diffMessage' => 'ESLINTFIX : Fix Files in diff',
                'method' => 'fixViolations',
                'toolResult' => 2,
                'expectedResult' => 0,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider callMethodsWithParametersCallsRunToolAndReturnsResultDataProvider
     *
     * @param string $tool
     * @param string $fullMessage
     * @param string $diffMessage
     * @param string $method
     * @param int    $toolResult
     * @param int    $expectedResult
     */
    public function callMethodsWithParametersCallsRunToolAndReturnsResult(
        string $tool,
        string $fullMessage,
        string $diffMessage,
        string $method,
        int $toolResult,
        int $expectedResult
    ) {
        $mockedProcessIsolation = true;
        $mockedTargetBranch = 'myTargetBranch';

        $this->partialSubject->shouldReceive('runTool')->once()
            ->with($mockedTargetBranch, $mockedProcessIsolation, $fullMessage, $tool, $diffMessage)
            ->andReturn($toolResult);
        $this->mockedOutputInterface->shouldReceive('write')
            ->with(H::containsString('ignore this'), true);

        $result = $this->partialSubject->$method($mockedTargetBranch, $mockedProcessIsolation);

        self::assertSame($expectedResult, $result);
    }

    /**
     * @test
     */
    public function phpCodeSnifferAdapterImplementsInterface()
    {
        self::assertInstanceOf(FixerSupportInterface::class, $this->partialSubject);
        self::assertInstanceOf(ToolAdapterInterface::class, $this->partialSubject);
    }
}
