<?php
declare(strict_types=1);
return [
    'mode'=>'shadow', // shadow | adaptive | invisible | disabled
    'origin'=>'https://example.com','require_origin_header'=>true,
    'database'=>['dsn'=>'mysql:host=127.0.0.1;dbname=example;charset=utf8mb4','user'=>'example','password'=>'CHANGE_ME','options'=>[]],
    'keys'=>[
        'hmac'=>['active'=>'h1','keys'=>['h1'=>['value'=>'base64url:CHANGE_ME','status'=>'active']]],
        'privacy'=>['active'=>'p1','keys'=>['p1'=>['value'=>'base64url:CHANGE_ME','status'=>'active']]],
        'rate_limit'=>['active'=>'r1','keys'=>['r1'=>['value'=>'base64url:CHANGE_ME','status'=>'active']]],
    ],
    'cookie'=>['name'=>'__Host-izi_guard','ttl'=>7200,'secure'=>true,'samesite'=>'Lax'],
    'trusted_proxies'=>[],
    'emergency'=>['enabled'=>false,'storage_path'=>dirname(__DIR__).'/storage/emergency'],
    'rate_limit'=>[
        'backend'=>'database', // database | redis
        'redis'=>['host'=>'127.0.0.1','port'=>6379,'timeout'=>1.0,'database'=>0,'password'=>'','prefix'=>'izi_guard:rl:'],
    ],
    // Optional. Runs only on the server; never populate account/signals from browser JSON.
    'server_context_provider'=>static function(string $action): array {
        return ['account_id'=>null,'signals'=>[],'assurances'=>[]];
    },
    'defaults'=>['challenge_ttl'=>90,'token_ttl'=>120,'max_verify_attempts'=>5,'pow_min_difficulty'=>13,'pow_max_difficulty'=>19,'pow_target_ms'=>450,
        'verify_limits'=>['global'=>['capacity'=>3000,'period'=>60],'network'=>['capacity'=>180,'period'=>60]],
        'consume_limits'=>['global'=>['capacity'=>5000,'period'=>60],'network'=>['capacity'=>300,'period'=>60]],'event_retention_days'=>7],
    'localization'=>['default'=>'en','available'=>['en','ru','zh-CN','ja','it']],
    'actions'=>[
        'contact'=>[
            'mode'=>'adaptive','honeypot'=>true,
            'pow'=>['min_difficulty'=>13,'max_difficulty'=>18,'target_ms'=>450],
            'interactive'=>['min_hold_ms'=>1200],
            'thresholds'=>['pow'=>30,'interactive'=>70,'deny'=>94],
            'limits'=>['global'=>['capacity'=>1000,'period'=>60],'network'=>['capacity'=>30,'period'=>60],'session'=>['capacity'=>10,'period'=>60],'account'=>['capacity'=>15,'period'=>60]],
            'application_signals'=>['recent_failed_actions'=>18,'trusted_customer'=>-12],
            'fail_mode'=>'open_with_limit','emergency_limit'=>['capacity'=>3,'period'=>60],
        ],
        'login'=>[
            'mode'=>'adaptive','honeypot'=>false,
            'pow'=>['min_difficulty'=>14,'max_difficulty'=>19,'target_ms'=>550],
            'interactive'=>['min_hold_ms'=>1400],
            'thresholds'=>['pow'=>20,'interactive'=>55,'deny'=>88],
            'limits'=>['global'=>['capacity'=>500,'period'=>60],'network'=>['capacity'=>20,'period'=>60],'session'=>['capacity'=>8,'period'=>60],'account'=>['capacity'=>8,'period'=>60]],
            'application_signals'=>['recent_failed_logins'=>22,'known_good_session'=>-10],
            // Example: uncomment for a critical authenticated action. Values must come from server state only.
            // 'required_assurances'=>['verified_account','mfa'],
            'fail_mode'=>'closed',
        ],
        'register'=>[
            'mode'=>'adaptive','honeypot'=>true,
            'pow'=>['min_difficulty'=>14,'max_difficulty'=>19,'target_ms'=>550],
            'interactive'=>['min_hold_ms'=>1400],
            'thresholds'=>['pow'=>22,'interactive'=>58,'deny'=>88],
            'limits'=>['global'=>['capacity'=>400,'period'=>60],'network'=>['capacity'=>12,'period'=>60],'session'=>['capacity'=>5,'period'=>60]],
            'application_signals'=>['recent_failed_registrations'=>24,'known_good_session'=>-8],
            'fail_mode'=>'closed',
        ],
    ],
];
