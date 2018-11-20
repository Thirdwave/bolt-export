<?php

namespace Bolt\Extension\Thirdwave\Export;

use Bolt\Asset\File\JavaScript;
use Bolt\Asset\File\Stylesheet;
use Bolt\Controller\Zone;
use Bolt\Extension\SimpleExtension;
use Bolt\Extension\Thirdwave\Export\Controller\ExportController;
use Bolt\Extensions\Snippets\Location;
use Bolt\Library;
use Bolt\Menu\MenuEntry;
use Bolt\Translation\Translator;
use DirectoryIterator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Translation\Loader\YamlFileLoader;

class Extension extends SimpleExtension
{


    /**
     * {@inheritdoc}
     */
    protected function registerTwigPaths()
    {
        return [
            'templates' => ['namespace' => 'export']
        ];
    }


    /**
     * {@inheritdoc}
     */
    protected function getDefaultConfig()
    {
        return [
            'permissions' => []
        ];
    }


    /**
     * {@inheritdoc}
     */
    protected function registerAssets()
    {
        return [
            Stylesheet::create()
                ->setFileName('export.css')
                ->setLate(true)
                ->setPriority(5)
                ->setZone(Zone::BACKEND),

            JavaScript::create()
                ->setFileName('export.js')
                ->setLate(true)
                ->setPriority(5)
                ->setZone(Zone::BACKEND)
        ];
    }


    /**
     * {@inheritdoc}
     */
    protected function registerBackendControllers()
    {
        return [
            '/export' => new ExportController(),
        ];
    }


    /**
     * {@inheritdoc}
     */
    protected function registerMenuEntries()
    {
        $menu = MenuEntry::create('export-menu', 'export')
            //->setLabel(Translator::__('Export content', [], 'messages', 'nl'))
            ->setLabel(Translator::__('Export content'))
            ->setIcon('fa:file-excel-o')
            ->setPermission('settings')
            ->setRoute('export');

        return [
            $menu,
        ];
    }


    /**
     * Initializes the extension.
     */
    public function initialize()
    {
        $config = $this->getConfig();

        // Only initialize the extension for backend.
        if ($this->app['config']->getWhichEnd() !== 'backend') {
            return;
        }

        $this->app['twig.loader.filesystem']->addPath(__DIR__ . '/views', 'export');

        // When no permissions are defined in the configuration file, add default
        // roles that should have access to the export functions. When the roles
        // are defined, check if role 'root' is present and add it if absent.
        if (!isset($this->config['permissions']) || !is_array($this->config['permissions'])) {
            $this->config['permissions'] = ['root', 'admin', 'developer'];
        } else {
            if (!in_array('root', $this->config['permissions'])) {
                $this->config['permissions'][] = 'root';
            }
        }

        $this->addLocale();

        $path = $this->app['config']->get('general/branding/path');

        $this->app->mount($path, new Controller($this));

        // The URL Generator is not able to resolve a route by name
        // at this point.
        // @todo Fix not able to use URL Generator
        // $this->addMenuOption(Translator::__('Export content'), $this->app['url_generator']->generate('export'));

        // Special case for when Bolt is installed in a folder and
        // is not bootstrapped through the regular file. Note that
        // this should not be applicable to your setup.
        if ($path === '/') {
            $path = '/cms';
        }

        $path = rtrim($path, '/');

        $this->addMenuOption(Translator::__('Export content'), $path . '/export');

        $this->addAssets();
    }


    /**
     * Adds locale files.
     */
    protected function addLocale()
    {
        $folder = __DIR__ . '/locales/' . substr($this->app['locale'], 0, 2);

        if (is_dir($folder)) {
            $iterator = new DirectoryIterator($folder);

            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isFile()) {
                    $this->app['translator']->addLoader('yml', new YamlFileLoader());
                    $this->app['translator']->addResource('yml', $fileInfo->getRealPath(), $this->app['locale']);
                }
            }
        }
    }


    /**
     * Adds the JavaScript and CSS files as snippets in the response.
     */
    protected function addAssets()
    {
        $this->app['htmlsnippets'] = true;

        $basePath = $this->app['paths']['root'] . 'extensions/vendor/thirdwave/bolt-export/assets';

        $this->addSnippet(
            Location::END_OF_HEAD,
            '<script src="' . $basePath . '/export.js"></script>'
        );

        $this->addSnippet(
            Location::END_OF_HEAD,
            '<link rel="stylesheet" href="' . $basePath . '/export.css" media="screen">'
        );
    }


    /**
     * Checks if the current user has a role that has permission
     * to access the export functions. When not the user is sent
     * back to the dashboard with a notification.
     *
     * @return RedirectResponse|void
     */
    public function checkPermissions()
    {
        $user = $this->app['users']->getCurrentUser();
        $id   = $user['id'];

        foreach ($this->config['permissions'] as $role) {
            if ($this->app['users']->hasRole($id, $role)) {
                return;
            }
        }

        $this->app['session']->getFlashBag()->add(
            'error',
            Translator::__('You do not have the right privileges to view that page.')
        );

        return Library::redirect('dashboard');
    }
}