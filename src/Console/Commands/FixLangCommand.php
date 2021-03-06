<?php

namespace Translation\Console\Commands;

use File;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Translation\Repositories\LangResourcesRepository;

class FixLangCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'translation:fixfiles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Call fire function
     *
     * @return void
     */
    public function handle()
    {
        $this->fire();
    }
    
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $repository = app(LangResourcesRepository::class);



        $languagesOptions = $repository->getMissingForLang('pt');

        dd($languagesOptions);
        // // Create $outputPath or empty it if already exists
        // if (File::isDirectory($outputPath)) {
        //     File::cleanDirectory($outputPath);
        // } else {
        //     File::makeDirectory($outputPath);
        // }

        // // Configure BladeCompiler to use our own custom storage subfolder
        // $compiler = new BladeCompiler(new Filesystem, $outputPath);
        // $compiled = 0;

        // // Get all view files
        // $allFiles = File::allFiles($inputPath);
        // foreach ($allFiles as $f)
        // {
        //     // Skip not blade templates
        //     $file = $f->getPathName();
        //     if ('.blade.php' !== substr($file, -10)) {
        //         continue;
        //     }

        //     // Compile the view
        //     $compiler->compile($file);
        //     $compiled++;

        //     // Rename to human friendly
        //     $human = str_replace(DIRECTORY_SEPARATOR, '-', ltrim($f->getRelativePathname(), DIRECTORY_SEPARATOR));
        //     File::move($outputPath . DIRECTORY_SEPARATOR . md5($file), $outputPath . DIRECTORY_SEPARATOR . $human . '.php');
        // }

        // if ($compiled) {
        //     $this->info("$compiled files compiled.");
        // } else {
        //     $this->error('No .blade.php files found in '.$inputPath);
        // }
    }
}
