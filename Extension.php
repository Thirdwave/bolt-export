<?php namespace Bolt\Extension\Thirdwave\Export;

use Bolt\BaseExtension;


/**
 * Class Extension
 *
 * @author  G.P. Gautier <ggautier@thirdwave.nl>
 * @version 0.5.0, 2016/06/08
 */
class Extension extends BaseExtension
{

  
    public function getName()
    {
        return 'Export';
    }


    public function initialize() {
        $path = $this->app['config']->get('general/branding/path');

        $this->app->mount($path, new Controller());
    }
}