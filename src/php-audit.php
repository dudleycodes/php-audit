<?php

$auditAuthCode = trim(filter_var($_GET['AUDITAUTHCODE'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW, FILTER_FLAG_STRIP_HIGH, FILTER_FLAG_STRIP_BACKTICK));

if (getenv('PHP-AUDITAUTHCODE', true) <> false || strlen($auditAuthCode) > 0) {

    if (strlen($auditAuthCode) < 0 || $auditAuthCode <> trim(getenv('PHP-AUDITAUTHCODE', true)))
    {
        http_response_code(404);

        if( strpos( $_SERVER['SERVER_SOFTWARE'], 'Apache') !== false) 
        {
            // Mimic Apache 404 error
            echo('<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">');
            echo('<html><head>');
            echo('<title>404 Not Found</title>');
            echo('</head><body>');
            echo('<h1>Not Found</h1>');
            echo('<p>The requested URL /php-audit.php was not found on this server.</p>');
            echo('<hr>');
            echo(trim($_SERVER['SERVER_SIGNATURE']));
            echo('</body></html>');
            echo('');
        }
        else
        {
            echo '404 Page Not Found';
        }

        die();
    }
}

/**
 * Returns data from phpinfo() as an array.
 * source: https://gist.github.com/sbmzhcn/6255314
 * @return array
 */
function parse_phpinfo() {
    ob_start(); phpinfo(INFO_MODULES); $s = ob_get_contents(); ob_end_clean();
    $s = strip_tags($s, '<h2><th><td>');
    $s = preg_replace('/<th[^>]*>([^<]+)<\/th>/', '<info>\1</info>', $s);
    $s = preg_replace('/<td[^>]*>([^<]+)<\/td>/', '<info>\1</info>', $s);
    $t = preg_split('/(<h2[^>]*>[^<]+<\/h2>)/', $s, -1, PREG_SPLIT_DELIM_CAPTURE);
    $r = array(); $count = count($t);
    $p1 = '<info>([^<]+)<\/info>';
    $p2 = '/'.$p1.'\s*'.$p1.'\s*'.$p1.'/';
    $p3 = '/'.$p1.'\s*'.$p1.'/';
    for ($i = 1; $i < $count; $i++) {
        if (preg_match('/<h2[^>]*>([^<]+)<\/h2>/', $t[$i], $matchs)) {
            $name = trim($matchs[1]);
            $vals = explode("\n", $t[$i + 1]);
            foreach ($vals AS $val) {
                if (preg_match($p2, $val, $matchs)) { // 3cols
                    $r[$name][trim($matchs[1])] = array(trim($matchs[2]), trim($matchs[3]));
                } elseif (preg_match($p3, $val, $matchs)) { // 2cols
                    $r[$name][trim($matchs[1])] = trim($matchs[2]);
                }
            }
        }
    }
    return $r;
}

function thisOrThat($t, $that) {
    return (!is_null($t) && strlen(trim($t)) > 0) ? $t: $that;
}

//======================================================================================================

header('Content-Type: application/json');

try {
    if (!extension_loaded('json')) {
        throw new Exception('Missing "json" extension!');
    }

    $phpinfo = json_encode(parse_phpinfo());

    $status = 200;
    $message = '"OK"';
} catch (Exception $ex) {
    $message = '"'. addslashes($ex->getMessage()) .'"';
} finally {
    echo('{');
    echo('    "status": ' . thisOrThat($status, 501));
    echo('  , "message": ' . thisOrThat($message, '"unknown"'));
    echo('  , "phpinfo" : ' . thisOrThat($phpinfo, "{}"));
    echo('  , "server": ' . thisOrThat('"'. trim($_SERVER['SERVER_SOFTWARE']) .'"', '""'));
    echo('}');
}
