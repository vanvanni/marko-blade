<?php

declare(strict_types=1);

namespace Marko\View\Blade\Tests;

use Illuminate\View\ViewException;
use Marko\Config\ConfigRepository;
use Marko\Config\ConfigRepositoryInterface;
use Marko\Core\Container\Container as MarkoContainer;
use Marko\Core\Path\ProjectPaths;

class ViteDirectiveTest extends BladeTestCase
{
    private function markoContainer(bool $useDevServer = false): MarkoContainer
    {
        $config = new ConfigRepository([
            'vite' => [
                'entry' => 'resources/js/app.js',
                'buildDirectory' => 'build',
                'manifestFilename' => '.vite/manifest.json',
                'devServerUrl' => 'http://localhost:5173',
                'devServerStylesheets' => [],
                'useDevServer' => $useDevServer,
            ],
        ]);

        $marko = new MarkoContainer();
        $marko->instance(ConfigRepositoryInterface::class, $config);
        $marko->instance(ProjectPaths::class, new ProjectPaths($this->viewsDir));

        return $marko;
    }

    private function writeManifest(): void
    {
        $dir = $this->viewsDir . '/public/build/.vite';
        mkdir($dir, 0777, true);

        file_put_contents($dir . '/manifest.json', json_encode([
            'resources/js/app.js' => ['file' => 'assets/app-1.js', 'css' => ['assets/app-1.css'], 'imports' => ['_shared.js']],
            '_shared.js' => ['file' => 'assets/shared-2.js'],
            'resources/css/app.css' => ['file' => 'assets/app-3.css'],
        ]));
    }

    private function render(string $template, ?MarkoContainer $marko = null): string
    {
        $this->writeView('post/page', $template);

        return trim($this->createFactory(markoContainer: $marko ?? $this->markoContainer())->make('blog::post/page')->render());
    }

    public function testViteDirectiveRendersManifestTagsForEntry(): void
    {
        $this->writeManifest();

        $html = $this->render("@vite('resources/js/app.js')");

        $this->assertStringContainsString('<link rel="stylesheet" href="/build/assets/app-1.css">', $html);
        $this->assertStringContainsString('<script type="module" src="/build/assets/app-1.js"></script>', $html);
        $this->assertStringContainsString('<link rel="modulepreload" href="/build/assets/shared-2.js">', $html);
    }

    public function testViteDirectiveAcceptsAnArrayOfEntries(): void
    {
        $this->writeManifest();

        $html = $this->render("@vite(['resources/css/app.css', 'resources/js/app.js'])");

        $this->assertStringContainsString('<link rel="stylesheet" href="/build/assets/app-3.css">', $html);
        $this->assertStringContainsString('<script type="module" src="/build/assets/app-1.js"></script>', $html);
    }

    public function testViteHeadTagsFallsBackToConfiguredEntry(): void
    {
        $this->writeManifest();

        $html = $this->render('@viteHeadTags');

        $this->assertStringContainsString('<script type="module" src="/build/assets/app-1.js"></script>', $html);
    }

    public function testViteDevServerModeEmitsDevServerTags(): void
    {
        $html = $this->render("@vite('resources/js/app.js')", $this->markoContainer(useDevServer: true));

        $this->assertStringContainsString('<script type="module" src="http://localhost:5173/@vite/client"></script>', $html);
        $this->assertStringContainsString('<script type="module" src="http://localhost:5173/resources/js/app.js"></script>', $html);
    }

    public function testViteReactRefreshRendersNothing(): void
    {
        $this->assertSame('', $this->render('@viteReactRefresh'));
    }

    public function testMissingManifestFailsWithMarkoMessage(): void
    {
        $this->expectException(ViewException::class);
        $this->expectExceptionMessage('Vite manifest not found');

        $this->render("@vite('resources/js/app.js')");
    }
}
