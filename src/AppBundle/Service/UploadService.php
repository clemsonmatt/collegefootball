<?php

namespace AppBundle\Service;

use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service("collegefootball.team.upload")
 */
class UploadService
{
    public function uploadImage($image, $namespace)
    {
        $extension = $image->guessExtension();
        $orgName   = str_replace(" ", "", $image->getClientOriginalName());
        $orgName   = str_replace("#", "", $orgName);
        $imageName = str_replace('.'.$extension, "", $orgName);

        $hash         = time();
        $hash         = md5($hash);
        $uniqueSecret = substr(str_shuffle($hash), 0, 15);

        $imagePath = $imageName.'-'.$uniqueSecret.'.'.$extension;
        $image->move('uploads/'.$namespace, $imagePath);

        return $imagePath;
    }
}
