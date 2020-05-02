<?php

class Geography_containers_m extends MY_Model
{
    protected $table = 'geography_containers';
    protected $primary_key = 'geo_id';

    /**
     * Минимальное количество точек,
     * которое должно входить в контейнер, для его создания.
     * (обычно, нет особого смысла парсить контейнер,
     * где зацепили одну точку)
     * @var int
     */
    private $minimal_points = 2;

    /**
     * Размеры одного контейнера региона
     * @var array
     */
    private $container_size = [0.0951356390, 0.0355173052];

    /**
     * Поиск контейнеров по указанным регионам.
     * Вернет сгруппированный массив, по id регона.
     * @param null $primary_keys
     * @param array $wheres
     * @return array
     */
    public function find($primary_keys = null, $wheres = [], $order_by = [])
    {
        $containers = $this->get($primary_keys, $wheres, $order_by);
        if (! $containers) return $containers;

        $result = [];
        foreach ($containers as $container) {
            $result[$container->{$this->primary_key}][] = $container;
        }

        return $result;
    }

    /**
     * Создаст контейнеры парсинга
     * @param int $geo_id
     * @param $geometries
     */
    public function create($geo_id, $geometries)
    {
        foreach ($geometries as $index => $area)
        {
            foreach ($area->coordinates as $coordinates)
            {
                // получаем все координаты долготы
                $lon_arr = array_column($coordinates, 0);
                // получаем все координаты широты
                $lat_arr = array_column($coordinates, 1);

                // начальная точка и координаты контейнера
                $begin_container = [
                    'coordinates' => [
                        min($lon_arr),
                        min($lat_arr)
                    ],
                    'spn' => $this->container_size
                ];

                // количество контейнеров по долготе
                $lon_count = ceil((max($lon_arr) - min($lon_arr)) / $begin_container['spn'][0]);

                // количество контейнеров по широте
                $lat_count = ceil((max($lat_arr) - min($lat_arr)) / $begin_container['spn'][1]);

                // проходим по контейнерам и точкам
                for($lon = 0; $lon < $lon_count; $lon++)
                {
                    for($lat = 0; $lat < $lat_count; $lat++)
                    {
                        // создаем координаты и размеры нового контейнера
                        $container = [
                            'coordinates' => [
                                $begin_container['coordinates'][0] + ($this->container_size[0] * $lon),
                                $begin_container['coordinates'][1] + ($this->container_size[1] * $lat)
                            ],
                            'spn' => $begin_container['spn']
                        ];

                        // проверяем можно ли создать тут контейнер
                        if ($this->isPossibleCreateContainer($container, $coordinates)) {
                            // сохраняем контейнер
                            $this->save(null, [
                                'geo_id'    => $geo_id,
                                'lon'       => $container['coordinates'][0],
                                'lat'       => $container['coordinates'][1],
                                'spn_lon'   => $container['spn'][0],
                                'spn_lat'   => $container['spn'][1],
                            ]);

                            // Сохраним контейнер для показа на карте
                            // $this->sections[$index][] = $container;
                        }
                    }
                }
            }
        }
    }

    /**
     * Разрешает создать контейнер в указанных
     * координатах карты, если там есть хотя бы
     * 2 точки.
     * @param $container
     * @param $coordinates
     * @return bool
     */
    private function isPossibleCreateContainer($container, $coordinates)
    {
        $points_in_container = 0;

        // проверяем что данныя точка не входит в область которая уже нарисована
        foreach ($coordinates as $point) {
            if ($this->inPoly($point[1], $point[0], $container)) {
                // если входит, разрешаем создать контейнер для парсинга
                $points_in_container++;
            }
        }

        // нет ни одной точки многоугольника
        return $points_in_container >= $this->minimal_points ? true : false;
    }

    /**
     * Входит ли точка в контейнер
     * @param $x
     * @param $y
     * @param $area
     * @return bool
     */
    private function inPoly($x, $y, $area)
    {
        $skip = 0.015;

        $xp = [
            $area['coordinates'][1] - $skip,
            $area['coordinates'][1] + $area['spn'][1] - $skip
        ];

        $yp = [
            $area['coordinates'][0] - $skip,
            $area['coordinates'][0] + $area['spn'][0] - $skip
        ];

        $j = count($xp) - 1;
        for ($i = 0; $i < count($xp); $i++) {
            if (
                (($yp[$i] <= $y && $y <= $yp[$j]) || ($yp[$j] < $y && $y < $yp[$i]))
                && ($x > ($xp[$j] - $xp[$i]) * ($y - $yp[$i]) / ($yp[$j] - $yp[$i]) + $xp[$i])
            ) {
                return true;
            }

            $j = $i;
        }

        return false;
    }

}