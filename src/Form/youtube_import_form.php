<?php

namespace Drupal\youtube_import\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

//not sure what this is used for, does not know if will work without- need to test
use Symfony\Component\HttpFoundation\Request;

//used for getting all users 
use  \Drupal\user\Entity\User;

//used for setting links
use Drupal\Core\Url;

//entity manager
use \Drupal\Core\Entity;

/**
 * Implements the SMTP admin settings form.
 */
class youtube_import_form extends ConfigFormBase
{

    /**
     * {@inheritdoc}.
     */
    public function getFormID()
    {
        return 'youtube_import_admin_settings';
    }

    /**
     * {@inheritdoc}.
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {

        //dont need the config since savedConfig truncates. Still keeping just in case.
        $config = $this->configFactory->get('youtube_import.settings');

        $savedConfig = youtube_import_get();

        $mapping = array();

        // A flag to see if there is a youtube field.
        $has_youtube_field = FALSE;

        $apikey = $playlistid = '';
        /*
        // Create the help link html.
        $markup = t('For configuration instructions visit&nbsp;') . l(
                t('/admin/help/youtube_import'),
                '/admin/help/youtube_import',
                array('attributes' => array('target' => '_blank'))
            );
        */

        // Add the help  link to the form.
        //$form['help_link'] = array(
        //   '#markup' => "<p>{$markup}</p>",
        //);

        // Create the field for the API key.
        $form['apikey'] = array(
            '#type' => 'textfield',
            '#required' => TRUE,
            '#title' => t('YouTube API key.'),
            '#default_value' => isset($savedConfig['apikey']) ? $savedConfig['apikey'] : $config->get('apikey'),
        );

        // Create the field for the username.
        $form['username'] = array(
            '#type' => 'textfield',
            '#title' => t('YouTube user name or your channel ID'),
            '#description' => t('This value is only used to get the playlist id. If you know the playlist id, you may leave this blank but be sure to fill in one or the other'),
            '#default_value' => isset($savedConfig['username']) ? $savedConfig['username'] : $config->get('username'),
        );

        // Create the field for the playlist id.
        $form['playlistid'] = array(
            '#type' => 'textfield',
            '#title' => t('YouTube play list ID.'),
            '#description' => t('You may leave this blank if you have entered the YouTube username and it will be automatically updated to the "uploads" playlist of that user.'),
            '#default_value' => isset($savedConfig['playlistid']) ? $savedConfig['playlistid'] : $config->get('playlistid'),
        );

        // Create the fequency setting.
        $form['frequency'] = array(
            '#type' => 'textfield',
            '#required' => TRUE,
            '#title' => t('Cron Frequency'),
            '#description' => t('Enter 0 to disable the cron job. Enter the time in seconds to have it run during cron.'),
            '#default_value' => isset($savedConfig['frequency']) ? $savedConfig['frequency'] : $config->get('frequency'),
        );

        // Create the content type drop down.
        $form['contenttype'] = array(
            '#type' => 'select',
            '#required' => TRUE,
            '#title' => t('Content Type'),
            '#options' => node_type_get_names(),
            '#default_value' => isset($savedConfig['contenttype']) ? $savedConfig['contenttype'] : $config->get('contenttype'),
            '#description' => t('Select the content type that videos should import to'),
        );

        // Get the usernames from the Drupal database.

        $ids = \Drupal::entityQuery('user')
            ->condition('status', 1)
            ->execute();
        $user_data = User::loadMultiple($ids);

        //$users = array('', '');
        $users = array();

        foreach ($user_data as $user_d) {
            //    $users[$user_d->uid] = $user_d->name;
            //$userinfo = $user_data->getData();

            //get user id
            $uid = $user_d->get('uid')->getValue();
            //get value of id
            $uid = $uid[0]['value'];

            //get user name
            $name = $user_d->get('name')->getValue();
            //get value of id
            $name = $name[0]['value'];

            $users[$uid] = $name;
        }

        //var_dump($users);
        //var_dump($user_data);
        //kint($user_data);

