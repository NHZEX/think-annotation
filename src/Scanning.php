<?php

namespace Zxin\Think\Annotation;

use Generator;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use think\App;

class Scanning
{
    /**
     * @var App
     */
    protected $app;

    protected $baseDir;
    protected $controllerLayer;
    protected $apps = [];

    protected $controllerNamespaces = 'app\\';

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * @return Generator
     */
    public function scanningClass(): Generator
    {
        $this->baseDir         = $this->app->getBasePath();
        $this->controllerLayer = $this->app->config->get('route.controller_layer');
        $this->apps            = [];

        $dirs   = array_map(function ($app) {
            return $this->baseDir . $app . DIRECTORY_SEPARATOR . $this->controllerLayer;
        }, $this->apps);
        $dirs[] = $this->baseDir . $this->controllerLayer . DIRECTORY_SEPARATOR;

        foreach ($this->scanningFile($dirs) as $file) {
            $class = $this->parseClassName($file);
            yield $file => $class;
        }
    }

    /**
     * @param $dirs
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
     * @param SplFileInfo $file
     * @return string
     */
    protected function parseClassName(SplFileInfo $file): string
    {
        $controllerPath = substr($file->getPath(), strlen($this->baseDir));

        $controllerPath = str_replace('/', '\\', $controllerPath);
        if (!empty($controllerPath)) {
            $controllerPath .= '\\';
        }

        $baseName = $file->getBasename(".{$file->getExtension()}");
        return $this->controllerNamespaces . $controllerPath . $baseName;
    }
}
