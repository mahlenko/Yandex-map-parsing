<?php

/**
 * Class Geography_position_m
 */
class Geography_position_m extends MY_Model
{
    protected $table = 'geography_position';
    protected $primary_key = 'geo_id';

    /**
     * Соберет весь список координатов для гео объекта
     * @param $geo_id
     * @param array $wheres
     * @return array|bool
     */
    public function find($geo_id, $wheres = [])
    {
        $results = $this->get($geo_id, $wheres);
        if (! $results) return false;

        $positions = [];
        foreach ($results as $item)
        {
            foreach (['lat', 'lon', 'lat_end', 'lon_end', 'spn_lat', 'spn_lon'] as $key) {
                if (isset($item->$key)) {
                    $item->$key = doubleval($item->$key);
                }
            }

            if ($item->type == 'bounding') {
                $positions[$item->geo_id][$item->type][$item->bounding] = $item;
            } else {
                $positions[$item->geo_id][$item->type] = $item;
            }
        }

        return $positions;
    }

    /**
     * Разбивает область на небольшие прямоугольники
     * по которым после проследует парсер.
     * @param array $bounding_box
     * @return array
     */
    public function boundingBoxes(array $bounding_box, $spn = [0.0951356390, 0.0355173052])
    {
        // размеры прямоугольника
        // долгота (y), широта (x)

        $bounding_start = $bounding_box[0];
        $bounding_end = $bounding_box[1];

        $coordinates = [];
        for ($i = 1, $w = $bounding_start[1]; $w < $bounding_end[1]; $w += $spn[1], $i++)
        {
            for ($k = 1, $h = $bounding_start[0]; $h < $bounding_end[0]; $h += $spn[0], $k++)
            {
                $coordinates[] = [
                    'lat' => $w,
                    'lon' => $h,
                    'lat_end' => $bounding_start[1] + ($spn[1] * $i),
                    'lon_end' => $bounding_start[0] + ($spn[0] * $k),
                    'spn_lat' => $spn[1],
                    'spn_lon' => $spn[0]
                ];
            }
        }

        return $coordinates;
    }


}
