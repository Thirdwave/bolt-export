<?php namespace Bolt\Extension\Thirdwave\Export\Controller;

use Bolt\Content;
use Bolt\Controller\Base;
use Bolt\Extension\Thirdwave\Export\Storage\ExportQuery;
use Doctrine\DBAL\DBALException;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class ExportController extends Base
{


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
     * @throws DBALException
     */
    public function download(Application $app, Request $request)
    {
        $profile  = $request->request->get('export');
        $filename = !empty($profile['file']) ? $profile['file'] . '.csv' : date('Ymd') . '-' . $profile['contenttype'] . '.csv';
        $query    = new ExportQuery($app['db'], $app['storage.legacy']);
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
}