        /*
      $user_data = db_query("SELECT uid,name FROM {users} WHERE status=1");
      $users = array('', '');
      foreach ($user_data as $user_data) {
          $users[$user_data->uid] = $user_data->name;
      }*/

        // Author selection drop down.
        $form['drupal_user'] = array(
            '#type' => 'select',
            '#title' => t('Author'),
            '#options' => $users,
            '#default_value' => isset($savedConfig['drupal_user']) ? $savedConfig['drupal_user'] : $config->get('drupal_user'),
            '#required' => FALSE,
            '#description' => t('YouTube import will default to the current user or the user selected here.'),
        );

        $apikey = null;
        $playlistid = null;
        $lastrun = null;

        if (isset($savedConfig['apikey']) && $savedConfig['apikey'] != '') {
            $apikey = $savedConfig['apikey'];
        }

        if (isset($savedConfig['playlistid']) && $savedConfig['playlistid'] != '') {
            $playlistid = $savedConfig['playlistid'];
        }

        if (isset($savedConfig['lastrun']) && $savedConfig['lastrun'] != '') {
            $lastrun = $savedConfig['lastrun'];
        }


        if ($apikey && $playlistid) {

            //generate url
            $url = Url::fromRoute('youtube_import.run_now');

            // Create the run link html.
            $markup = \Drupal::l(t('Click here to run the import now.'), $url);

            // If there is a lastrun date, lets display it.
            if ($lastrun) {
                $markup .= ' (Last run: ' . format_date((int)$lastrun, 'long') . ')';
            }

            // Add the link to the form.
            $form['youtube_import_run_link'] = array(
                '#markup' => "<p>{$markup}</p>",
            );
        }


        /*
         * The form has 2 submit buttons because the mapping area
         * could get long and tedious to scroll through
         * this is the first one.
         */
        /*
        $form['submittop'] = array(
            '#type' => 'submit',
            '#value' => t('Save configuration'),
        );


*/

        if (isset($savedConfig['contenttype'])) {
            $contenttype = $savedConfig['contenttype'];
        }


        // If there is no content type, then we can't select fields.
        if (!empty($contenttype)) {


            /*
             * Just a heading to let the user know this is the
             * mapping section.
             */
            $form['mapheading'] = array(
                '#type' => 'markup',
                '#markup' => '<h2>' . t('Field Mapping') . '</h2>',
            );

            // Drupal 7 -
            // Retrieve the fields for the content type.
            //$fieldinfo = field_info_instances('node', $contenttype);

            //Drupal 8
            $fieldinfo = \Drupal::entityManager()->getFieldDefinitions('node', $contenttype);


            /*
             * Initialize an array for the field names and labels
             * as well as add the ones that do not show up.
             */
            $fields = array('title' => t('Title'), 'created' => 'Created');

            /*
             * Loop through the fields and add them to our
             * more useful array.
             */
            //kint($fieldinfo);
            //exit();

            foreach ($fieldinfo as $key => $value) {
                // Need to mark youtube fields as they are always included.
                //if ($value['widget']['type'] == 'youtube') {
                // kint($value);

                if ($value->getDataType() == 'youtube') {
                    $fields[$key] = $value['label'] . '*';
                    $has_youtube_field = TRUE;
                } else {
                    //drupal 7
                    //$fields[$key] = $value['label'];

                    //drupal 8
                    $fields[$key] = $value->getLabel();
                }
            }


            /*
             * Get the properties that we can pull
             * from YouTube.
             */
            $properties = array(
                '' => t('None'),
                'title' => t('Title'),
                'description' => t('Description'),
                'publishedAt' => t('Published Date'),
                'thumbnails' => t('Thumbnail Image'),
                'id' => t('Video ID'),
                'url' => t('Share URL'),
                'duration' => t('Duration'),
                'dimension' => t('Dimension'),
                'definition' => t('Definition'),
                'viewCount' => t('Number of Views'),
                'likeCount' => t('Number of Likes'),
                'dislikeCount' => t('Number of dislikes'),
                'favoriteCount' => t('Number of Favorites'),
                'commentCount' => t('Number of comments'),
            );

            // Create our indefinite field element.
            $form['mapping'] = array(
                '#tree' => TRUE,
            );

            /*
             * Loop through each of the fields in the
             * content type and create a mapping drop down
             * for each.
             */
            foreach ($fields as $fieldname => $label) {

                // YouTube fields are added automatically.
                if (strpos($label, '*') !== FALSE) {
                    $form['mapping'][$fieldname] = array(
                        '#type' => 'select',
                        '#title' => t("@l <small>@f</small>", array('@f' => $fieldname, '@l' => $label)),
                        '#options' => $properties,
                        '#value' => 'url',
                        '#disabled' => TRUE,
                    );
                } else {

                    // Create the mapping dropdown.
                    $form["mapping"][$fieldname] = array(
                        '#type' => 'select',
                        '#title' => t("@l <small>@f</small>", array('@f' => $fieldname, '@l' => $label)),
                        '#options' => $properties,
                        '#default_value' => isset($savedConfig['mapping'][$fieldname]) ? $savedConfig['mapping'][$fieldname] : NULL
                        //'#default_value' => isset($mapping[$fieldname]) ? $mapping[$fieldname] : NULL,
                    );
                }
            }

            // If there is a youtube field, need to explain *.
            if ($has_youtube_field) {
                $form['youtube_markup'] = array(
                    '#type' => 'markup',
                    '#markup' => '<p>' . t('YouTube fields are automatically added to the mapping.') . '</p>',
                );
            }

            // Create the submit button at the bottom of the form.
            /*$form['submit'] = array(
                '#type' => 'submit',
                '#value' => t('Save Configuration Settings 1'),
            );*/

        }


