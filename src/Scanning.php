<?php

namespace Zxin\Think\Annotation;

use Generator;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use think\App;
use ValueError;

class Scanning
{
    protected App $app;

    protected string $baseDir;
    protected string $controllerLayer;
    /**
     * 多应用支持，目前无作用
     * @var array<string>
     */
    protected array $apps = [];

    protected string $controllerNamespaces = 'app\\';

    public function __construct(App $app, ?string $namespaces = null)
    {
        if (!empty($namespaces)) {
            if (!str_ends_with($namespaces, '\\')) {
                throw new ValueError("{$namespaces} must end with \\");
            }
            $this->controllerNamespaces = $namespaces;
        }
        $this->app = $app;
    }

    public function scanningClass(): Generator
    {
        $this->baseDir         = $this->app->getBasePath();
        $this->controllerLayer = $this->app->config->get('route.controller_layer');
        $this->apps            = [];

        $dirs   = array_map(fn ($app): string => $this->baseDir . $app . DIRECTORY_SEPARATOR . $this->controllerLayer, $this->apps);
        $dirs[] = $this->baseDir . $this->controllerLayer . DIRECTORY_SEPARATOR;

        foreach ($this->scanningFile($dirs) as $file) {
            $class = $this->parseClassName($file);
            yield $file => $class;
        }
    }

    /**
     * @param string|array<string> $dirs
     * @return Generator
     */
    protected function scanningFile($dirs): Generator
    {
        $finder = new Finder();
        $finder->files()->in($dirs)->name('*.php');
        if (!$finder->hasResults()) {
            return;
        }
        yield from $finder;
    }

    /**
     * 解析类命名（仅支持Psr4）
     */
    protected function parseClassName(SplFileInfo $file): string
    {
        $controllerPath = substr($file->getPath(), \strlen($this->baseDir));

        $controllerPath = str_replace('/', '\\', $controllerPath);
        if (!empty($controllerPath)) {
            $controllerPath .= '\\';
        }

        $baseName = $file->getBasename(".{$file->getExtension()}");
        return $this->controllerNamespaces . $controllerPath . $baseName;
    }

    public function getControllerLayer(): string
    {
        return $this->controllerLayer;
    }

    public function getControllerNamespaces(): string
    {
        return $this->controllerNamespaces;
    }

    /**
     * @return string[]
     */
    public function getApps(): array
    {
        return $this->apps;
    }
}
