<?php

namespace Bolt\Extension\Thirdwave\Export;

use Bolt\Asset\File\JavaScript;
use Bolt\Asset\File\Stylesheet;
use Bolt\Controller\Zone;
use Bolt\Extension\SimpleExtension;
use Bolt\Extension\Thirdwave\Export\Controller\ExportController;
use Bolt\Menu\MenuEntry;
use Bolt\Translation\Translator;
use Elvin\BoltBridge\Application;

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
        // @todo: Fix permissions. They are not checked now.
        return [
            'permissions' => ['root', 'admin', 'developer']
        ];
    }


    /**
     * {@inheritdoc}
     */
    protected function registerAssets()
    {
        // @todo: Fix url when using extension within Elvin. This is a workaround.
        if (class_exists(Application::class)) {
            return [
                Stylesheet::create('/cms/public/extensions/vendor/thirdwave/bolt-export/export.css')
                    ->setZone(Zone::BACKEND),
                JavaScript::create('/cms/public/extensions/vendor/thirdwave/bolt-export/export.js')
                    ->setZone(Zone::BACKEND),
            ];
        }

        return [
            Stylesheet::create('export.css')->setZone(Zone::BACKEND),
            JavaScript::create('export.js')->setZone(Zone::BACKEND),
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
        // @todo: Translations are not working yet.
        return [
            MenuEntry::create('export-menu', 'export')
                //->setLabel(Translator::__('Export content', [], 'messages', 'nl'))
                ->setLabel(Translator::__('Export content'))
                ->setIcon('fa:file-excel-o')
                ->setPermission('chief-editor')
                ->setRoute('export')
        ];
    }
}
