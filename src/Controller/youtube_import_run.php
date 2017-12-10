<?php

namespace Drupal\youtube_import\Controller;

use Drupal\Core\Controller\ControllerBase;

class youtube_import_run extends ControllerBase
{

    /**
     * A menu location to call the run.
     */
    function youtube_import_run_now()
    {
        // All this does is trigger the run from a url.
        youtube_import_videos();
        // Sedirect to somewhere.
        drupal_goto('admin/content/youtube-import');
    }
}