<?php

namespace Zooroyal\CodingStandard\CommandLine\Commands\StaticCodeAnalysis;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zooroyal\CodingStandard\CommandLine\ToolAdapters\PHPParallelLintAdapter;

class PHPParallelLintCommand extends Command
{
    /** @var PHPParallelLintAdapter */
    private $toolAdapter;

    /**
     * PHPParallelLintCommand constructor.
     *
     * @param PHPParallelLintAdapter $toolAdapter
     */
    public function __construct(PHPParallelLintAdapter $toolAdapter)
    {
        $this->toolAdapter = $toolAdapter;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('sca:parallel-lint');
        $this->setDescription('Run Parallel-Lint on PHP files.');
        $this->setHelp(
            'This tool executes Parallel-Lint on a certain set of PHP files of this project. It '
            . 'ignores files which are in directories with a .dontLintPHP file. Subdirectories are ignored too.'
        );
        $this->setDefinition(
            new InputDefinition(
                [
                    new InputOption(
                        'target',
                        't',
                        InputOption::VALUE_REQUIRED,
                        'Finds files which have changed since the current branch parted from the target branch '
                        . 'only. The value has to be a commit-ish.'
                    ),
                    new InputOption(
                        'auto-target',
                        'a',
                        InputOption::VALUE_NONE,
                        'Finds files which have changed since the current branch parted from the parent branch '
                        . 'only. It tries to find the parent branch by automagic.'
                    ),
                    new InputOption(
                        'process-isolation',
                        'p',
                        InputOption::VALUE_NONE,
                        'Runs all checks in separate processes. Slow but not as resource hungry.'
                    ),
                ]
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $targetBranch = $input->getOption('auto-target') ? null : $input->getOption('target');
        $processIsolationInput = $input->getOption('process-isolation');

        $exitCode = $this->toolAdapter->writeViolationsToOutput($targetBranch, $processIsolationInput);

        return $exitCode;
    }
}
