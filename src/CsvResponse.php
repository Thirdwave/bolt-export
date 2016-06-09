<?php namespace Bolt\Extension\Thirdwave\Export;

use Symfony\Component\HttpFoundation\Response;


/**
 * Class CsvResponse
 *
 * @author  G.P. Gautier <ggautier@thirdwave.nl>
 * @version 0.5.0, 2016/06/09
 */
class CsvResponse extends Response
{
    public function __construct($rows, $filename)
    {
        $content = chr(255) . chr(254);

        foreach ($rows as $row) {
            $content .= '"' . implode('"' . "\t" . '"', $row) . '"' . "\n";
        }

        $content = mb_convert_encoding($content, 'UTF-16LE', 'UTF-8');

        parent::__construct($content, 200, array(
          'Content-Description'       => 'File Transfer',
          'Content-Type'              => 'application/vnd.ms-exce',
          'Content-Disposition'       => 'attachment; filename=' . $filename,
          'Content-Transfer-Encoding' => 'binary',
          'Expires'                   => 0,
          'Cache-Control'             => 'must-revalidate, post-check=0, pre-check=0',
          'Pragma'                    => 'public',
          'Content-Length'            => strlen($content)
        ));
    }
}