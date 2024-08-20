<?php
/**
 * @author Torleif west <torleifw@op.ac.nz>
 */
namespace OP;

use SilverStripe\Core\Cache\CacheFactory;
use SilverStripe\Core\Cache\FilesystemCacheFactory;
use SilverStripe\Core\Path;

class AssetCacheFactory extends FilesystemCacheFactory
{
    public function __construct($directory = null)
    {
        $directory =  Path::join(ASSETS_PATH, 'jsoncache');
        parent::__construct($directory);
    }
}
