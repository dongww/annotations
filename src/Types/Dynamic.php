<?php

namespace Minime\Annotations\Types;

use Minime\Annotations\Interfaces\TypeInterface;
use Minime\Annotations\Types\Json;

class Dynamic implements TypeInterface
{

    /**
     * Parse a given undefined type value
     *
     * @param  string $value
     * @return mixed
     */
    public function parse($value, $annotation = null)
    {
        $json = Json::jsonDecode($value);
        if (JSON_ERROR_NONE === json_last_error()) {
            return $json;
        } elseif (false !== ($float = filter_var($value, FILTER_VALIDATE_FLOAT))) {
            return $float;
        }

        return $value;
    }

}
