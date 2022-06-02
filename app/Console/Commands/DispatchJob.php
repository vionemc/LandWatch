<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

use InvalidArgumentException;

use ReflectionClass;
use ReflectionParameter;

use function array_key_exists;
use function array_map;
use function class_exists;
use function dispatch;
use function settype;

final class DispatchJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job:dispatch {job} {params?*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch job';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        /** @var string $class */
        $class = $this->argument('job');
        $defaultNamespace = '\\App\\Jobs\\';
        if (!class_exists($class)) {
            $oldClass = $class;
            $class =  $defaultNamespace . $class;
            if (!class_exists($class)) {
                throw new InvalidArgumentException("$oldClass and $class classes do not exist.");
            }
        }

        $parameters = [];
        // Cast to appropriate types if typing was used
        $parameterTypes = array_map(
            static fn (ReflectionParameter $param) => $param->hasType() ? (string) $param->getType() : null,
            (new ReflectionClass($class))->getConstructor()?->getParameters()
        );
        foreach ($this->argument('params') ?? [] as $i => $parameter) {
            if (array_key_exists($i, $parameterTypes) && $parameterTypes[$i] !== null) {
                settype($parameter, $parameterTypes[$i]);
                $parameters[] = $parameter;
            }
        }

        dispatch(new $class(...$parameters));

        return 0;
    }
}