        return parent::buildForm($form, $form_state);


    }

    /**
     * Check if config variable is overridden by the settings.php.
     *
     * @param string $name
     *  STMP settings key.
     *
     * @return bool
     */
    protected function isOverridden($name)
    {
        $original = $this->configFactory->getEditable('smtp.settings')->get($name);
        $current = $this->configFactory->get('smtp.settings')->get($name);
        return $original != $current;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $values = $form_state->getValues();
        //kern($form);

        if (empty($values['username']) && empty($values['playlistid'])) {
            $form_state->setError($form, t('The username and playlist id cannot both be blank.'));
            $form_state->setError($form);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {

        //Get form state values
        $values = $form_state->getValues();

        // Get the previous settings.
        $settings = youtube_import_get();

        // Get the youtube settings list (non mapping stuff).
        $setting_keys = array(
            'username',
            'drupal_user',
            'apikey',
            'playlistid',
            'lastrun',
            'frequency',
            'contenttype',
        );

        // Loop through the form values and see which matches we can find.
        foreach ($setting_keys as $key) {

            // Set the value or clear it depending on user submission.
            if (array_key_exists($key, $values)) {
                $settings[$key] = $values[$key];
            } else {
                $settings[$key] = '';
            }
        }

        // Loop through the user updated mapping fields.
        if (array_key_exists('mapping', $values)) {
            foreach ($values['mapping'] as $key => $value) {
                // Set the mapping value.
                $settings['mapping'][$key] = $value;
            }
        }

        // If the username was set and the playlist wasn't, let's get the default.
        if (empty($settings['playlistid'])) {
            $settings['playlistid'] = youtube_import_playlist_id($settings['username'], $settings['apikey']);
        }

        // Determine the level of success.
        if (!empty($settings['playlistid'])) {
            // Inform the user.
            drupal_set_message(t('YouTube Import settings saved successfully.'));
        } else {
            drupal_set_message(t('Unable to set the play list ID.'), 'error');
        }

        // Save our settings.
        youtube_import_set($settings);

        parent::submitForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     *
     * @todo - Flesh this out.
     */
    public function getEditableConfigNames()
    {
        return [
            'youtube_import.settings',
        ];
    }

}
