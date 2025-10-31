<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Command\Support;

use Symfony\Component\Console\Input\InputInterface;

class RetryParameterValidator
{
    /**
     * @return string[]
     */
    public function validate(InputInterface $input): array
    {
        $errors = [];
        $errors = array_merge($errors, $this->validateBatchOptions($input));

        return array_merge($errors, $this->validateIdOptions($input));
    }

    /**
     * @return string[]
     */
    private function validateBatchOptions(InputInterface $input): array
    {
        $errors = [];
        $batch = (bool) $input->getOption('batch');
        $requestTask = (bool) $input->getOption('request-task');
        $id = $input->getArgument('id');
        $hasId = null !== $id && '' !== $id;

        if ($batch && $requestTask) {
            $errors[] = '不能同时指定--batch和--request-task选项';
        }

        if ($batch && !$hasId) {
            $errors[] = '使用--batch选项时必须指定RequestTask ID';
        }

        return $errors;
    }

    /**
     * @return string[]
     */
    private function validateIdOptions(InputInterface $input): array
    {
        $errors = [];
        $id = $input->getArgument('id');
        $hasId = null !== $id && '' !== $id;
        $all = (bool) $input->getOption('all');

        if ($hasId && $all) {
            $errors[] = '不能同时指定ID和--all选项';
        }

        return $errors;
    }
}
