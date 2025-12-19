<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Command\Support;

use Symfony\Component\Console\Input\InputInterface;

final class RetryParameterExtractor
{
    /**
     * @return array{
     *     id: string,
     *     all: bool,
     *     batch: bool,
     *     limit: int,
     *     dryRun: bool,
     *     requestTask: string
     * }
     */
    public function extract(InputInterface $input): array
    {
        return [
            'id' => $this->extractStringArgument($input, 'id'),
            'all' => (bool) $input->getOption('all'),
            'batch' => (bool) $input->getOption('batch'),
            'limit' => $this->extractNumericOption($input, 'limit', 10),
            'dryRun' => (bool) $input->getOption('dry-run'),
            'requestTask' => $this->extractStringOption($input, 'request-task'),
        ];
    }

    private function extractStringArgument(InputInterface $input, string $name): string
    {
        return is_string($input->getArgument($name)) ? $input->getArgument($name) : '';
    }

    private function extractStringOption(InputInterface $input, string $name): string
    {
        return is_string($input->getOption($name)) ? $input->getOption($name) : '';
    }

    private function extractNumericOption(InputInterface $input, string $name, int $default): int
    {
        return is_numeric($input->getOption($name)) ? (int) $input->getOption($name) : $default;
    }
}
