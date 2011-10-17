<?php

/**
 * @package Video_Gallery
 * @author Gürkan Oluç
 * @version 1.0.5
 */
/*
  Plugin Name: Video Gallery
  Plugin URI: http://blog.gurkanoluc.com/video-gallery-plugin
  Description: Video gallery plugin
  Author: Gürkan Oluç
  Version: 1.0.5
  Author URI: http://www.gurkanoluc.com
 */

class videoGallery {

    public $pluginUrl;
    public $phpThumbLibrary;
    private $pluginFilename;
    private $pluginDir;
    private $pluginFilepath;
    private $pluginAdminUrl;
    private $db;
    private $galleriesTable;
    private $videosTable;

    function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->pluginFilename = 'video-gallery.php';
        $this->pluginDir = 'video-gallery';
        $this->pluginFilepath = dirname(__FILE__);
        $this->pluginUrl = get_bloginfo('wpurl') . '/wp-content/plugins/' . $this->pluginDir;
        $this->pluginAdminUrl = 'admin.php?page=' . $this->pluginDir . '/' . $this->pluginFilename;
        $this->galleriesTable = $this->db->prefix . 'vg_galleries';
        $this->videosTable = $this->db->prefix . 'vg_videos';
        $this->phpThumbLibrary = $this->pluginUrl . '/lib/thumb/thumb.php';
        /**
         * Actions
         */
        add_action('admin_menu', array(&$this, 'addToOptionsMenu'));
        add_action('admin_head', array(&$this, 'adminJsCss'));
        add_action('wp_head', array(&$this, 'frontJsCss'));
        add_action('wp_ajax_vg_add_gallery', array(&$this, 'addGallery'));
        add_action('wp_ajax_vg_delete_gallery', array(&$this, 'deleteGallery'));
        add_action('wp_ajax_vg_edit_gallery_form', array(&$this, 'editGalleryForm'));
        add_action('wp_ajax_vg_edit_gallery_submit', array(&$this, 'editGallerySubmit'));
        add_action('wp_ajax_vg_delete_video', array(&$this, 'deleteVideo'));

