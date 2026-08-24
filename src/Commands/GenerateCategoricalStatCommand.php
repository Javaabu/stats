<?php

namespace Javaabu\Stats\Commands;

use Illuminate\Console\Command;
use Javaabu\GeneratorHelpers\Concerns\GeneratesFiles;
use Javaabu\Stats\Generators\AbstractCategoricalStatGenerator;
use Javaabu\Stats\Generators\CountCategoricalStatGenerator;
use Javaabu\Stats\Generators\SumCategoricalStatGenerator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class GenerateCategoricalStatCommand extends Command
{
    use GeneratesFiles;

    protected $name = 'stats:categorical';

    protected $description = 'Generate a categorical stat';

    /** @return array */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name for the stat class.'],
            ['model', InputArgument::REQUIRED, 'The model class used for the base query. Can be the model class or morph name.'],
            ['category_model', InputArgument::REQUIRED, 'The model class used as the category provider. Can be the model class or morph name.'],
            ['category_id_field', InputArgument::REQUIRED, 'The category ID field on the base query model.'],
        ];
    }

    /** @return array */
    protected function getOptions()
    {
        return [
            ['type', 't', InputOption::VALUE_REQUIRED, 'Which type of stat to generate. Accepts count and sum.', 'count'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the stat even if it already exists.'],
            ['path', 'p', InputOption::VALUE_REQUIRED, 'Specify the path to create the files.'],
        ];
    }

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        $model_class = (string) $this->argument('model');
        $category_model_class = (string) $this->argument('category_model');
        $category_field = (string) $this->argument('category_id_field');
        $force = (bool) $this->option('force');
        $path = (string) $this->option('path');
        $type = strtolower((string) $this->option('type'));

        if (! in_array($type, ['count', 'sum'], true)) {
            $this->error("Invalid categorical stat type [{$type}]. Expected count or sum.");

            return Command::FAILURE;
        }

        $generator = $this->getGenerator(
            $type,
            $name,
            $model_class,
            $category_model_class,
            $category_field
        );

        $path = $this->getPath(app_path('Stats/Categorical'), $path);
        $file_name = $generator->getName().'.php';
        $file_path = $this->getFullFilePath($path, $file_name);
        $output = $generator->render();

        if ($this->putContent($file_path, $output, $force)) {
            $this->info("{$file_name} created!");
        }

        $this->registerStat($generator);

        return Command::SUCCESS;
    }

    protected function getGenerator(
        string $type,
        string $name,
        string $model_class,
        string $category_model_class,
        string $category_field
    ): AbstractCategoricalStatGenerator {
        $class = $this->getGeneratorClass($type);

        return new $class($name, $model_class, $category_model_class, $category_field);
    }

    /**
     * @return class-string<AbstractCategoricalStatGenerator>
     */
    protected function getGeneratorClass(string $type): string
    {
        return match ($type) {
            'count' => CountCategoricalStatGenerator::class,
            'sum' => SumCategoricalStatGenerator::class,
        };
    }

    protected function registerStat(AbstractCategoricalStatGenerator $generator): void
    {
        $file_path = app_path('Providers/AppServiceProvider.php');
        $metric = $generator->getMetric();
        $class_name = $generator->getFullClassName();
        $name = $generator->getName();

        $replacements = [
            [
                'search' => "CategoricalStats::register([\n",
                'keep_search' => true,
                'content' => $this->getRenderer()->addIndentation("'{$metric}' => {$class_name}::class,\n", 3),
            ],
        ];

        if ($this->appendContent($file_path, $replacements)) {
            $this->info("{$name} stat registered!");
        }
    }
}
