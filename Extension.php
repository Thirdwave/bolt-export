<?php namespace Bolt\Extension\Thirdwave\Export;

use Bolt\BaseExtension;
use Bolt\Extensions\Snippets\Location;
use Bolt\Library;
use Bolt\Translation\Translator;
use DirectoryIterator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Translation\Loader\YamlFileLoader;


/**
 * Class Extension
 *
 * @author  G.P. Gautier <ggautier@thirdwave.nl>
 * @version 0.5.0, 2016/06/08
 */
class Extension extends BaseExtension
{


    /**
     * @return string
     */
    public function getName()
    {
        return 'Export';
    }


    /**
     * Initializes the extension.
     */
    public function initialize()
    {
        // Only initialize the extension for backend.
        if ($this->app['config']->getWhichEnd() !== 'backend') {
            return;
        }

        $this->app['twig.loader.filesystem']->addPath(__DIR__ . '/views', 'export');

        // When no permissions are defined in the configuration file, add default
        // roles that should have access to the export functions. When the roles
        // are defined, check if role 'root' is present and add it if absent.
        if (!isset($this->config['permissions']) || !is_array($this->config['permissions'])) {
            $this->config['permissions'] = array('root', 'admin', 'developer');
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

        $this->addMenuOption(Translator::__('Export content'), $path . '/export');

        $this->addAssets();
    }


    /**
     * Adds locale files.
     */
    protected function addLocale() {
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