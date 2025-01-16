<?php

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeService extends GeneratorCommand
{
    protected $name = 'make:service';

    protected $description = 'Command description';

    protected $type = 'Service';

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Services';
    }

    protected function replaceClass($stub, $name): array|string
    {
        $stub = parent::replaceClass($stub, $name);

        return str_replace('dummy:name', $this->option('name') . 'Service', $stub);
    }

    protected function getStub(): string
    {
        return __DIR__.'/stubs/service.stub';
    }

    protected function getOptions(): array
    {
        return [
            ['name', null, InputOption::VALUE_OPTIONAL, 'Наименование сервиса.'],
        ];
    }
}
