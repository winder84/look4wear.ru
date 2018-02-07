<?php

namespace AppBundle\External;

use Liip\ImagineBundle\Binary\Loader\LoaderInterface;
use Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException;

class RemoteLoader implements LoaderInterface
{
    /**
     * @param mixed $path
     * @return string
     */
    public function find($path)
    {
        if (!preg_match('{https?://}', $path)) {
            throw new NotLoadableException(sprintf('This is not absolute URI: %s.', $path));
        }

        $ch = curl_init($path);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)');
        $content = curl_exec($ch);

        $exists = curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200;

        if (!$exists) {
            throw new NotLoadableException(sprintf('Source image %s not found.', $path));
        }

        if (false === $content) {
            throw new NotLoadableException(sprintf('Source image %s could not be loaded.', $path));
        }

        return $content;
    }

}