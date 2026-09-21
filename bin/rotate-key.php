<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/Support/Encoding.php';
use TwoIzi\Guard\Support\Encoding;
$type=$argv[1]??'hmac';if(!in_array($type,['hmac','privacy','rate_limit'],true)){fwrite(STDERR,"Usage: php bin/rotate-key.php [hmac|privacy|rate_limit]\n");exit(2);} $kid=gmdate('Ymd').'-'.substr(bin2hex(random_bytes(3)),0,6);$value='base64url:'.Encoding::base64UrlEncode(random_bytes(32));echo "New {$type} key (not written automatically):\n";echo "kid: {$kid}\nvalue: {$value}\n\nMove the previous active key to verify_only only where supported, add this key as active, deploy, then retire old keys after all short-lived artifacts have expired. Keep rate_limit rotation coordinated because it intentionally starts fresh buckets.\n";
