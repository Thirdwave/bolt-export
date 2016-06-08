<?php namespace Bolt\Extension\Thirdwave\Export;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;


/**
 * Class Controller
 *
 * @author  G.P. Gautier <ggautier@thirdwave.nl>
 * @version 0.5.0, 2016/06/08
 */
class Controller implements ControllerProviderInterface
{


    /**
     * @param Application $app
     * @return ControllerCollection
     */
    public function connect(Application $app)
    {
        $routes = $app['controllers_factory'];

        $routes->match('/export', array($this, 'export'))->bind('export');
        $routes->match('/export/count', array($this, 'count'))->bind('export_count');
        $routes->match('/export/download', array($this, 'download'))->bind('export_download');

        return $routes;
    }
}