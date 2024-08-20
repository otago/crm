<?php
/**
 * @author Torleif west <torleifw@op.ac.nz>
 */
namespace OP;

use SilverStripe\Core\Cache\CacheFactory;
use SilverStripe\Core\Cache\FilesystemCacheFactory;

class AssetCacheFactory extends FilesystemCacheFactory
{
    public function __construct($directory = null)
    {
        $directory = ASSETS_PATH . DIRECTORY_SEPARATOR. 'jsoncache';
        parent::__construct($directory);
    }
}
