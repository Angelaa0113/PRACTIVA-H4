<?php
$nombres = ["Candia", "Poma", "Siles", "Figueredo"];

function prueba($num, $nombres, $secuencial)
{
    echo "\n--- Prueba $num ---\n";

    $cola = [];
    $resultados = [];
    $tamanos = [];
    $lock = false;

    for ($i = 1; $i <= 24; $i++) {
        $cola[] = [$i, rand(1, 5)];
    }
    $copia = $cola;

    $trabajador = function ($nombre) use (&$cola, &$resultados, &$tamanos, &$lock) {
        $docs = [];
        $suma = 0;

        // cada uno procesa 6 documentos 
        while (count($docs) < 6 && count($cola) > 0) {
            [$id, $tam] = array_shift($cola);

            $fin = microtime(true) + $tam * 0.1;
            while (microtime(true) < $fin) {
                Fiber::suspend();
            }

            $docs[] = $id;
            $suma += $tam;
        }

        while ($lock) {
            Fiber::suspend();
        }
        $lock = true;
        $resultados[$nombre] = $docs;
        $tamanos[$nombre] = $suma;
        $lock = false;
    };

    $hilos = [];
    foreach ($nombres as $nombre) {
        $hilos[] = new Fiber(function () use ($trabajador, $nombre) {
            $trabajador($nombre);
        });
    }

    $inicio = microtime(true);

    foreach ($hilos as $hilo) {
        $hilo->start();
    }

    $quedan = true;
    while ($quedan) {
        $quedan = false;
        foreach ($hilos as $hilo) {
            if (!$hilo->isTerminated()) {
                $hilo->resume();
                $quedan = true;
            }
        }
        usleep(1000);
    }

    $tiempo = microtime(true) - $inicio;

    $total = 0;
    $ids = [];
    foreach ($nombres as $nombre) {
        $docs = $resultados[$nombre];
        sort($docs);
        echo "$nombre: " . count($docs) . " documentos [" . implode(", ", $docs) . "] tamaño total: " . $tamanos[$nombre] . "\n";
        $total += count($docs);
        $ids = array_merge($ids, $docs);
    }
    echo "Total procesado: $total\n";

    $terminados = 0;
    foreach ($hilos as $hilo) {
        if ($hilo->isTerminated()) {
            $terminados++;
        }
    }

    echo "Verificacion 1 (suma 24): " . ($total == 24 ? "OK" : "ERROR") . "\n";
    echo "Verificacion 2 (ids unicos): " . (count(array_unique($ids)) == 24 ? "OK" : "ERROR") . "\n";
    echo "Verificacion 3 (hilos terminados): " . ($terminados == 4 ? "OK" : "ERROR") . "\n";
    echo "Tiempo con 4 hilos: " . round($tiempo, 2) . " s\n";

    if ($secuencial) {
        $inicio = microtime(true);
        foreach ($copia as [$id, $tam]) {
            usleep($tam * 100000);
        }
        echo "Tiempo secuencial: " . round(microtime(true) - $inicio, 2) . " s\n";
    }
}

for ($n = 1; $n <= 4; $n++) {
    prueba($n, $nombres, $n == 1);
}
