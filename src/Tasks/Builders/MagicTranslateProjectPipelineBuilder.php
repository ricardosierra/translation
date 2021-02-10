<?php

namespace Translation\Tasks\Builders;

use League\Pipeline\Pipeline;
use Operador\Contracts\StageInterface;

use Operador\Contracts\PipelineBuilder;

use Translation\Tasks\Pipelines\MagicTranslateProjectPipeline;

class MagicTranslateProjectPipelineBuilder extends PipelineBuilder
{
    public static function getPipelineWithOutput($output)
    {
        $builder = self::makeWithOutput($output);
        $builder
            ->add(MagicTranslateProjectPipeline::makeWithOutput($builder->getOutput()));

        return $builder->build();
    }
}