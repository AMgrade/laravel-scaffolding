<?php

declare(strict_types=1);

namespace AMgrade\Scaffolding\Console\Commands;

use AMgrade\Scaffolding\Tokenizers\RepositoryServiceProviderTokenizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use PhpParser\Node\Stmt\Use_;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;

use function array_map;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function is_dir;
use function is_readable;
use function mkdir;
use function preg_replace;
use function rtrim;
use function str_pad;
use function trim;

use const false;

class MakeRepositoryCommand extends Command
{
    protected $name = 'make:repository';

    protected $description = 'Create a new repository class';

    protected array $globalConfig;

    protected array $config;

    protected string $stubsFolder;

    protected array $paths;

    public function handle(): int
    {
        $this->globalConfig = Config::get('scaffolding.global', []);
        $this->config = Config::get('scaffolding.repository', []);
        $this->stubsFolder = $this->getStubsFolder();
        $this->paths = $this->resolvePaths();

        $this->createFactory();
        $this->createContract();
        $this->createRepositories();
        $this->createProvider();

        $this->info('Repository classes have been successfully created');

        return self::SUCCESS;
    }

    protected function resolvePaths(): array
    {
        $basePath = rtrim($this->globalConfig['paths']['repositories'], '/');
        $contractsPath = "{$basePath}/Contracts";

        $this->checkDirectory($basePath);
        $this->checkDirectory($contractsPath);

        $databasePaths = [];
        $databases = $this->config['databases'] ?: [$this->getDefaultDatabase()];

        foreach ($databases as $database) {
            $database = trim($database, '/');
            $databasePath = "{$basePath}/{$database}";

            $this->checkDirectory($databasePath);

            $databasePaths[$database] = $databasePath;
        }

        return [
            'repositories' => [
                'base' => $basePath,
                'contracts' => $contractsPath,
                'databases' => $databasePaths,
            ],
            'providers' => $this->globalConfig['paths']['providers'],
        ];
    }

    protected function getStubsFolder(): string
    {
        return rtrim($this->config['stubs'] ?? __DIR__.'/../../../stubs/repository', '/');
    }

    protected function createFactory(): void
    {
        $filename = "{$this->paths['repositories']['base']}/Factory.php";

        if (file_exists($filename)) {
            return;
        }

        $content = $this->replaceStubPlaceholders(
            $this->getStubContent('factory.stub'),
            $this->getBasePlaceholders(),
        );

        $this->putFileContent($filename, $content);
    }

    protected function createContract(): void
    {
        $name = $this->argument('name');
        $filename = "{$this->paths['repositories']['contracts']}/{$name}RepositoryContract.php";

        if (file_exists($filename)) {
            return;
        }

        $content = $this->replaceStubPlaceholders(
            $this->getStubContent('contract.stub'),
            $this->getBasePlaceholders(),
        );

        $this->putFileContent($filename, $content);
    }

    protected function createRepositories(): void
    {
        $name = $this->argument('name');

        foreach ($this->paths['repositories']['databases'] as $database => $path) {
            $filename = "{$path}/{$name}Repository.php";

            if (file_exists($filename)) {
                return;
            }

            $content = $this->replaceStubPlaceholders(
                $this->getStubContent('repository.stub'),
                $this->getBasePlaceholders() + ['DATABASE' => $database],
            );

            $this->putFileContent($filename, $content);
        }
    }

    protected function createProvider(): void
    {
        $filename = "{$this->paths['providers']}/RepositoryServiceProvider.php";

        $content = $this->replaceStubPlaceholders(
            $this->getStubContent('provider.stub'),
            $this->getProviderPlaceholders($filename),
        );

        $this->putFileContent($filename, $content);
    }

    protected function getBasePlaceholders(): array
    {
        return [
            'NAME' => $this->argument('name'),
            'PROVIDERS_NAMESPACE' => $this->globalConfig['namespaces']['providers'],
            'REPOSITORIES_NAMESPACE' => $this->globalConfig['namespaces']['repositories'],
        ];
    }

    protected function getProviderPlaceholders(string $filename): array
    {
        $name = $this->argument('name');

        $contractClass = "{$this->globalConfig['namespaces']['repositories']}\\Contracts\\{$name}RepositoryContract";
        $repositoryClass = "{$this->globalConfig['namespaces']['repositories']}\\{$this->config['default_database']}\\{$name}Repository";

        $tokenizer = new RepositoryServiceProviderTokenizer(
            file_exists($filename) && is_readable($filename)
                ? file_get_contents($filename)
                : '',
        );

        $imports = $tokenizer->getImports();
        $imports[Use_::TYPE_NORMAL][$contractClass] = $contractClass;
        $imports[Use_::TYPE_NORMAL][$repositoryClass] = $repositoryClass;
        $imports[Use_::TYPE_NORMAL][ServiceProvider::class] = ServiceProvider::class;

        $map = $tokenizer->getMap(
            $contractClass,
            $repositoryClass,
            $imports[Use_::TYPE_NORMAL],
        );

        $mapSpaces = str_pad(' ', 8);

        $map = array_map(static fn ($mapping) => "{$mapSpaces}{$mapping},", $map);

        $placeholders = $this->getBasePlaceholders();
        $placeholders['PROVIDER_IMPORTS'] = $tokenizer->getImportsAsString($imports);
        $placeholders['PROVIDER_MAP'] = implode("\n", $map);

        return $placeholders;
    }

    protected function checkDirectory(string $directory): void
    {
        if (
            !is_dir($directory) &&
            !mkdir($directory, 0755)
        ) {
            throw new RuntimeException(
                "Can't create '{$directory}' directory",
            );
        }
    }

    protected function getDefaultDatabase(): string
    {
        $map = [
            'mysql' => 'MySQL',
            'pgsql' => 'PostgreSQL',
            'sqlite' => 'SQLite',
            'sqlsrv' => 'SQLServer',
            'mongodb' => 'MongoDB',
            'dynamodb' => 'DynamoDB',
        ];

        if (!($connection = Config::get('database.default'))) {
            return $map['mysql'];
        }

        if (!($driver = Config::get("database.connections.{$connection}.driver"))) {
            return $map['mysql'];
        }

        return $map[$driver] ?? $map['mysql'];
    }

    protected function getStubContent(string $name): string
    {
        $path = "{$this->stubsFolder}/{$name}";

        if (!file_exists($path) || !is_readable($path)) {
            throw new RuntimeException(
                "Stub file '{$path}' not found or is not readable",
            );
        }

        if (false === ($content = file_get_contents($path))) {
            throw new RuntimeException("Can't get stub '{$path}' content");
        }

        return $content;
    }

    protected function replaceStubPlaceholders(
        string $content,
        array $placeholders,
    ): string {
        foreach ($placeholders as $placeholder => $replacement) {
            $content = preg_replace("~{{ {$placeholder} }}~", $replacement, $content);
        }

        return $content;
    }

    protected function putFileContent(string $filename, string $content): void
    {
        if (!file_put_contents($filename, $content)) {
            throw new RuntimeException("Can't write content into '{$filename}'");
        }
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED);
    }
}
