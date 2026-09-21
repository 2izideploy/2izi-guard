<?php
declare(strict_types=1);

$raw = random_bytes(32);
echo 'base64url:' . rtrim(strtr(base64_encode($raw), '+/', '-_'), '=') . PHP_EOL;
