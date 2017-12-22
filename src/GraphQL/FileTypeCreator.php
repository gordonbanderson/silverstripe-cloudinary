<?php

namespace MadeHQ\Cloudinary\GraphQL;

use GraphQL\Type\Definition\Type;
use SilverStripe\AssetAdmin\GraphQL\FileTypeCreator As BaseFileTypeCreator;
use SilverStripe\AssetAdmin\Controller\AssetAdmin;
use SilverStripe\AssetAdmin\Forms\UploadField;

class FileTypeCreator extends BaseFileTypeCreator
{
    public function fields()
    {
        $fields = parent::fields();
        $fields['id']['type'] = Type::string();
        return $fields;
    }

    public function resolveSmallThumbnailField($object, array $args, $context, $info)
    {
        $width = UploadField::config()->uninherited('thumbnail_width');
        $height = UploadField::config()->uninherited('thumbnail_height');
        return cloudinary_url($object->ID, ['width' => $width, 'height' => $height]);
    }

    public function resolveThumbnailField($object, array $args, $context, $info)
    {
        $width = AssetAdmin::config()->uninherited('thumbnail_width');
        $height = AssetAdmin::config()->uninherited('thumbnail_height');
        return cloudinary_url($object->ID, ['width' => $width, 'height' => $height]);
    }

    public function resolveUrlField($object, array $args, $context, $info)
    {
        return $object->URL;
    }

    /**
     * @param File $object
     * @param array $args
     * @param array $context
     * @param ResolveInfo $info
     * @return string
     */
    public function resolveCategoryField($object, array $args, $context, $info)
    {
// var_dump(func_get_args());die;
        return 'image';
        return $object instanceof Folder ? 'folder' : $object->appCategory();
    }
}
