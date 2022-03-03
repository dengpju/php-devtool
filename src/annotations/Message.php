<?php


namespace PHPDTool\annotations;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target({"ALL"})
 */
class Message
{
    /**
     * @param string $docComment
     * @return string
     */
    public static function parse(string $docComment): string
    {
        preg_match_all("/(?<=(\@Message\(\")).*?(?=(\"\)))/", $docComment, $doc);
        if ($doc) {
            if (isset($doc[0]) && isset($doc[0][0]) && !empty($doc[0][0])) {
                return trim($doc[0][0], '"');
            }
        }
        return "";
    }
}