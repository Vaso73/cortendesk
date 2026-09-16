<?php

return [
    'mail' => [
        'footer' => 'Sent by :app at :url.',
        'invitation' => [
            'subject' => 'You have been invited to :app',
            'title' => 'You have been invited to :app.',
            'intro' => ':invited_by invited you to the remote-support console. Your username is :username. Choose a password to finish setting up your account.',
            'action' => 'Accept invitation',
            'copy_link' => 'If the button does not work, copy this link into your browser:',
            'expiry' => 'The link works once and expires :relative (:timestamp UTC).',
            'ignore' => 'If you were not expecting this, ignore the message — no account is created until someone follows the link.',
        ],
        'login' => [
            'subject' => ':code is your :app sign-in code',
            'title' => 'Your sign-in code',
            'intro' => 'Someone signed in as :username from a browser this console has not seen before. Enter this code to finish signing in:',
            'expires' => '{1} The code expires in :count minute.|[2,*] The code expires in :count minutes.',
            'ip' => 'The request came from :ip.',
            'recovery' => 'If this was not you, someone else knows your password — change it as soon as you can and tell an administrator.',
        ],
        'password_reset' => [
            'subject' => 'Reset your :app password',
            'title' => 'Reset your password',
            'intro' => 'Someone asked to reset the password for :username on :app. Use the link below to choose a new one:',
            'action' => 'Choose a new password',
            'expires' => '{1} The link expires in :count minute and can be used once.|[2,*] The link expires in :count minutes and can be used once.',
            'ip' => 'The request came from :ip.',
            'copy_link' => 'If the button does not work, paste this into your browser:',
            'ignore' => 'If you did not ask for this, ignore this message — your password has not changed.',
        ],
        'test' => [
            'subject' => ':app — test message',
            'title' => 'Your mail settings work.',
            'intro' => 'This is a test message from the :app console. If you are reading it, the SMTP relay accepted the message and delivered it.',
            'close' => 'Nothing else to do — you can close this.',
        ],
    ],
    'events' => [
        'device_pending_approval' => 'Device pending approval',
        'device_offline' => 'Device offline',
        'device_online' => 'Device recovered',
        'console_login_failed' => 'Failed console login',
        'security_alarm' => 'Security alarm',
        'remote_connection_failure' => 'Repeated remote connection failure',
    ],
    'apprise' => [
        'test' => [
            'title' => ':app notification test',
            'body' => 'This is a test notification from :app.',
        ],
        'device_pending_approval' => [
            'title' => 'Device pending approval',
            'body' => 'Device :device is awaiting approval.',
        ],
        'device_offline' => [
            'title' => 'Device offline',
            'body' => 'Device :device stopped heartbeating.',
        ],
        'device_online' => [
            'title' => 'Device recovered',
            'body' => 'Device :device is online again.',
        ],
        'console_login_failed' => [
            'title' => 'Failed console login',
            'web_body' => 'A web-console sign-in attempt failed.',
            'client_body' => 'A RustDesk client sign-in attempt failed.',
        ],
        'security_alarm' => [
            'title' => 'Security alarm',
            'body' => 'Device :device reported a security alarm.',
            'types' => [
                'ip_whitelist_block' => 'IP whitelist block',
                'many_failed_attempts' => 'Many failed attempts (>30)',
                'rapid_access_attempts' => 'Rapid access attempts',
                'ipv6_prefix_attempts_exceeded' => 'IPv6 prefix attempts exceeded',
                'terminal_login_backoff' => 'Terminal login backoff',
                'terminal_login_concurrency' => 'Terminal login concurrency',
                'session_scope_violation' => 'Session scope violation',
                'console_brute_force' => 'Console brute force',
                'console_password_spraying' => 'Console password spraying',
            ],
        ],
        'remote_connection_failure' => [
            'title' => 'Repeated remote connection failures',
            'body' => 'Device :device reported more than 30 failed connection attempts.',
        ],
        'not_configured' => 'Apprise is not configured.',
        'http_error' => 'Apprise returned HTTP :status. :error',
    ],
    'command' => [
        'description' => 'Detect device offline/recovery transitions for Apprise notifications',
        'disabled' => 'Device presence notifications are disabled.',
        'device_label' => 'Device :id',
        'offline_count' => '{0} no offline transitions|{1} 1 offline transition|[2,*] :count offline transitions',
        'recovered_count' => '{0} no recovered transitions|{1} 1 recovered transition|[2,*] :count recovered transitions',
        'summary' => 'Detected device transitions: :offline and :recovered.',
    ],
];
