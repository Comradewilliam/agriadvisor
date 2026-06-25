<?php
foreach ([3306, 8889] as $port) {
    try {
        $p = new PDO("mysql:host=127.0.0.1;port={$port}", 'root', 'root');
        echo "Port {$port} OK\n";
        foreach ($p->query('SHOW DATABASES') as $r) {
            echo "  - {$r[0]}\n";
        }
    } catch (Throwable $e) {
        echo "Port {$port}: {$e->getMessage()}\n";
    }
}
