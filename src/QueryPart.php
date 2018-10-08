<?php

namespace Scriptotek\PrimoSearch;

class QueryPart
{
    protected $field;
    protected $precision;
    protected $value;
    protected $conjugation = 'AND';

    protected $aliases = [
        '=' => 'exact',
        '~' => 'contains',
        '^' => 'begins with',
    ];

    public function __construct($field, $precision, $value, $conjugation = 'AND')
    {
        $this->field = $field;
        $this->precision = $this->aliases[$precision] ?? $precision;
        $this->value = $value;
        $this->conjugation = $conjugation;
    }

    public function setOperator($conjugation)
    {
        $this->conjugation = $conjugation;
    }

    public function build()
    {
        return "{$this->field},{$this->precision},{$this->value},{$this->conjugation}";
    }
}
