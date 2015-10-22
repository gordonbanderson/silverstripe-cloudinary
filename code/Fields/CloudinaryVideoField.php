<?php

class CloudinaryVideoField extends CloudinaryUploadField
{

    /**
     * @var string
     */
    protected $templateFileButtons = 'CloudinaryVideoField_FileButtons';

    /**
     * @var array
     */
    private static $allowed_actions = array(
        'process',
        'delete_image',
    );

    public function Field($properties = array())
    {
        $parent = parent::Field($properties);
        Requirements::javascript(CLOUDINARY_RELATIVE . "/javascript/CloudinaryVideoField_downloadtemplate.js");
        Requirements::javascript(CLOUDINARY_RELATIVE . "/javascript/CloudinaryVideoField.js");
        Requirements::css(CLOUDINARY_RELATIVE . '/css/CloudinaryVideoField.css');

        return $parent;

    }

    public function getExtensionsAllowed(){
        $strExtensions = CloudinaryVideoField::config()->allowedExtensions;
        return explode(',', $strExtensions);
    }

    public function VideoURL()
    {
        $arrFiles = reset($this->value);
        if (isset($arrFiles[0]) && $arrFiles[0]) {
            $video = CloudinaryVideo::get()->byID($arrFiles[0]);
            if ($video)
                return $video->getField('URL');
        }
    }

    public function processURL()
    {
        return $this->Link('process');
    }

    public function process()
    {

        if (isset($_POST['SourceURL'])) {
            $sourceURL = $_POST['SourceURL'];
            $bIsCloudinary = CloudinaryVideo::isCloudinary($sourceURL);
            $bIsYoutube = YoutubeVideo::isYoutube($sourceURL);
            $bIsVimeo = VimeoVideo::isVimeo($sourceURL);
            $video = null;
            if ($bIsYoutube || $bIsVimeo || $bIsCloudinary) {
                if($bIsCloudinary){
                    $filterClass = 'CloudinaryVideo';
                    $fileType = 'video';
                }elseif($bIsYoutube){
                    $filterClass = 'YoutubeVideo';
                    $fileType = 'youtube';
                }else{
                    $filterClass = 'VimeoVideo';
                    $fileType = 'vimeo';
                }

                $funcForID = $bIsYoutube ? 'youtube_id_from_url' : 'vimeo_id_from_url';
                $funcForDetails = $bIsYoutube ? 'youtube_video_details' : 'vimeo_video_details';
                if($bIsCloudinary){
                    $arr = Config::inst()->get('CloudinaryConfigs', 'settings');
                    if(isset($arr['CloudName']) && !empty($arr['CloudName'])){
                        $arrPieces = explode('/', $sourceURL);
                        $arrFileName = array_slice($arrPieces, 7);
                        $fileName = implode('/', $arrFileName);
                        $publicID = substr($fileName, 0, (strrpos($fileName, '.')));

                        $video = $filterClass::get()->filterAny(array(
                            'URL'       => $sourceURL,
                            'PublicID'  => $publicID
                        ))->first();
                        if(!$video){
                            $api = new \Cloudinary\Api();
                            $resource = $api->resource($publicID, array("resource_type" => "video"));;//qoogjqs9ksyez7ch8sh5
                            $json = json_encode($resource);
                            $arrResource = Convert::json2array($json);

                            $video = new $filterClass(array(
                                'Title'     => $arrResource['public_id']. '.' .$arrResource['format'],
                                'PublicID'  => $arrResource['public_id'],
                                'Version'	=> $arrResource['version'],
                                'URL'		=> $arrResource['url'],
                                'SecureURL'	=> $arrResource['secure_url'],
                                'FileType'	=> $arrResource['resource_type'],
                                'FileSize'	=> $arrResource['bytes'],
                                'Format'	=> $arrResource['format'],
                                'Signature'	=> isset($arrResource['signature']) ? $arrResource['signature'] : '',
                            ));
                            $video->write();
                        }
                    }

                }else{
                    $video = $filterClass::get()->filter('URL', $sourceURL)->first();
                    if(!$video){
                        $sourceID = $filterClass::$funcForID($sourceURL);
                        $details = $filterClass::$funcForDetails($sourceID);
                        $video = new $filterClass(array(
                            'Title'         => $details['title'],
                            'Duration'      => $details['duration'],
                            'URL'           => $sourceURL,
                            'secure_url'    => $sourceURL,
                            'PublicID'      => $sourceID,
                            'FileType'      => $fileType,
                        ));
                        $video->write();
                    }
                }
                if ($video) {
                    $this->value = $iVideoID = $video->ID;
                    $file =  $this->customiseCloudinaryFile($video);

                    return Convert::array2json(array(
                        'colorselect_url'   => $file->UploadFieldImageURL,
                        'thumbnail_url'     => $file->UploadFieldThumbnailURL,
                        'fieldname'         => $this->getName(),
                        'id'                => $file->ID,
                        'url'               => $file->URL,
                        'buttons'           => $file->UploadFieldFileButtons,
                        'more_files'        => $this->canUploadMany(),
                        'field_id'          => $this->ID()
                    ));
                }
            }
        }
        return Convert::array2json(array());

    }

    public function canUploadMany(){
        $record = $this->getRecord();
        return ($record instanceof DataObject)
            && $record->hasMethod($this->getName())
            && $record->{$this->getName()}() instanceof RelationList;
    }

    public function DeleteLink()
    {
        return $this->Link('delete_image');
    }

    public function delete_image()
    {
        if ($this->value) {
            $this->value = 0;
        }
    }

    public function IsUploaded()
    {
        return !empty($this->value);
    }

    public function Thumbnail()
    {
        if ($video = CloudinaryFile::get()->byID($this->value)) {
            return $video->GetFileImage(80, 60, 90);
        }
    }

    public function ColorSelectThumbnail()
    {
        if ($video = CloudinaryFile::get()->byID($this->value)) {
            return $video->GetFileImage(200, 112, 90);
        }
    }

} 