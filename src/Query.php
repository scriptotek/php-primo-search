<?php

namespace Scriptotek\PrimoSearch;

use InvalidArgumentException;

class Query
{
    protected $data = [
        'q' => [],
        'qInclude' => [],
        'qExclude' => [],
        'multifacets' => [],
        'sort' => 'rank',
        'offset' => 0,
        'limit' => 10,
    ];

    public static function new()
    {
        return new static;
    }

    public function where($field, $op, $value)
    {
        $this->data['q'][] = new QueryPart($field, $op, $value);
        return $this;
    }

    public function orWhere($field, $op, $value)
    {
        if (!count($this->data['q'])) {
            throw new \Error('Cannot start query with OR');
        }
        $this->data['q'][count($this->data['q']) - 1]->setOperator('OR');
        $this->data['q'][] = new QueryPart($field, $op, $value);
        return $this;
    }

    public function not($field, $op, $value)
    {
        if (!count($this->data['q'])) {
            throw new \Error('Cannot start query with NOT');
        }
        $this->data['q'][count($this->data['q']) - 1]->setOperator('NOT');
        $this->data['q'][] = new QueryPart($field, $op, $value);
        return $this;
    }

    public function build()
    {
        if (!count($this->data['q'])) {
            throw new \Error('Query is empty');
        }

        $params = [];
        foreach ($this->data as $key => $val) {
            if (is_array($val)) {
                if (count($val)) {
                    $glue = ($key == 'q') ? ';' : '|,|';
                    $val = array_map(function ($part) {
                        return is_object($part) ? $part->build() : implode(',', $part);
                    }, $val);
                    $params[$key] = implode($glue, $val);
                }
            } else {
                $params[$key] = (string) $val;
            }
        }

        return $params;
    }

    /**
     * Filter results by including facet values.
     *
     * @param string $category        The facet type
     * @param string|array  $values   One or more facet values (string or array)
     * @param string $conjuction      The logical conjuction (AND or OR) to use between the facet values.
     */
    public function includeFacetValues($category, $values, $conjuction = 'OR')
    {
        if (!is_array($values)) {
            $values = [$values];
        }
        foreach ($values as $value) {
            if ($conjuction == 'OR') {
                $this->data['multifacets'][] = [$category, 'include', $value];
            } elseif ($conjuction == 'AND') {
                $this->data['qInclude'][] = [$category, 'exact', $value];
            } else {
                throw new InvalidArgumentException('Invalid operator: ' . $conjuction);
            }
        }
        return $this;
    }

    /**
     * Filter results by excluding facet values.
     *
     * @param string $category        The facet type
     * @param string|array  $values   One or more facet values (string or array)
     * @param string $conjuction      The logical conjuction (AND or OR) to use between the facet values.
     */
    public function excludeFacetValues($category, $values, $conjuction = 'OR')
    {
        if (!is_array($values)) {
            $values = [$values];
        }
        foreach ($values as $value) {
            if ($conjuction == 'OR') {
                $this->data['multifacets'][] = [$category, 'exclude', $value];
            } elseif ($conjuction == 'AND') {
                $this->data['qExclude'][] = [$category, 'exact', $value];
            } else {
                throw new InvalidArgumentException('Invalid operator: ' . $conjuction);
            }
        }
        return $this;
    }

    public function sort(string $value)
    {
        $this->data['sort'] = $value;
        return $this;
    }

    public function offset(int $value)
    {
        $this->data['offset'] = $value;
        return $this;
    }

    public function limit(int $value)
    {
        $this->data['limit'] = $value;
        return $this;
    }
}
