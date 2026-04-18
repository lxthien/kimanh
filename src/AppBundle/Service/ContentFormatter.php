<?php

namespace AppBundle\Service;

use AppBundle\Utils\ConvertImages;
use AppBundle\Entity\News;

class ContentFormatter
{
    private $convertImages;

    public function __construct(ConvertImages $convertImages)
    {
        $this->convertImages = $convertImages;
    }

    public function stripTagsContent($string)
    {
        // ----- remove HTML TAGs -----
        $string = preg_replace('/<[^>]*>/', ' ', $string);
        // ----- remove control characters ----- 
        $string = str_replace("\r", '', $string);
        $string = str_replace("\n", ' ', $string);
        $string = str_replace("\t", ' ', $string);

        // ----- remove multiple spaces -----
        $string = trim(preg_replace('/ {2,}/', ' ', $string));

        return $string;
    }

    public function lazyloadContent(News $post)
    {
        $content = $post->getContents();

        // Return early if no content
        if (empty($content)) {
            return '';
        }

        // Protect lone "<" characters that are not part of HTML tags
        // Match "<" followed by a digit, space, or other non-tag characters
        $placeholder = '___LESS_THAN_PLACEHOLDER___';
        $content = preg_replace('/<(?=[0-9\s\-\+\=\.\,])/', $placeholder, $content);

        $dom = new \DOMDocument();

        // set error level
        $internalErrors = libxml_use_internal_errors(true);

        // Wrap content to preserve structure and handle UTF-8 properly
        $wrappedContent = '<div id="lazyload-wrapper">' . $content . '</div>';
        $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $wrappedContent,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        // Remove the XML declaration that was added
        foreach ($dom->childNodes as $item) {
            if ($item->nodeType == XML_PI_NODE) {
                $dom->removeChild($item);
            }
        }

        // Restore error level
        libxml_use_internal_errors($internalErrors);

        $imgs = $dom->getElementsByTagName('img');

        foreach ($imgs as $img) {
            $src = $img->getAttribute('src');
            $alt = $img->getAttribute('alt');

            list($width, $height) = @getimagesize(substr($src, 1));

            $src = !is_bool($this->convertImages->webpConvert2($src, '')) ? $this->convertImages->webpConvert2($src, '') : $src;

            $img->setAttribute('src', '/' . $src);
            $img->setAttribute('loading', 'lazy');
            $img->setAttribute('alt', !empty($alt) ? $alt : $post->getTitle());
            $img->setAttribute('width', !empty($width) ? ($width > 900 ? 900 : $width) : 500);
            $img->setAttribute('height', !empty($height) ? ($width > 900 ? round(($height * 900) / $width) : $height) : 500);
        }

        $newContent = $dom->saveHTML();

        // Remove the wrapper div we added
        $newContent = preg_replace('/<div id="lazyload-wrapper">/', '', $newContent);
        $newContent = preg_replace('/<\/div>$/', '', $newContent);

        // Clean up any remaining DOCTYPE, html, head, body tags
        $newContent = preg_replace('/^<!DOCTYPE[^>]*>/i', '', $newContent);
        $newContent = preg_replace('/<\/?html[^>]*>/i', '', $newContent);
        $newContent = preg_replace('/<\/?head[^>]*>/i', '', $newContent);
        $newContent = preg_replace('/<\/?body[^>]*>/i', '', $newContent);

        // Restore the "<" characters
        $newContent = str_replace($placeholder, '<', $newContent);

        return trim($newContent);
    }
}
