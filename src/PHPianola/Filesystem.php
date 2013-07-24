<?php
namespace PHPianola;

/**
 * Filesystem
 *
 * @author warmans
 */
class Filesystem extends \Symfony\Component\Filesystem\Filesystem
{
    /**
     * @param string $path
     * @param string $open_mode
     * @return \SplFileObject
     */
    public function getFile($path, $open_mode='r')
    {
        return new \SplFileObject($path, $open_mode);
    }

    /**
     * Find some files in the specified directory
     *
     * @param string $named
     * @param sring $in
     * @return \Iterator
     */
    public function findFiles($named, $in)
    {
        $finder = new \Symfony\Component\Finder\Finder();
        return $finder->files()->name($named)->in($in);
    }
}