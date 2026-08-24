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
        $line_ending = $this->getLineEnding($file_path);

        $replacements = [
            [
                'search' => 'CategoricalStats::register([',
                'keep_search' => true,
                'content' => $line_ending.$this->getRenderer()->addIndentation("'{$metric}' => {$class_name}::class,", 3),
            ],
        ];

        $this->appendContent($file_path, $replacements);

        if ($this->isStatRegistered($file_path, $metric, $class_name)) {
            $this->info("{$name} stat registered!");

            return;
        }

        $this->showManualRegistrationInstructions($metric, $class_name);
    }

    protected function isStatRegistered(string $file_path, string $metric, string $class_name): bool
    {
        if (! $this->getFilesystem()->exists($file_path)) {
            return false;
        }

        $contents = $this->getFilesystem()->get($file_path);
        $registration_pattern = '/CategoricalStats::register\(\s*\[(.*?)\]\s*(?:,\s*[^)]*)?\);/s';

        if (! preg_match_all($registration_pattern, $contents, $registration_matches)) {
            return false;
        }

        $expected_mapping = preg_replace('/\s+/', '', "'{$metric}' => {$class_name}::class");

        foreach ($registration_matches[1] as $registered_stats) {
            $normalized_stats = preg_replace('/\s+/', '', $registered_stats);

            if (str_contains($normalized_stats, $expected_mapping)) {
                return true;
            }
        }

        return false;
    }

    protected function getLineEnding(string $file_path): string
    {
        if ($this->getFilesystem()->exists($file_path)
            && str_contains($this->getFilesystem()->get($file_path), "\r\n")) {
            return "\r\n";
        }

        return "\n";
    }

    protected function showManualRegistrationInstructions(string $metric, string $class_name): void
    {
        $this->warn('The generated stat could not be registered automatically.');
        $this->line('Add the following import and registration call to App\\Providers\\AppServiceProvider:');
        $this->newLine();
        $this->line('use Javaabu\\Stats\\CategoricalStats;');
        $this->newLine();
        $this->line('// In the boot() method:');
        $this->line('CategoricalStats::register([');
        $this->line("    '{$metric}' => {$class_name}::class,");
        $this->line(']);');
    }
}
