<?php

namespace Translation\Tasks\Pipelines;

use League\Pipeline\Pipeline;
use Operador\Contracts\StageInterface;

class MagicTranslateProjectPipeline implements StageInterface
{
    public function __invoke()
    {
        
    }
}

// class DatabaseMount implements StageInterface
// {


//     public function __invoke($renderDatabaseArray)
//     {
//         $eloquentClasses =  collect($renderDatabaseArray["Leitoras"]["displayClasses"]);


//         $this->entitys = $eloquentClasses->reject(function($eloquentData, $className) {
//             return $this->eloquentHasError($className);
//         })->map(function($eloquentData, $className) use ($renderDatabaseArray) {
//             return (new EloquentMount($className, $renderDatabaseArray))->getEntity();
//         });
//     }
// }
// class ReaderPipeline
// {
//     public function __invoke($eloquentClasses)
//     {

//         $pipeline = (new Pipeline)
//             ->pipe(new DatabaseRender)
//             ->pipe(new DatabaseMount);
        
//         // Returns 21
//         $entitys = $pipeline->process(10);
        
        
        
//         // Re-usable Pipelines
//         // Because the PipelineInterface is an extension of the StageInterface pipelines can be re-used as stages. This creates a highly composable model to create complex execution patterns while keeping the cognitive load low.
        
//         // For example, if we'd want to compose a pipeline to process API calls, we'd create something along these lines:
        
//         $processApiRequest = (new Pipeline)
//             ->pipe(new ExecuteHttpRequest) // 2
//             ->pipe(new ParseJsonResponse); // 3
            
//         $pipeline = (new Pipeline)
//             ->pipe(new ConvertToPsr7Request) // 1
//             ->pipe($processApiRequest) // (2,3)
//             ->pipe(new ConvertToResponseDto); // 4 
            
//         $pipeline->process(new DeleteBlogPost($postId));
//     }
// }
