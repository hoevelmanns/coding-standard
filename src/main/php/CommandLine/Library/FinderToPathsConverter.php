<?php

namespace Zooroyal\CodingStandard\CommandLine\Library;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FinderToPathsConverter
{
    /**
     * Converts Finder objects to Arrays of their relative paths.
     *
     * @param Finder $finder
     *
     * @return string[]
     */
    public function finderToArrayOfPaths(Finder $finder) : array
    {
        $result = array_map(
            function ($value) {
                /** @var SplFileInfo $value */
                return $value->getRelativePathname();
            },
            iterator_to_array($finder)
        );

        return array_values($result);
    }
}
