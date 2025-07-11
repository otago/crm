<?php

namespace OP;

use SilverStripe\ORM\DataObject;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Config\Configurable;




class CRMResult
{
    use Injectable;
    use Configurable;

    private $body;
    private $code;
    private $headers;

    public function __construct($body, $code, $headers)
    {
        $this->body = $body;
        $this->code = $code;
        $this->headers = $headers;
    }

    /**
     * is this request successful
     * @return bool
     */
    public function isSuccessfulRequest()
    {
        return $this->code >= 200 && $this->code <= 299;
    }

    /**
     * Recursivity creates the SilverStripe dataobject representation of content
     * @param mixed $array
     * @return \DataObject|\DataList|null
     */
    private function parseobject($array)
    {
        if (is_object($array)) {
            if (get_class($array) == DataObject::class) {
                return $array;
            }
            $do = DataObject::create();
            foreach (get_object_vars($array) as $key => $obj) {
                if ($key == '__Type') {
                    $do->setField('Title', $obj);
                } else if (is_array($obj) || is_object($obj)) {
                    $do->setField($key, $this->parseobject($obj));
                } else {
                    $do->setField($key, $obj);
                }
            }
            return $do;
        } else if (is_array($array)) {
            $dataList = ArrayList::create();
            foreach ($array as $key => $obj) {
                $dataList->push($this->parseobject($obj));
            }
            return $dataList;
        }
        return null;
    }

    /**
     * Returns SilverStripe object representations of content
     * @return \DataObject|\DataList|null
     */
    public function Data()
    {
        return $this->parseobject(json_decode($this->body));
    }

    /**
     * returns json string
     * @return string
     */
    public function Body()
    {
        return $this->body;
    }

    /**
     * returns the raw header data
     * @return string
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
