<?php

use DiDom\Document;

class Test extends MY_Controller
{

    private $container_size = [0.0951356390, 0.0355173052];

    private $sections;


    public function emails()
    {
        $this->load->model('Company/company_emails_m');

        $urls = ['https://честный-автосервис.рф/', 'https://l-f-a.ru/', 'http://plusauto.org/'];
        $this->company_emails_m->findAndSave($urls, 1);
    }



    public function region()
    {
        $this->load->model('Requests/region_requests');

        $region = $this->region_requests->getRegion('Беларусь, Гомель');
        $this->data('REGION', $region);

        $geometries = $region->displayGeometry->geometries;

        foreach ($geometries as $index => $object)
        {
            foreach ($object->coordinates as $coordinates)
            {
                // получаем все координаты долготы
                $lon_arr = array_column($coordinates, 0);
                // получаем все координаты широты
                $lat_arr = array_column($coordinates, 1);
                $size = [
                    'coordinates' => [
                        min($lon_arr),
                        min($lat_arr)
                    ],
                    'spn' => [
                        max($lon_arr) - min($lon_arr),
                        max($lat_arr) - min($lat_arr)
                    ],
                    'spn' => $this->container_size
                ];

                // y
                $lon_size = ceil((max($lon_arr) - min($lon_arr)) / $size['spn'][0]);
                // x
                $lat_size = ceil((max($lat_arr) - min($lat_arr)) / $size['spn'][1]);

                for($lon = 0; $lon < $lon_size; $lon++)
                {
                    for($lat = 0; $lat < $lat_size; $lat++)
                    {
                        // поиск верха от куда начинаем рисовать контейнеры
                        $container = [
                            'coordinates' => [
                                $size['coordinates'][0] + ($this->container_size[0] * $lon),
                                $size['coordinates'][1] + ($this->container_size[1] * $lat)
                            ],
                            'spn' => $size['spn']
                        ];

                        // создаём контейнер на карте
                        if ($this->newContainer($container, $coordinates)) {
                            $this->sections[$index][] = $container;
                        }
                    }
                }
            }
        }

        $this->data('SECTION', $this->sections);

        return $this->twig->display('test', $this->data());
    }


    /**
     * Разрешает создать контейнер в указанных
     * координатах карты, если там есть хотя бы
     * 2 точки.
     * @param $container
     * @param $coordinates
     * @return bool
     */
    private function newContainer($container, $coordinates)
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
        return $points_in_container > 1 ? true : false;
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
