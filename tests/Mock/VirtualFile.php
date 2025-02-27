<?php

namespace AlibabaCloud\Credentials\Tests\Mock;

use AlibabaCloud\Configure\Config;
use org\bovigo\vfs\vfsStream;

class VirtualFile
{

    /**
     * @var string VirtualFile Content
     */
    private $content;

    /**
     * VirtualFile constructor.
     *
     * @param string $content
     */
    public function __construct($content)
    {
        $this->content = $content;
    }

    /**
     * @param string $content
     *
     * @return VirtualFile
     */
    public static function content($content = '')
    {
        return new static($content);
    }

    /**
     * @param string $fileName
     *
     * @return string VirtualFile Filename
     */
    public function url($fileName = 'file')
    {
        return vfsStream::newFile($fileName)
                        ->withContent($this->content)
                        ->at(vfsStream::setup(Config::KEY))
                        ->url();
    }
}
