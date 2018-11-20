<?php namespace Bolt\Extension\Thirdwave\Export\Controller;

use Bolt\Content;
use Bolt\Controller\Base;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class ExportController extends Base
{


    /**
     * @var Extension
     */
//    protected $extension;

    /**
     * @param ControllerCollection $c
     */
//    public function __construct(Extension $extension)
//    {
//        $this->extension = $extension;
//    }

    /**
     * {@inheritdoc}
     */
    protected function addRoutes(ControllerCollection $c)
    {
        $c->get('/', array($this, 'export'))->bind('export');
        $c->post('/download', array($this, 'download'))->bind('export_download');

        // Check for permissions before executing the controller functions.
        //$c->before(array($this, 'checkPermissions'));
        
        return $c;
    }


    /**
     * @param Application $app
     * @param Request     $request
     * @return string
     */
    public function export(Application $app, Request $request)
    {
        $contenttype = null;
        if ($request->query->has('contenttype')) {
            $contenttype = $app['config']->get('contenttypes/' . $request->query->get('contenttype'));

            $this->expandRelations($contenttype, $app);
        }

        return $app['render']->render('@export/index.twig', array(
          'contenttypes' => $app['config']->get('contenttypes'),
          'contenttype'  => $contenttype,
          'base_columns' => array_flip(Content::getBaseColumns())
        ));
    }


    /**
     * @param Application $app
     * @param Request     $request
     * @return Response
     */
    public function download(Application $app, Request $request)
    {
        $profile  = $request->request->get('export');
        $filename = !empty($profile['file']) ? $profile['file'] . '.csv' : date('Ymd') . '-' . $profile['contenttype'] . '.csv';
        $query    = new ExportQuery($app['db'], $app['storage']);
        $rows     = $query->profile($profile)->results();

        if (isset($profile['header'])) {
            array_unshift($rows, $profile['fields']);
        }

        $csv = '';

        foreach ($rows as $row) {
            $csv .= '"' . implode('"' . "\t" . '"', $row) . '"' . "\n";
        }

        $csv = mb_convert_encoding($csv, 'UTF-16LE', 'UTF-8');

        return new Response(chr(255) . chr(254) . $csv, 200, array(
          'Content-Description'       => 'File Transfer',
          'Content-Type'              => 'application/vnd.ms-exce',
          'Content-Disposition'       => 'attachment; filename=' . $filename,
          'Content-Transfer-Encoding' => 'binary',
          'Expires'                   => 0,
          'Cache-Control'             => 'must-revalidate, post-check=0, pre-check=0',
          'Pragma'                    => 'public',
          'Content-Length'            => strlen($csv)
        ));
    }


    /**
     * @param array       $contenttype
     * @param Application $app
     */
    protected function expandRelations(array &$contenttype, Application $app) {
        if ( empty($contenttype['relations']) ) {
            return;
        }

        foreach ( $contenttype['relations'] as $relation => &$properties ) {
            $items = $app['storage']->getContent($relation);

            $properties['values'] = [];
            foreach ( $items as $item ) {
                $properties['values'][$item->values['id']] = $item->getTitle();
            }
        }
    }
}