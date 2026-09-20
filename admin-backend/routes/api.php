<?php
declare(strict_types=1);

function api_route(string $method,string $path):bool{
 return ($_SERVER['REQUEST_METHOD']??'GET')===$method &&
        (parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)===$path);
}
