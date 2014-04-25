<?php
/**
 * Stilero Social Promoter Facebook Plugin
 *
 * @version  1.0
 * @author Daniel Eliasson <daniel at stilero.com>
 * @copyright  (C) 2013-dec-26 Stilero Webdesign (http://www.stilero.com)
 * @category Plugins
 * @license	GPLv2
 */

// no direct access
defined('_JEXEC') or die ('Restricted access');
if(!defined('DS')){
    define('DS',DIRECTORY_SEPARATOR);
}
define('PATH_TWITTER', dirname(__FILE__).DS.'library'.DS);
JLoader::register('tmhOauth', PATH_TWITTER.'tmhOauth.php');
JLoader::register('SocialpromoterImporter', JPATH_ADMINISTRATOR.DS.'components'.DS.'com_socialpromoter'.DS.'helpers'.DS.'importer.php');
JLoader::register('SocialpromoterPosttype', JPATH_ADMINISTRATOR.DS.'components'.DS.'com_socialpromoter'.DS.'library'.DS.'posttype.php');
//jimport('joomla.plugin.plugin');
jimport('joomla.event.plugin');

class plgSocialpromoterStilerosptwitter extends JPlugin {
    const SP_NAME = 'Twitter Plugin';
    const SP_DESCRIPTION = 'Posts photos to Twitter';
    const SP_IMAGE = '';
    protected $supportedPosttypes;
    protected $_oauthParams;
    protected $_defaultTitle;
    protected $_defaultDesc;
    protected $_defaultTags;
    
    public function __construct(&$subject, $config = array()) {
        parent::__construct($subject, $config);
        $language = JFactory::getLanguage();
        $language->load('plg_system_stilerosptwitter', JPATH_ADMINISTRATOR, 'en-GB', true);
        $language->load('plg_system_stilerosptwitter', JPATH_ADMINISTRATOR, null, true);
        $this->setParams();
    }
    
    /**
     * Reads the params and sets them in the class 
     */
    protected function setParams(){
        if(!isset($this->params)){
            $plg = JPluginHelper::getPlugin('socialpromoter', 'stilerosptwitter');
            $plg_params = new JRegistry();
            $plg_params->loadString($plg->params);
            $this->params = $plg_params;
        }
        $this->_oauthParams = array(
            'consumer_key' => $this->params->def('consumer_key'),
            'consumer_secret' => $this->params->def('consumer_secret'),
            'token' => $this->params->def('token'),
            'secret' => $this->params->def('secret')
        );
        $this->_defaultTitle = $this->params->def('default_title');
        $this->_defaultDesc = $this->params->def('default_desc');
        $this->_defaultTags = $this->params->def('default_tags');
    }
    
    /**
     * Checks if tags are set, otherwise the default tags will be returned
     * @param string $tags
     * @return string
     */
    public function title($title){
        if($title == ''){
            return $this->_defaultTitle;
        }else{
            return $title;
        }
    }
    
    /**
     * Checks if tags are set, otherwise the default tags will be returned
     * @param string $tags
     * @return string
     */
    public function description($desc){
        if($desc == ''){
            return $this->_defaultDesc;
        }else{
            return $desc;
        }
    }
    
    /**
     * Checks if tags are set, otherwise the default tags will be returned
     * @param string $tags
     * @return string
     */
    public function tags($tag){
        if($tag == ''){
            return $this->_defaultTags;
        }else{
            return $tag;
        }
    }
    
     /**
     * Posts an image to Twitter
     * @param string $url Full local url to the photo to upload
     * @param string $title The title of the photo.
     * @param string $description A description of the photo. May contain some limited HTML.
     * @param string $tags A space-seperated list of tags to apply to the photo.
     */
    public function postImage($url, $title='', $description='', $tags=''){
        $file = realpath(str_replace(JUri::root(), JPATH_ROOT.DS, $url));
        $filename = basename($file);
        $imagetype = image_type_to_mime_type(exif_imagetype($file));
        $status = $this->title($title).' - '.$this->description($description).' '.$this->tags($tags);
        $params = array(
            'media[]' => "@".$file.";type=".$imagetype.";filename=".$filename,
            'status'  => $status
        );
        $tmhOAuth = new tmhOAuth($this->_oauthParams);
        $code = $tmhOAuth->user_request(
            array(
                'method' => 'POST',
                'url' => $tmhOAuth->url("1.1/statuses/update_with_media"),
                'params' => $params,
                'multipart' => true
            )
        );
        if ($code == 200) {
            return json_decode($tmhOAuth->response['response'], true);
        }else{
            return false;
        }
    }
    
    /**
     * Checks if the main component is installed
     * @return boolean
     */
    protected function canRun(){
        return SocialpromoterHelper::canRun();
    }
    
    /**
     * Returns an array with supported post types
     * @return array Array with the supported post types
     */
    public function getSupportedMethods(){
        return $this->supportedPosttypes;
    }
    
    /**
     * Checks if the post type is supported
     * @param string $type Type of post (link,image) from Socialpromoter::image;
     */
    public function canPost($type){
        return in_array($type, $this->supportedPosttypes);
    }
} //End Class