        /**
         * Filters
         */
        add_filter('the_content', array(&$this, 'replaceGalleries'));
        /**
         * load language file
         */
        load_plugin_textdomain('videogallery', false, $this->pluginDir . '/lang');
    }

    /**
     * Add link to options menu
     */
    public function addToOptionsMenu() {
        add_menu_page('General', 'Video Gallery', 10, __FILE__, array(&$this, 'mainContent'));
        add_submenu_page('video-gallery/video-gallery.php', $this->_('Galeri Ekle'), $this->_('Galeri Ekle'), 10, __FILE__ . '&p=addGallery', array(&$this, 'mainContent'));
        // add_submenu_page('video-gallery/video-gallery.php', $this->_('Galerileri Yönet'), $this->_('Galerileri Yönet'), 10, __FILE__.'&p=manageGalleries', array(&$this,'mainContent'));
    }

    public function frontJsCss() {
        /**
         * Javascripts
         */
        wp_register_script('video-gallery-front', $this->pluginUrl . '/js/front.js');
        wp_register_script('video-gallery-modal', $this->pluginUrl . '/js/jquery.nyroModal-1.6.2.js');
        wp_enqueue_script('jquery');
        wp_enqueue_script('video-gallery-modal');
        wp_enqueue_script('video-gallery-front');
        /**
         * CSS
         */
        wp_register_style('video-gallery', $this->pluginUrl . '/css/style.css');
        wp_enqueue_style('video-gallery');
        wp_register_style('video-gallery-modal', $this->pluginUrl . '/css/nyroModal.css');
        wp_enqueue_style('video-gallery-modal');
        wp_print_scripts();
        wp_print_styles();
    }

    public function adminJsCss() {
        /**
         * Javascripts
         */
        // wp_register_script('language-strings', $this->pluginUrl . '/js/languageStrings.js');
        wp_register_script('video-gallery', $this->pluginUrl . '/js/admin.js');
        wp_register_script('video-gallery-modal', $this->pluginUrl . '/js/jquery.nyroModal-1.6.2.min.js');
        wp_register_script('video-gallery-scrollTo', $this->pluginUrl . '/js/jquery.scrollTo.js');
        wp_enqueue_script('jquery');
        // wp_enqueue_script('language-strings');
        wp_enqueue_script('video-gallery');
        wp_localize_script('video-gallery', 'objectL10n', array(
            'close' => __('Kapat', 'videogallery'),
            'addGallery' => __('Galeri Ekle', 'videogallery')
        ));
        wp_enqueue_script('video-gallery-modal');
        wp_enqueue_script('video-gallery-scrollTo');
        /**
         * CSS
         */
        wp_register_style('video-gallery', $this->pluginUrl . '/css/style.css');
        wp_enqueue_style('video-gallery');
        wp_register_style('video-gallery-modal', $this->pluginUrl . '/css/nyroModal.css');
        wp_enqueue_style('video-gallery-modal');
        wp_print_scripts();
        wp_print_styles();
    }

    /**
     * Main content
     */
    public function mainContent() {
        $allowed_functions = array('add');
        $func = $_GET['f'];
        $p = $_GET['p'];
        
        if (method_exists(&$this, $func)) {
            call_user_method($func, &$this);
        } else {
            
            $videoGalleryTitle = $this->_('Social Video Gallery', 'videogallery');
            
            $html .= <<<EOS
        <div id="dialog"></div>
        <h2>{$videoGalleryTitle}</h2>
        <ul id="vg_menu">
EOS;
            $html .= '<li>';
            $html .= '<input type="button" id="vg_add_gallery_link" class="button-secondary" value="' . $this->_('Galeri Ekle') . '">';
            $html .= '</li>';
            $html .= '</ul>';

            $gallery_name_string = $this->_('Galeri Adı');
            $gallery_submit_string = $this->_('Ekle');
            $display = ( $p == 'addGallery' ) ? 'style="display:block;"' : '';
            $html .= <<<EOS
      <div id="vg_add_gallery_div" {$display}>
        <form method="post" id="vg_add_gallery_form">
        <input type="hidden" id="add_video_gallery_last_video" value="5" />
        <table class="form-table">
          <tr valign="top">
            <td colspan="2"><div id="vg_add_gallery_message" class="message"></div></td>
          </tr>
EOS;
            $add_gallery_video_tip = $this->_('Örnek URL\'ler için buraya tıklayınız');
            $html .= <<<EOS
                <tr valign="top" id="add_gallery_gallery_name_tr">
                  <th scope="col">{$gallery_name_string}</td>
                  <td>
                    <input type="text" name="gallery_name" id="gallery_name" class="regular-text" />
                  </td>
                </tr>
EOS;
            $html .= '<tr valign="top">';
            $html .= '<th scope="col">' . $this->_('Video genişliği') . '</th>';
            $html .= '<td>';
            $html .= '<select id="add_gallery_video_width">';
            for ($i = 1; $i < 9; $i++) {
                $html .= '<option value="' . $i * 80 . '">' . $i * 80 . '</option>';
            }
            $html .= '</select> Px';
            $html .= '</td>';
            $html .= '</tr>';
            $html .= '<tr valign="top">';
            $html .= '<th scope="col">' . $this->_('Video yüksekliği') . '</th>';
            $html .= '<td>';
            $html .= '<select id="add_gallery_video_height">';
            for ($i = 1; $i < 9; $i++) {
                $html .= '<option value="' . $i * 80 . '">' . $i * 80 . '</option>';
            }
            $html .= '</select> Px';
            $html .= '</td>';
            $html .= '</tr>';
            $html .= '<tr valign="top">';
            $html .= '<th scope="col">' . $this->_('Thumbnail genişliği x yüksekliği') . '</th>';
            $html .= '<td><input type="text" class="small-text" id="add_video_gallery_thumb_width" /> x <input type="text" class="small-text" id="add_video_gallery_thumb_height" /> Px</td>';
            $html .= '</tr>';

            $html .= <<<EOS
      <tr>
        <td colspan="2"><span class="add_gallery_video_tip"><a href="#" id="add_gallery_video_tip_url">{$add_gallery_video_tip}</a></span></td>
      </tr>
EOS;
            $service = $this->_('Servis');
            $example_url = $this->_('Örnek Url');
            $html .= <<<EOS
      <tr id="add_gallery_video_tip_tr">
        <td colspan="2">
            <div id="add_gallery_video_tip_div">
              <table cellspacing="2" cellpadding="2" border="0" id="add_gallery_video_tips_table">
                <thead>
                  <tr>
                    <th class="s">{$service}</th>
                    <th class="e">{$example_url}</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr>
                    <td class="s">Youtube</td>
                    <td class="e">http://www.youtube.com/watch?v=JgiGrXpOhYg</td>
                  </tr>
                  <tr>
                    <td class="s">Vimeo</td>
                    <td class="e">http://www.vimeo.com/1592934</td>
                  </tr>
                  <tr>
                    <td class="s">Dailymotion</td>
                    <td class="e">http://www.dailymotion.com/video/xcgumk_acoustic-research-arwh1-bluetooth-m_tech</td>
                  </tr>
                  </tbody>
              </table>
            </div>
         </td>
      </tr>
EOS;

            for ($i = 0; $i < 5; $i++) {
                $html .= '<tr valign="top" id="vg_add_gallery_url_tr_' . ( $i + 1 ) . '">';
                $html .= '<td scope="col">' . $this->_('Video') . ' ' . ($i + 1 ) . '</td>';
                $html .= '<td><input type="text" class="regular-text vg_add_gallery_url" id="vg_add_gallery_url_' . ($i + 1) . '" /> </td>';
                $html .= '</tr>';
            }

            $html .= '<tr valign="top">';
            $html .= '<td colspan="2" style="padding-left:400px;">';
            $html .= '<select id="add_gallery_video_count">';
            for ($i = 1; $i < 51; $i++) {
                $html .= sprintf('<option value="%s">%s</option>', $i, $i);
            }
            $html .= '</select>';
            $html .= '<input type="button" class="button-secondary" id="add_gallery_video_count_button" value="' . $this->_('Video Ekle') . '" />';
            $html .= '</td>';
            $html .= '</tr>';

            $html .= <<<EOS
            <tr valign="top">
            <td colspan="2">
                <input class="button-primary" id="vg_add_gallery_button" type="submit" value="{$gallery_submit_string}" />
            </td>
          </tr>
        </table>
        </form>
      </div>
EOS;

            if ($p == 'manageGalleries' || empty($p))
                $html .= $this->_getGalleriesAsHTML();

            echo $html;
        }
    }

    private function _getGalleriesAsHTML() {
        $galleries = $this->getGalleries();
        $see_how_to_add_videos_and_galleries_string = $this->_('Video yada galerileri sayfama nasıl ekleyeceğim?');
        $html .= '<h3 style="float:left;">' . $this->_('Galeriler') . '</h3>';
        $html .= '<table class="widefat fixed" cellspacing="0" style="width:700px;">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th scope="col">' . $this->_('ID') . '</th>';
        $html .= '<th>' . $this->_('Galeri Adı') . '</th>';
        $html .= '<th>' . $this->_('Video Sayısı') . '</th>';
        $html .= '<th>' . $this->_('İşlemler') . '</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        if ($galleries) {
            foreach ($galleries AS $gallery) {
                $html .= '<tr id="gallery_' . $gallery->id . '">';
                $html .= '<td>' . $gallery->id . '</td>';
                $html .= '<td id="gallery_list_gallery_name_' . $gallery->id . '">' . $gallery->gallery_name . '</td>';
                $html .= '<td id="gallery_video_count_' . $gallery->id . '">' . $gallery->videos_count . '</td>';
                $html .= '<td>
          <a href="#" id="gallery_delete_link_' . $gallery->id . '" class="gallery_delete_link">' . $this->_('Sil') . '</a> &bull;
          <a href="#" id="gallery_edit_link_' . $gallery->id . '" class="gallery_edit_link">' . $this->_('Düzenle') . '</a>
        </td>';
                $html .= '</tr>';
            }
        }
        $html .= '</tbody>';
        $html .= '</table>';
        return $html;
    }

    /**
     * Add Gallery
     */
    public function addGallery() {
        $gallery_name = wp_specialchars($_POST['gallery_name']);
        $gallery_name = trim($gallery_name);
        if (empty($gallery_name)) {
            echo $this->_ajaxResponse(array(0, $this->_('Lütfen bir galeri adı giriniz')));
            return;
        }

        $video_gallery_links_text = trim($_POST['video_galleries']);
        $video_gallery_links_array = explode('::', $video_gallery_links_text);
        // Dirty Hack..
        array_pop($video_gallery_links_array);

        $videos = array();
        $video_gallery_links_array_count = count($video_gallery_links_array);
        for ($i = 0; $i < $video_gallery_links_array_count; $i++) {
            $url = $video_gallery_links_array[$i];
            $url = str_replace(($i + 1) . '=', '', $url);
            if (!empty($url)) {
                $video = $this->_getVideoInformation($url);
                $videos[] = $video;
            }
        }

        $video_width = (int) $_POST['video_width'];
        $video_height = (int) $_POST['video_height'];
        $thumbnail_width = (int) $_POST['thumbnail_width'];
        $thumbnail_height = (int) $_POST['thumbnail_height'];

        $sql = $this->db->prepare("INSERT INTO {$this->galleriesTable} SET gallery_name = '%s',
    video_width = %d, video_height = %d, thumbnail_width = %d, thumbnail_height = %d", $gallery_name, $video_width, $video_height, $thumbnail_width, $thumbnail_height);
        $query = $this->db->query($sql);

        if (!$query) {
            echo $this->_ajaxResponse(array(0, $this->_('Birşeyler yanlış gitti')));
            return;
        }

        $gallery_id = $this->db->insert_id;

        if ($videos) {
            $values = array();
            foreach ($videos AS $video) {
                $values[] = sprintf("(%d, '%s', '%s', '%s', '%s')", $gallery_id, $video['url'], $video['image'], $video['service'], $video['id']);
            }

            $sql = "INSERT INTO $this->videosTable (gallery_id, url, image, service, video_id) VALUES " . implode(',', $values);
            $query = $this->db->query($sql);
        }

        echo $this->_ajaxResponse(array(1, $this->_('Galeri oluşturuldu')));
        return;
    }

    /**
     * Deletes gallery
     */
    public function deleteGallery() {

        $id = (int) $_POST['gallery_id'];

        if (!$id) {
            echo $this->_ajaxResponse(array(0));
            return;
        }

        $sql = "DELETE FROM {$this->galleriesTable} WHERE id = %d";
        $sql = $this->db->prepare($sql, $id);
        $query = $this->db->query($sql);

        if ($query) {

            $sql = "DELETE FROM {$this->videosTable} WHERE gallery_id = %d";
            $sql = $this->db->prepare($sql, $id);
            $query2 = $this->db->query($sql);
            echo $this->_ajaxResponse(array(1));
        } else {
            echo $this->_ajaxResponse(array(0));
        }
    }

    public function editGallerySubmit() {
        $gallery_id = (int) $_POST['gallery_id'];

        if (!$gallery_id) {
            return false;
        }

        $gallery_name = trim($_POST['gallery_name']);
        $video_width = (int) $_POST['video_width'];
        $video_height = (int) $_POST['video_height'];
        $thumb_width = (int) $_POST['thumbnail_width'];
        $thumb_height = (int) $_POST['thumbnail_height'];

        if (empty($gallery_name)) {
            echo $this->_ajaxResponse(array(0));
            return;
        }

        $sql = $this->db->prepare("UPDATE {$this->galleriesTable} SET gallery_name = %s, video_width = %d,
    video_height = %d, thumbnail_width = %d, thumbnail_height = %d WHERE id= %d", $gallery_name, $video_width, $video_height, $thumb_width, $thumb_height, $gallery_id);
        $query = $this->db->query($sql);


        $videos_text = trim($_POST['videos']);
        $videos_links_array = explode('::', $videos_text);
        // Dirty Hack..
        array_pop($videos_links_array);

        $videos = array();
        $videos_links_array_count = count($videos_links_array);
        for ($i = 0; $i < $videos_links_array_count; $i++) {
            $url = $videos_links_array[$i];
            $url = str_replace(($i + 1) . '=', '', $url);
            if (!empty($url)) {
                $video = $this->_getVideoInformation($url);
                $videos[] = $video;
            }
        }

        if ($videos) {
            $values = array();
            foreach ($videos AS $video) {
                $values[] = sprintf("(%d, '%s', '%s', '%s', '%s')", $gallery_id, $video['url'], $video['image'], $video['service'], $video['id']);
            }
            $sql = "INSERT INTO $this->videosTable (gallery_id, url, image, service, video_id) VALUES " . implode(',', $values);
            $query = $this->db->query($sql);
        }



        echo $this->_ajaxResponse(array(1, count($videos)));
    }

    public function editGalleryForm() {
        $gallery_id = (int) $_POST['gallery_id'];

        if (!$gallery_id) {
            echo $this->_ajaxResponse(array(0));
            return;
        }


        $sql = "SELECT * FROM {$this->galleriesTable} WHERE id = %d LIMIT 1";
        $sql = $this->db->prepare($sql, $gallery_id);
        $gallery = $this->db->get_row($sql);

        if (!$gallery) {
            echo $this->_ajaxResponse(array(0));
            return;
        }

        $html = '<tr id="gallery_edit_' . $gallery_id . '" class="inline-edit-row inline-edit-row-post quick-edit-row quick-edit-row-post alternate inline-editor">';
        $html .= '<td colspan="4">';
        $html .= '<h4>' . $this->_('Galeri Düzenle') . '</h4>';
        $html .= '<fieldset class="inline-edit-col-left">';
        $html .= '<div class="inline-edit-col">';
        $html .= '<label>';
        $html .= '<span class="title">' . $this->_('Galeri Adı') . '</span>';
        $html .= '<span class="input-text-wrap"><input type="text" id="vg_edit_gallery_gallery_name_' . $gallery->id . '"name="gallery_name" value="' . $gallery->gallery_name . '" /></span>';
        $html .= '</label>';
        // $html .= '</div>';
        // Video genişliği
        // $html .= '<div class="inline-edit-col">';
        $html .= '<div class="clear"></div>';
        $html .= '<label>';
        $html .= '<span class="title">' . $this->_('Video genişliği') . '</span>';
        $html .= '<span class="input-text-wrap">';
        $html .= '<select id="gallery_edit_video_width_' . $gallery->id . '">';
        for ($i = 1; $i < 9; $i++) {
            $selected = ( $i * 80 == $gallery->video_width ) ? ' selected="selected"' : '';
            $html .= '<option value="' . $i * 80 . '"' . $selected . '>' . $i * 80 . '</option>';
        }
        $html .= '</select> Px';
        $html .= '</span>';
        $html .= '</label>';
        // $html .= '</div>';

        $html .= '<div class="clear"></div>';

        // Video yüksekliği
        // $html .= '<div class="inline-edit-col">';
        $html .= '<label>';
        $html .= '<span class="title">' . $this->_('Video yüksekliği') . '</span>';
        $html .= '<span class="input-text-wrap">';
        $html .= '<select id="gallery_edit_video_height_' . $gallery->id . '">';
        for ($i = 1; $i < 9; $i++) {
            $selected = ( $i * 80 == $gallery->video_height ) ? ' selected="selected"' : '';
            $html .= '<option value="' . $i * 80 . '"' . $selected . '>' . $i * 80 . '</option>';
        }
        $html .= '</select> Px';
        $html .= '</span>';
        $html .= '</label>';

        $html .= '<div class="clear"></div>';

        $html .= '<div class="f-left" style="margin-right:20px;">';
        $html .= '<label>';
        $html .= '<span class="title">' . $this->_('Thumb genişliği') . '</span>';
        $html .= '<span class="input-text-wrap"><input type="text"  style="width:50px;" class="small-text" id="gallery_edit_thumb_width_' . $gallery->id . '"name="gallery_name" value="' . $gallery->thumbnail_width . '" /> </span>';
        $html .= '</label>';
        $html .= '</div>';
        // $html .= '<div class="clear"></div>';
        $html .= '<div>';
        $html .= '<label>';
        $html .= '<span class="title">' . $this->_('Thumb yüksekliği') . '</span>';
        $html .= '<span class="input-text-wrap"><input type="text" style="width:50px;" id="gallery_edit_thumb_height_' . $gallery->id . '"name="gallery_name" value="' . $gallery->thumbnail_height . '" /></span>';
        $html .= '</label>';
        $html .= '</div>';

        $html .= '<div class="clear"></div>';

        $html .= '<label>';
        $html .= '<span class="title">' . $this->_('Yayın Kodu') . '</span>';
        $html .= '<span class="input-text-wrap"><input type="text" class="vg_edit_gallery_gallery_copy_text" name="gallery_name" value="[gallery-' . $gallery->id . ']" /></span>';
        $html .= '</label>';

        $html .= '</div>';
        $html .= '</fieldset>';

        $html .= '<div class="clear"></div>';
        $html .= '<h4>' . $this->_('Videolar') . '</h4>';
        $html .= '<div id="add_videos">';
        $html .= '<select id="gallery_edit_add_video_count_' . $gallery->id . '">';
        for ($i = 1; $i < 51; $i++) {
            $html .= '<option value="' . $i . '">' . $i . '</option>';
        }
        $html .= '</select>';
        $html .= '<input type="button" id="gallery_edit_add_video_button_' . $gallery->id . '"
    class="gallery_edit_add_video_button button-secondary" value="' . $this->_('Video Ekle') . '"/>';
        $html .= '<div id="gallery_edit_add_video_videos_' . $gallery->id . '" class="gallery_edit_add_video_videos">';
        $html .= '</div>';
        $html .= '</div>';
        $videos = $this->getVideos($gallery_id);
        if ($videos) {
            $html .= '<div id="gallery_edit_videos">';
            $i = 0;
            foreach ($videos AS $video) {
                if ($i % 4 == 0)
                    $html .= '<div class="clear"></div>';
                $html .= '<div id="video_' . $video->id . '" class="gallery_edit_video">';
                $html .= '<img src="' . $this->phpThumbLibrary . '?src=' . $video->image . '" style="width:100px; height:100px;">';
                $html .= '<br/>';
                $html .= '<span class="input-text-wrap"><input type="text" class="gallery_edit_video_copy_text" style="width:100px;" value="[video-' . $video->id . ']" /></span>';
                $html .= '<br/><span class="gallery_edit_video_delete_link_span">
      <small><a href="#" id="gallery_edit_video_delete_link_' . $video->id . '_' . $video->gallery_id . '" class="gallery_edit_video_delete_link">' . $this->_('Sil') . '</a></small>
      </span>';
                $html .= '</div>';
                $i++;
            }
            $html .= '</div>';
        }

        $html .= '<div class="clear"></div><div style="float:right; margin-right:20px; margin-bottom:20px;">';
        $html .= '<input type="button" class="gallery_edit_back_button button-secondary" id="gallery_edit_back_button_' . $gallery->id . '" value="' . $this->_('İptal') . '" />';
        $html .= '<input type="button" class="gallery_edit_add_video_submit_button button-primary" id="gallery_edit_add_video_submit_button_' . $gallery->id . '" value="Submit" />';
        $html .= '</div>';


        $html .= '</td>';
        $html .= '</tr>';


        echo $this->_ajaxResponse(array(1, $html));
    }

    public function replaceGalleries($content) {
        preg_match_all('~\[gallery-(\d+)\]+~im', $content, $matches);
        $html = '';
        // If there is a gallery in post
        if ($matches[1]) {
            foreach ($matches[1] AS $galleryID) {
                $gallery = $this->getGallery($galleryID);
                $gallery_videos = $this->getVideos($galleryID);
                if ($gallery_videos) {
                    $extra_options = '';
                    if ((int) $gallery->video_width > 0) {
                        $extra_options .= "width : '" . $gallery->video_width . "px',\n";
                    }
                    if ((int) $gallery->video_height > 0) {
                        $extra_options .= "height : '" . $gallery->video_height . "px'\n";
                    }


                    $html .= '<div class="clear"></div>';
                    $html .= '<div class="post_video_gallery" id="video_gallery_' . $gallery->id . '">';
                    $html .= '<h3>' . $gallery->gallery_name . '</h3>';
                    $i = 0;
                    $extra_params = ( $gallery->thumbnail_width ) ? '&w=' . $gallery->thumbnail_width : '';
                    $extra_params .= ( $gallery->thumbnail_height ) ? '&h=' . $gallery->thumbnail_height : '';
                    $thumb_css = (!empty($width_css) || !empty($height_css) ) ? 'style="' . $width_css . $height_css . '"' : '';
                    foreach ($gallery_videos AS $video) {
                        $i++;

                        $html .= '<div id="video_' . $video->id . '" class="video_item videoPlayerHidden">';
                        $flash_url = $this->getFlashPlayerUrl($video);
                        $html .= '<a href="#hidden_video_' . $video->id . '" rel="facebox" class="post_video_gallery_video">';
                        $html .= '<img src="' . $this->phpThumbLibrary . '?src=' . $video->image . $extra_params . '" /></a>';
                        $html .= '</div>';
                        if ($i % 3 == 0) {
                            $html .= '<div class="clear"></div>';
                        }
                        $html .= '<div id="hidden_video_' . $video->id . '" style="display:none;">';
                        $html .= '<object width="' . $gallery->video_width . '" height="' . $gallery->video_height . '"><param name="movie" value="' . $flash_url . '"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="' . $flash_url . '" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="' . $gallery->video_width . '" height="' . $gallery->video_height . '"></embed></object>';
                        $html .= '</div>';
                    }
                    $html .= '</div>';
                    $content = str_replace('[gallery-' . $galleryID . ']', $html, $content);
                }
            }
        }


        unset($matches);

        preg_match_all('~\[video-(\d+)\]+~im', $content, $matches);

        if ($matches[1]) {
            foreach ($matches[1] AS $videoID) {
                $video = $this->getVideo($videoID);
                if (!$video)
                    continue;
                $html = '';
                $html .= '<div id="video_' . $video->id . '" class="video_item videoPlayerHidden">';
                $flash_url = $this->getFlashPlayerUrl($video);
                $html .= '<a href="#hidden_video_' . $video->id . '" rel="facebox" class="post_video_gallery_video">';
                $html .= '<img src="' . $this->phpThumbLibrary . '?src=' . $video->image . $extra_params . '" /></a>';
                $html .= '</div>';
                if ($i % 3 == 0) {
                    $html .= '<div class="clear"></div>';
                }
                $html .= '<div id="hidden_video_' . $video->id . '" style="display:none;">';
                $html .= '<object width="' . $video->video_width . '" height="' . $video->video_height . '"><param name="movie" value="' . $flash_url . '"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="' . $flash_url . '" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="' . $gallery->video_width . '" height="' . $gallery->video_height . '"></embed></object>';
                $html .= '</div>';

                $content = str_replace('[video-' . $videoID . ']', $html, $content);
            }
        }


        return $content;
    }

    public function deleteVideo() {
        $video_id = (int) $_POST['video_id'];

        if (!$video_id) {
            echo $this->_ajaxResponse(array(0));
            return;
        }

        $sql = "DELETE FROM {$this->videosTable} WHERE id = %d";
        $sql = $this->db->prepare($sql, $video_id);
        $query = $this->db->query($sql);

        if ($query) {
            echo $this->_ajaxResponse(array(1));
        } else {
            echo $this->_ajaxResponse(array(0));
        }
    }

    public function getGallery($galleryID) {
        $sql = "SELECT * FROM {$this->galleriesTable} WHERE id = %d LIMIT 1";
        $sql = $this->db->prepare($sql, $galleryID);
        $gallery = $this->db->get_row($sql);
        return $gallery;
    }

    public function getGalleries() {
        $sql = "SELECT g.*, (SELECT COUNT(id) FROM {$this->videosTable} v WHERE v.gallery_id = g.id ) AS videos_count FROM {$this->galleriesTable} g GROUP BY g.id ORDER BY id DESC";
        $galleries = $this->db->get_results($sql);
        return $galleries;
    }

    public function getVideos($gallery_id) {
        $sql = "SELECT * FROM $this->videosTable WHERE gallery_id = %d";
        $sql = $this->db->prepare($sql, $gallery_id);
        $videos = $this->db->get_results($sql);
        return $videos;
    }

    /**
     * Returns video information
     * @param <type> $id
     * @return <type>
     */
    public function getVideo($id) {
        $sql = "SELECT v.*, g.video_width, g.video_height FROM {$this->videosTable} v LEFT JOIN {$this->galleriesTable} g " .
            "ON (g.id = v.gallery_id) WHERE v.id = %d";
        $sql = $this->db->prepare($sql, $id);
        $video = $this->db->get_row($sql);
        return $video;
    }

    public function getVidesForWidget($sort = 'random', $limit = 10, $gallery_id) {
        $sort = ( $sort == 'random' ) ? 'RAND()' : 'v.id';
        $where = ($gallery_id) ? 'WHERE v.gallery_id = ' . $gallery_id : '';
        $sql = "SELECT v.*, g.video_width, g.video_height" .
            " FROM {$this->videosTable} v JOIN {$this->galleriesTable} g ON g.id = v.gallery_id " .
            $where . " ORDER BY {$sort} DESC LIMIT {$limit}";
        $r = $this->db->get_results($sql);
        return $r;
    }

    private function _getVideoInformation($url) {
        $video_exists = false;
        if (!$url) {
            return false;
        }
        if (strpos($url, '.youtube.')) {
            $url_parts = parse_url($url);
            if ($url_parts['query']) {
                parse_str($url_parts['query'], $url_parts_array);
                if (isset($url_parts_array['v'])) {
                    $video_exists = true;
                    $video_id = $url_parts_array['v'];
                    $video_url = $url;
                    $video_img = 'http://i3.ytimg.com/vi/' . $url_parts_array['v'] . '/default.jpg';
                    $video_service = 'youtube';
                }
            }
        } else if (strpos($url, '.vimeo.')) {
            $url_parts = parse_url($url);
            $video_id = substr($url_parts['path'], 1);
            $video_data = file_get_contents('http://vimeo.com/api/v2/video/' . $video_id . '.json');
            $video_data = json_decode($video_data, true);
            if ($video_data[0]) {
                $video_exists = true;
                $video_img = $video_data[0]['thumbnail_small'];
                $video_url = $video_data[0]['url'];
                $video_service = 'vimeo';
            }
        } else if (strpos($url, '.dailymotion')) {
            $url_parts = parse_url($url);
            $path = substr($url_parts['path'], 1);
            // / erase
            $path_array = explode('/', $path);
            $path = $path_array[1];

            // Get video dailymotion
            $path_array = explode('_', $path);
            if ($path_array) {
                $video_exists = true;
                $video_id = $path_array[0];
                $video_url = $url;
                $video_img = 'http://www.dailymotion.com/thumbnail/80x60/video/' . $video_id;
                $video_service = 'dailymotion';
            }
        }

        if ($video_exists) {
            return array(
                'id' => $video_id,
                'service' => $video_service,
                'image' => $video_img,
                'url' => $video_url
            );
        } else {
            return false;
        }
    }

    /**
     * Returns the flash player url of video by looking its service
     * @param object $video
     * @return string
     */
    public function getFlashPlayerUrl($video) {
        if ($video->service == 'youtube') {
            $flash_url = 'http://www.youtube.com/v/' . $video->video_id;
        } else if ($video->service == 'dailymotion') {
            $flash_url = 'http://www.dailymotion.com/swf/' . $video->video_id;
        } else {
            $flash_url = 'http://vimeo.com/moogaloop.swf?clip_id=' . $video->video_id;
        }
        return $flash_url;
    }

    /**
     *
     * @param <type> $href
     * @param <type> $title
     * @param <type> $extra
     * @return <type>
     */
    private function _link($href, $title, $extra = '') {
        $href_string = '';
        foreach ($href AS $key => $value) {
            $href_string_array[] = $key . '=' . $value;
        }

        $href = implode('&', $href_string_array);

        return '<a href=" ' . $this->pluginAdminUrl . '&' . $href . ' " ' . $extra . '>' . $title . '</a>';
    }

    /**
     *
     * @param <type> $string
     * @return <type> 
     */
    private function _($string) {
        return __($string, "videogallery", 'videogallery');
    }

    /**
     *
     * @param <type> $response
     * @return <type>
     */
    private function _ajaxResponse($response) {
        return implode('::', $response);
    }

}

function videoGallery_widgetControl() {

    if ($_POST) {

        $sort = $_POST['sort'];
        $limit = (int) $_POST['limit'];
        $gallery_id = (int) $_POST['gallery_id'];
        if (!$limit)
            $limit = 10;

        if (!in_array($sort, array('random', 'id')))
            $sort = 'random';

        update_option('video_gallery_widget_options', array(
            'sort' => $sort,
            'limit' => $limit,
            'gallery_id' => $gallery_id
        ));
    }


    $options = get_option('video_gallery_widget_options');

    if (!$options) {
        add_option('video_gallery_widget_options', array(
            'sort' => 'random',
            'limit' => 10,
            'gallery_id' => 0
        ));
    }

    $sort = ( $options['sort'] ) ? $options['sort'] : 'random';
    $limit = ( $options['limit'] ) ? $options['limit'] : 10;
    $gallery_id = ( $options['gallery_id']) ? $options['gallery_id'] : 0;

    $vg = new videoGallery();

    $html = '<form method="POST" name="videoGallery_widget">';
    $html .= '<table cellpadding="2" cellspacing="2">';


    // Sorting Options
    $html .= '<tr>';
    $html .= '<td>' . __('Video Sıralaması', 'videogallery') . '</td>';
    $html .= '<td>';
    $sort_values = array('random', 'id');
    $html .= '<select name="sort">';
    foreach ($sort_values AS $value) {
        $selected = ( $value == $sort ) ? 'selected="selected"' : '';
        $html .= sprintf('<option value="%s" %s>%s</option>', $value, $selected, strtoupper($value));
    }
    $html .= '</select>';
    $html .= '</td>';
    $html .= '</tr>';

    // Limit Options
    $html .= '<tr>';
    $html .= '<td>' . __('Video Limiti', 'videogallery') . '</td>';
    $html .= '<td>';
    $html .= '<select name="limit">';
    for ($i = 1; $i <= 20; $i++) {
        $selected = ( $i == $limit ) ? 'selected="selected"' : '';
        $html .= sprintf('<option value="%s" %s>%s</option>', $i, $selected, $i);
    }
    $html .= '</select>';
    $html .= '</td>';
    $html .= '</tr>';

    // Active gallery
    $html .= '<tr>';
    $html .= '<td>' . __('Galeri seçiniz', 'videogallery') . '</td>';
    $html .= '<td>';
    $html .= '<select name="gallery_id">';
    $selected = (!$gallery_id) ? 'selected = "selected"' : '';
    $html .= '<option value="0" ' . $selected . '>Hepsi</option>';
    $galleries = $vg->getGalleries();
    foreach ($galleries AS $gallery) {
        $selected = ($gallery->id == $gallery_id) ? 'selected="selected"' : '';
        $html .= '<option value="' . $gallery->id . '" ' . $selected . '>' . $gallery->gallery_name . '</option>';
    }
    $html .= '</select>';
    $html .= '</td>';
    $html .= '</tr>';

    $html .= '</table>';
    $html .= '</form>';
    echo $html;
}

function videoGallery_widget($args) {
    extract($args);
    $v = new videoGallery();

    $options = get_option('video_gallery_widget_options');

    if (!$options) {
        add_option('video_gallery_widget_options', array(
            'sort' => 'random',
            'limit' => 10,
            'gallery_id' => 0
        ));
    }


    $sort = ( $options['sort'] ) ? $options['sort'] : 'random';
    $limit = ( $options['limit'] ) ? $options['limit'] : 10;
    $gallery_id = ( $options['gallery_id'] ) ? $options['gallery_id'] : 0;
    echo $before_widget;
    echo $before_title . __('Video Galeri', 'videogallery') . $after_title;
    $videos = $v->getVidesForWidget($sort, $limit, $gallery_id);
    $html = '<div id="sidebar_videos">';
    if ($videos) {
        foreach ($videos AS $video) {
            $extra_params = ( $video->thumbnail_width ) ? '&w=' . $gallery->thumbnail_width : '';
            $extra_params .= ( $video->thumbnail_height ) ? '&h=' . $gallery->thumbnail_height : '';
            $html .= '<div id="video_' . $video->id . '" class="video_sidebar videoPlayerHidden">';
            $flash_url = $v->getFlashPlayerUrl($video);
            $html .= '<a href="#hidden_video_' . $video->id . '" rel="facebox" class="post_video_gallery_video">';
            $html .= '<img src="' . $v->phpThumbLibrary . '?src=' . $video->image . $extra_params . '" /></a>';
            $html .= '</div>';
            $html .= '<div id="hidden_video_' . $video->id . '" style="display:none;">';
            $html .= '<object width="' . $gallery->video_width . '" height="' . $gallery->video_height . '"><param name="movie" value="' . $flash_url . '"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="' . $flash_url . '" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="' . $gallery->video_width . '" height="' . $gallery->video_height . '"></embed></object>';
            $html .= '</div>';
        }
    }
    $html .= '</div>';

    echo $html;
    echo $after_widget;
}

function videoGallery_widget_init() {
    register_sidebar_widget(__('Video Galeri', 'videogallery'), 'videoGallery_widget');
    register_widget_control(__('Video Galeri', 'videogallery'), 'videoGallery_widgetControl', 300, 200);
}

add_action("plugins_loaded", "videoGallery_widget_init");

if ((bool) $_GET['activate']) {
    videoGallery_activatePlugin();
}

/**
 * Install Function
 */
function videoGallery_activatePlugin() {
    global $wpdb;

    $sql = '
		CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'vg_galleries` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`gallery_name` varchar(255) CHARACTER SET utf8 NOT NULL,
			`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			`video_width` int(11) NOT NULL,
			`video_height` int(11) NOT NULL,
			`thumbnail_width` int(11) NOT NULL,
			`thumbnail_height` int(11) NOT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8';

    $wpdb->query($sql);

    $sql = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'vg_videos` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`gallery_id` int(11) NOT NULL,
			`url` varchar(255) CHARACTER SET utf8 NOT NULL,
			`image` varchar(255) CHARACTER SET utf8 NOT NULL,
			`service` varchar(20) CHARACTER SET utf8 NOT NULL,
			`video_id` varchar(255) CHARACTER SET utf8 NOT NULL,
			PRIMARY KEY (`id`),
			KEY `gallery_id_index` (`gallery_id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8';
    $wpdb->query($sql);
}

$videoGallery = new videoGallery();
?>
