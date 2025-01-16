<?php

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeFacade extends GeneratorCommand
{
    protected $name = 'make:facade';

    protected $description = 'Command description';

    protected $type = 'Facade';

    public function handle()
    {
        if (parent::handle() === false) {
            return false;
        }

        $name = $this->argument('name');

        $this->call('make:service', array_filter([
            'name' => "{$name}Service",
        ]));
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Facades';
    }

    protected function replaceClass($stub, $name): array|string
    {
        $stub = parent::replaceClass($stub, $name);

        return str_replace('dummy:name', $this->option('name'), $stub);
    }

    protected function getStub(): string
    {
        return __DIR__.'/stubs/facade.stub';
    }

    protected function getOptions(): array
    {
        return [
            ['name', null, InputOption::VALUE_OPTIONAL, 'Наименование фасада.'],
        ];
    }

    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::OPTIONAL, 'Наименование фасада.'],
        ];
    }
}
