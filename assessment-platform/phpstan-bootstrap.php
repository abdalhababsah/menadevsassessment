<?php

// Override broadcast connection for PHPStan analysis to avoid
// Reverb requiring a running WebSocket server during static analysis.
putenv('BROADCAST_CONNECTION=log');
$_ENV['BROADCAST_CONNECTION'] = 'log';
$_SERVER['BROADCAST_CONNECTION'] = 'log';
