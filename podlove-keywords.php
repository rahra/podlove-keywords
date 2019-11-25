<?php
   /*
   Plugin Name: Podlove Podcast Keywords
   Plugin URI: https://github.com/rahra/podlove-keywords
   description: This plugin adds your favorite keywords of your podcast to the RSS feed of Podlove Podcast Publisher.
   Version: 1.2
   Author: Bernhard R. Fischer
   Author URI: https://www.cypherpunk.at/
   License: GPL3
   */

define('PTAGS_SLUG', 'podlove_keywords');

class PodloveKeywordsPlugin
{
   /*! plugin constructor */
   public function __construct()
   {
      // add options menu to WP settings
      add_action('admin_menu', array($this, 'create_plugin_settings_page'));

      // add action which adds keywords to the feed
      add_action('podlove_append_to_feed_head', array($this, 'set_feed_tags'), 10, 3);

      // add action which adds keywords to each entry
      if (get_option('pt_tags_post') == 1)
         add_action('podlove_append_to_feed_entry', array($this, 'set_feed_entry_tags'), 10, 4);
   }


   /*! This method actually adds the keywords to the feed. */
   public function set_feed_tags($podcast, $feed, $format)
   {
      // get keywords
      $keywords = get_option('pt_tags');
      // create full keyword string
      $keywords_tag = sprintf('<itunes:keywords>%s</itunes:keywords>', $keywords);
      // output keywords
      echo "\t" . apply_filters('podlove_feed_itunes_keywords', $keywords_tag);
      echo PHP_EOL;
   }


   /*! This method adds each entry's keywords to the feed. */
   public function set_feed_entry_tags($podcast, $episode, $feed, $format)
   {
      // get keywords
      $post_tags = wp_get_post_tags($episode->post_id);
      if ($post_tags)
      {
         foreach($post_tags as $tag)
         {
            if (strlen($keywords) > 0)
               $keywords .= ',';
            $keywords .= $tag->name;
         }
      }
      // create full keyword string
      $keywords_tag = sprintf('<itunes:keywords>%s</itunes:keywords>', $keywords);
      // output keywords
      echo "\n\t" . apply_filters('podlove_feed_itunes_keywords', $keywords_tag);
      echo PHP_EOL;
   }


   /*! This method creates the settings page of the plugin.
   */
   public function create_plugin_settings_page()
   {
      $parent_slug = 'options-general.php';
      $page_title = 'Podlove Keywords Settings Page';
      $menu_title = 'Podlove Keywords';
      $capability = 'manage_options';
      $callback = array( $this, 'plugin_settings_page_content' );
      $icon = 'dashicons-admin-plugins';
      $position = 100;

      // add submenu
      add_submenu_page($parent_slug, $page_title, $menu_title, $capability, PTAGS_SLUG, $callback );

      // settings page callbacks for page display
      add_action('admin_init', array($this, 'setup_sections'));
      add_action('admin_init', array($this, 'setup_fields'));
   }


   /*! Output content of settings page.
   */
   public function plugin_settings_page_content()
   {
      ?> <div class="wrap"> <h2>Podlove Podcast Keywords Settings</h2>
      <p>Here you can set keywords which will be inserted into your podcast feed
      using the &lt;itunes:keywords&gt; tag.</p>
      <p>Please be aware that this tag is deprecated by Apple although it is
      observed that almost all podcast portals evaluate this tag. There is no
      real alternative yet.</p>
      <?php

      if (isset($_GET['settings-updated']) && $_GET['settings-updated'])
      {
         ?> <div class="notice notice-success is-dismissible"> <p>Your settings have been updated!</p> </div> <?php
      }

      ?> <form method="post" action="options.php"> <?php

      settings_fields(PTAGS_SLUG);
      do_settings_sections(PTAGS_SLUG);
      submit_button();

      ?></form></div><?php
   }


   /*! Create section within settings page.
   */
   public function setup_sections()
   {
      add_settings_section( 'section0', 'Tag Configuration Section',
         function ()
         {
            echo "Add comma separated list of keywords here. Allowed characters are a-z, A-Z, 0-9, hyphen '-' and comma ','.";
         },
         PTAGS_SLUG);
   }


   /*! Setup input fields.
   */
   public function setup_fields()
   {
      add_settings_field('pt_tags', 'Tags/Keywords', array( $this, 'field_callback' ), PTAGS_SLUG, 'section0');
      add_settings_field('pt_tags_post', 'Append keywords of post to entries', array($this, 'post_field_callback'), PTAGS_SLUG, 'section0' );

      register_setting(PTAGS_SLUG, 'pt_tags', array($this, 'check_keywords'));
      register_setting(PTAGS_SLUG, 'pt_tags_post', array($this, 'check_post_keywords'));
   }


   /*! Check and sanitize value of input field.
   */
   public function check_keywords($val)
   {
      return preg_replace('/[^[:alnum:]-,]/i', '', $val);
   }


   /*! Check and sanitized value of checkbox
   */
   public function check_post_keywords($val)
   {
      if ($val != 1 && $val != 0)
         $val = 0;
      return $val;
   }


   /*! Ouput input field and its content on the settings page.
   */
   public function field_callback()
   {
      $value = get_option('pt_tags');
      echo '<textarea name="pt_tags" id="pt_tags" rows="5" cols="50">' . $value . '</textarea>';
   }


   /*! Output checkbox field. */
   public function post_field_callback()
   {
      $value = get_option('pt_tags_post');
      echo '<input type="checkbox" name="pt_tags_post" value="1" ' . checked(1, $value, false) . '/>';
   }
}


new PodloveKeywordsPlugin();


?>
