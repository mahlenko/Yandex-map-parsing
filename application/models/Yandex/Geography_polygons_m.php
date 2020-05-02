<?php

class Geography_polygons_m extends MY_Model
{
    protected $table = 'geography_polygons';
    protected $primary_key = 'geo_id';

    /**
     * Полигоны региона. Вернет сгруппированный массив,
     * по id региона.
     * @param null $primary_keys
     * @param array $wheres
     * @return array
     */
    public function find($primary_keys = null, $wheres = [])
    {
        $polygons = $this->get($primary_keys, $wheres);
        if (! $polygons) return $polygons;

        $result = [];
        foreach ($polygons as $polygon) {
            $result[$polygon->{$this->primary_key}][$polygon->area_id][] = $polygon;
        }

        return $result;
    }
}