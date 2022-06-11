<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Utils;

class PathInfo implements \Stringable
{

    public readonly string $basename;
    public readonly string $dirname;
    public readonly string $filename;
    public readonly string $extension;

    public function __construct(public string $path)
    {
        $info = pathinfo($path);
        $this->basename = $info['basename'];
        $this->dirname = $info['dirname'];
        $this->filename = $info['filename'];
        $this->extension = $info['extension'] ?? '';
    }

    public function __toString(): string
    {
        return $this->path;
    }

}
