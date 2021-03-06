<?php

namespace Minime\Annotations;

use Minime\Annotations\Interfaces\ParserInterface;
use Minime\Annotations\Interfaces\ParserRulesInterface;

/**
 * An Annotations parser
 *
 * @package Annotations
 * @author  Márcio Almada and the Minime Community
 * @license MIT
 *
 */
class Parser implements ParserInterface
{
    /**
     * The Doc block to parse
     *
     * @var string
     */
    private $raw_doc_block;

    /**
     * The ParserRules object
     *
     * @var ParserRulesInterface
     */
    private $rules;

    /**
     * The parsable type in a given docblock
     * declared in a ['token' => 'symbol'] associative array
     *
     * @var array
     */
    protected $types = [
        'Integer'  => 'integer',
        'String'   => 'string',
        'Float'    => 'float',
        'Json'     => 'json',
        'PHP'      => 'eval',
        'Concrete' => '->'
    ];

    /**
    * The regex equivalent of $types
    *
    * @var string
    */
    protected $types_pattern;

    /**
     * The regex to extract data from a single line
     *
     * @var string
     */
    protected $data_pattern;

    /**
     * Parser constructor
     *
     * @param string               $raw_doc_block the doc block to parse
     * @param ParserRulesInterface $rules
     */
    public function __construct($raw_doc_block, ParserRulesInterface $rules)
    {
        $this->raw_doc_block = preg_replace('/^\s*\*\s{0,1}|\/\*{1,2}|\s*\*\//m', '', $raw_doc_block);
        $this->types_pattern = '/^('.implode('|', $this->types).')(\s+)/';
        $this->rules = $rules;
        $identifier = $rules->getAnnotationIdentifier();
        $this->data_pattern = '/(?<=\\'.$identifier.')('
            .$rules->getAnnotationNameRegex()
            .')(((?!\s\\'.$identifier.').)*)/s';
    }

    /**
     * Parse a given docblock
     *
     * @return array
     */
    public function parse()
    {
        $annotations = $this->parseAnnotations($this->raw_doc_block);
        foreach ($annotations as &$value) {
            if (1 == count($value)) {
                $value = $value[0];
            }
        }
        unset($value);

        return $annotations;
    }

    /**
     * Creates raw [annotation => value, [...]] tree
     *
     * @param  string $str
     * @return array
     */
    protected function parseAnnotations($str)
    {
        $annotations = [];
        preg_match_all($this->data_pattern, $str, $found);
        foreach ($found[2] as $key => $value) {
            $annotations[ $this->rules->sanitizeKey($found[1][$key]) ][] = $this->parseValue($value, $found[1][$key]);
        }

        return $annotations;
    }

    /**
     * Parse a single annotation value
     *
     * @param  string          $value
     * @throws ParserException If the type is not recognized
     * @return mixed
     */
    public function parseValue($value, $key = null)
    {
        $value = trim($value);
        if ('' === $value) { // implicit boolean

            return true;
        }
        $type = 'Dynamic';
        if (preg_match($this->types_pattern, $value, $found)) { // strong typed
            $type = $found[1];
            $value = trim(substr($value, strlen($type)));
        }
        if (in_array($type, $this->types)) {
            $type = array_search($type, $this->types);
        }
        $typeParser = "Minime\\Annotations\\Types\\". $type;

        return (new $typeParser)->parse($value, $key);
    }

}
