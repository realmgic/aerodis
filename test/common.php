<?php
function dump($a)
{
    ob_start();
    var_dump($a);
    $aa = ob_get_contents();
    ob_clean();
    return trim($aa);
}

function compare($a, $b)
{
    try {
        if ($a !== $b) {
            throw new \ErrorException('Assert failed : <' . dump($a) . '> != <' . dump($b) . '>');
        }
    } catch (\Exception $e) {
        echo $e->getTraceAsString() . "\n";
        exit;
    }
}

function upper($a, $b)
{
    try {
        if ($a < $b) {
            throw new \ErrorException('Must ' . dump($a) . ' >= ' . dump($b));
        }
    } catch (\Exception $e) {
        echo $e->getTraceAsString() . "\n";
        exit;
    }
}

function lower($a, $b)
{
    try {
        if ($a > $b) {
            throw new ErrorException('Must ' . dump($a) . ' <= ' . dump($b));
        }
    } catch (\Exception $e) {
        echo $e->getTraceAsString() . "\n";
        exit;
    }
}

function compare_map($a, $b)
{
    ksort($a);
    ksort($b);
    compare($a, $b);
}