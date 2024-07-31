<?php

namespace Javaabu\Stats\Commands;

use Illuminate\Console\Command;
use Javaabu\GeneratorHelpers\Concerns\GeneratesFiles;
use Javaabu\Stats\Generators\AbstractStatGenerator;
use Javaabu\Stats\Generators\CountStatGenerator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class GenerateTimeSeriesStatCommand extends Command
{
    protected $name = 'stats:time-series';

    protected $description = 'Generate time series stat';

    use GeneratesFiles;

    /** @return array */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name for the stat class.'],
            ['model', InputArgument::REQUIRED, 'The model class for which you want to generate the stats for. Can be the model class or morph name.']
        ];
    }

    /** @return array */
    protected function getOptions()
    {
        return [
            ['type', 't', InputOption::VALUE_REQUIRED, 'Which type of stat to generate. (Accepts count and sum).', 'count'],
            ['force', 'f', InputOption::VALUE_NONE, 'If stat gets created even if it already exists'],
            ['path', 'p', InputOption::VALUE_REQUIRED, 'Specify the path to create the files'],
        ];
    }

    public function handle(): int
    {
        // Arguments
        $name = (string) $this->argument('name');
        $model_class = (string) $this->argument('model');
        $force = (bool) $this->option('force');
        $path = (string) $this->option('path');
        $type = (string) $this->option('type');

        // create generator
        $generator = $this->getGenerator($type, $name, $model_class);

        // get the file path
        $path = $this->getPath(app_path('Stats/TimeSeries'), $path);

        $file_name = $generator->getName() . '.php';
        $file_path = $this->getFullFilePath($path, $file_name);

        $output = $generator->render();

        if ($this->putContent($file_path, $output, $force)) {
            $this->info("$file_name created!");
        }

        $this->registerStat($generator);

        return Command::SUCCESS;
    }

    protected function getGenerator(string $type, string $name, string $model_class): AbstractStatGenerator
    {
        $class = $this->getGeneratorClass($type);

        return new $class($name, $model_class);
    }


    /**
     * @return class-string<AbstractStatGenerator>
     */
    protected function getGeneratorClass(string $type): string
    {
        return match ($type) {
            'count' => CountStatGenerator::class
        };
    }

    protected function registerStat(AbstractStatGenerator $generator): void
    {
        $file_path = app_path('Providers/AppServiceProvider.php');

        $metric = $generator->getMetric();
        $class_name = $generator->getFullClassName();
        $name = $generator->getName();

        $replacements = [
            [
                'search' => "TimeSeriesStats::register([\n",
                'keep_search' => true,
                'content' => $this->getRenderer()->addIndentation("'$metric' => $class_name::class,\n", 3),
            ],
        ];

        if ($this->appendContent($file_path, $replacements)) {
            $this->info("$name stat registered!");
        }
    }
}
