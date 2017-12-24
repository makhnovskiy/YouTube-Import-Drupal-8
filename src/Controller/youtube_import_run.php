<?php

namespace Drupal\youtube_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

class youtube_import_run extends ControllerBase
{

    /**
     * A menu location to call the run.
     */
    function youtube_import_run_now()
    {
        // All this does is trigger the run from a url.
        youtube_import_videos();
        // Redirect to somewhere.
        //$this->redirect_me('/admin/content/youtube-import');
        $this->redirect_me('/admin/config/system/youtube_import');
    }

    function redirect_me($path)
    {
        $response = new RedirectResponse($path);
        $response->send();
        return;
    }
}