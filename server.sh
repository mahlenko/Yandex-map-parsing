#!/bin/bash
echo "---------------------------------------"
echo " Бекап категорий"
#php -f index.php import rubrics
wait
echo " ОК "

echo "--------------------------------------";
echo " Готов получать компании";

while true; do
php -f index.php tasks run
wait
done