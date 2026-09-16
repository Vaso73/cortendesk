<?php

return [
    'mail' => [
        'footer' => 'Odoslané zo služby :app na adrese :url.',
        'invitation' => [
            'subject' => 'Dostali ste pozvánku do :app',
            'title' => 'Dostali ste pozvánku do :app.',
            'intro' => ':invited_by vás pozýva do konzoly vzdialenej podpory. Vaše používateľské meno je :username. Na dokončenie účtu si nastavte heslo.',
            'action' => 'Prijať pozvánku',
            'copy_link' => 'Ak tlačidlo nefunguje, skopírujte tento odkaz do prehliadača:',
            'expiry' => 'Odkaz možno použiť raz a jeho platnosť vyprší :relative (:timestamp UTC).',
            'ignore' => 'Ak ste túto pozvánku neočakávali, správu ignorujte — účet sa nevytvorí, kým niekto nepoužije odkaz.',
        ],
        'login' => [
            'subject' => ':code je váš prihlasovací kód do :app',
            'title' => 'Váš prihlasovací kód',
            'intro' => 'Niekto sa prihlásil ako :username z prehliadača, ktorý táto konzola ešte nepozná. Prihlásenie dokončíte zadaním tohto kódu:',
            'expires' => '{1} Platnosť kódu vyprší o :count minútu.|[2,4] Platnosť kódu vyprší o :count minúty.|[5,*] Platnosť kódu vyprší o :count minút.',
            'ip' => 'Požiadavka prišla z adresy :ip.',
            'recovery' => 'Ak ste to neboli vy, niekto iný pozná vaše heslo — čo najskôr ho zmeňte a upozornite správcu.',
        ],
        'password_reset' => [
            'subject' => 'Obnovte si heslo do :app',
            'title' => 'Obnovenie hesla',
            'intro' => 'Niekto požiadal o obnovenie hesla používateľa :username v službe :app. Nové heslo si nastavíte cez tento odkaz:',
            'action' => 'Nastaviť nové heslo',
            'expires' => '{1} Platnosť odkazu vyprší o :count minútu a možno ho použiť raz.|[2,4] Platnosť odkazu vyprší o :count minúty a možno ho použiť raz.|[5,*] Platnosť odkazu vyprší o :count minút a možno ho použiť raz.',
            'ip' => 'Požiadavka prišla z adresy :ip.',
            'copy_link' => 'Ak tlačidlo nefunguje, vložte tento odkaz do prehliadača:',
            'ignore' => 'Ak ste o obnovenie hesla nepožiadali, správu ignorujte — vaše heslo sa nezmenilo.',
        ],
        'test' => [
            'subject' => ':app — testovacia správa',
            'title' => 'Nastavenie e-mailov funguje.',
            'intro' => 'Toto je testovacia správa z konzoly :app. Ak ju čítate, SMTP server správu prijal a doručil.',
            'close' => 'Nemusíte robiť nič ďalšie — túto správu môžete zavrieť.',
        ],
    ],
    'events' => [
        'device_pending_approval' => 'Zariadenie čaká na schválenie',
        'device_offline' => 'Zariadenie je odpojené',
        'device_online' => 'Pripojenie zariadenia bolo obnovené',
        'console_login_failed' => 'Neúspešné prihlásenie do konzoly',
        'security_alarm' => 'Bezpečnostný alarm',
        'remote_connection_failure' => 'Opakované zlyhanie vzdialeného pripojenia',
    ],
    'apprise' => [
        'test' => [
            'title' => 'Test notifikácií :app',
            'body' => 'Toto je testovacia notifikácia zo služby :app.',
        ],
        'device_pending_approval' => [
            'title' => 'Zariadenie čaká na schválenie',
            'body' => 'Zariadenie :device čaká na schválenie.',
        ],
        'device_offline' => [
            'title' => 'Zariadenie je odpojené',
            'body' => 'Zariadenie :device prestalo odosielať signál dostupnosti.',
        ],
        'device_online' => [
            'title' => 'Pripojenie zariadenia bolo obnovené',
            'body' => 'Zariadenie :device je znova pripojené.',
        ],
        'console_login_failed' => [
            'title' => 'Neúspešné prihlásenie do konzoly',
            'web_body' => 'Pokus o prihlásenie do webovej konzoly zlyhal.',
            'client_body' => 'Pokus o prihlásenie cez klienta RustDesk zlyhal.',
        ],
        'security_alarm' => [
            'title' => 'Bezpečnostný alarm',
            'body' => 'Zariadenie :device nahlásilo bezpečnostný alarm.',
            'types' => [
                'ip_whitelist_block' => 'Blokovanie podľa zoznamu povolených IP adries',
                'many_failed_attempts' => 'Veľa neúspešných pokusov (>30)',
                'rapid_access_attempts' => 'Rýchlo sa opakujúce pokusy o prístup',
                'ipv6_prefix_attempts_exceeded' => 'Prekročený počet pokusov z prefixu IPv6',
                'terminal_login_backoff' => 'Odklad prihlásenia do terminálu',
                'terminal_login_concurrency' => 'Súbežné prihlásenia do terminálu',
                'session_scope_violation' => 'Porušenie rozsahu relácie',
                'console_brute_force' => 'Útok hrubou silou na konzolu',
                'console_password_spraying' => 'Hromadné skúšanie hesiel v konzole',
            ],
        ],
        'remote_connection_failure' => [
            'title' => 'Opakované zlyhania vzdialeného pripojenia',
            'body' => 'Zariadenie :device nahlásilo viac ako 30 neúspešných pokusov o pripojenie.',
        ],
        'not_configured' => 'Služba Apprise nie je nastavená.',
        'http_error' => 'Služba Apprise vrátila stav HTTP :status. :error',
    ],
    'command' => [
        'description' => 'Zistí odpojenia zariadení a obnovenie ich pripojenia pre notifikácie Apprise',
        'disabled' => 'Notifikácie o dostupnosti zariadení sú vypnuté.',
        'device_label' => 'Zariadenie :id',
        'offline_count' => '{0} žiadne odpojenia|{1} 1 odpojenie|[2,4] :count odpojenia|[5,*] :count odpojení',
        'recovered_count' => '{0} žiadne obnovené pripojenia|{1} 1 obnovené pripojenie|[2,4] :count obnovené pripojenia|[5,*] :count obnovených pripojení',
        'summary' => 'Zistené prechody zariadení: :offline a :recovered.',
    ],
];
