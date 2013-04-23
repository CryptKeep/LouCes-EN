<?php

// error reporting
error_reporting(E_ALL ^ E_NOTICE);

// determine enviroment
define('CLI', (bool) defined('STDIN'));

// configuration
define('BOT_EMAIL', 'botmale@domain.etc');
define('BOT_PASSWORD', 'password');
define('BOT_OWNER', 'Your LoU Name');
define('BOT_SERVICE_ACC', 'Give Access to andother user');
define('BOT_SERVER', 'http://prodgameXX.lordofultima.com/XXX/'); // your world server!
define('BOT_LANG','en'); // your prefered language for login

// prefix for commands to the bot
define('PRE','!');

// time settings
date_default_timezone_set("Europe/Berlin"); // server default timezone
setlocale(LC_TIME, "en");
setlocale(LC_ALL, 'en', 'us');


//
// after this, no changes needed!
//


// features like spam
define('SPAMTTL', 15);
define('POLLTRIP', 1);
define('SETTLERTTL', 129600);   // 36h after change to new settle.php deprecated
define('CLAIMTTL', 129600);     // 36h time for claims, after that the bot delete claims - if you change you need to restart the bot !  define('CLAIMTTL', 86400); 
define('SETTLETTL', 129600);   // 36h time for settle, after that bot delete.. but settle was only functional with Cheddarz tools :) ok 


// extension cheddarz tools
//define('EXTENSION_VERSION', '0.9.9'); // deprecated
//define('EXTENSION_KEY', 'cheddarz');

// ignore
define('IGNORE_PUNISHTTL', 3600);

// stats Website
define('STATS_URL', 'cheddarz.uk.to');

// global chat
define('GLOBALCHAT', false);

// fork
define('MAXCHILDS', 8);

// server pain barrier
define('MAX_PARALLEL_REQUESTS', 10); // Up this by 2 till is crashes

// Alliance Forums
define('BOT_STATISTICS_FORUM', 'Stats');
define('BOT_SETTLERS_FORUM', 'Settlers');
define('BOT_REPORTS_FORUM', 'Reports');
define('BOT_BLACK_FORUM', 'BlackBook');
define('BOT_SURVEY_FORUM', 'Survey');
define('BOT_EXTENSION_FORUM', 'Tools');

// log and directory settings
define('BOT_PATH',((CLI) ? $_SERVER['PWD'] : $_SERVER['DOCUMENT_ROOT']).DIRECTORY_SEPARATOR);
define('LOG_PATH', BOT_PATH.'logs'.DIRECTORY_SEPARATOR);
define('LOG_FILE', LOG_PATH.'log.txt');
define('PERM_DATA', BOT_PATH.'perm_data'.DIRECTORY_SEPARATOR);
define('DOKU_DATA', BOT_PATH.'doku_data'.DIRECTORY_SEPARATOR);
define('FNC_DATA', BOT_PATH.'bot_functions'.DIRECTORY_SEPARATOR);
define('BACK_DATA', BOT_PATH.'backup_data'.DIRECTORY_SEPARATOR);

// lock
define('LOCK_FILE', BOT_PATH.str_replace('.php', '.lock', $_SERVER['PHP_SELF']));

// shorthands
define('SERVER', 'SERVER');
define('PUBLICY', 'PUBLICY');
define('OPERATOR', 'OPERATOR');
define('PRIVACY', 'PRIVACY');
define('OWNER', 'OWNER');
define('SENDER','s');
define('CHANNEL','c');
define('MESSAGE','m');
define('ALLYIN','ALLYIN');
define('PRIVATEIN','PRIVATEIN');
define('PRIVATEOUT','PRIVATEOUT');
define('GLOBALIN','GLOBALIN');
define('SYSTEMIN','SYSTEMIN');
define('OFFICER','OFFICER');
define('ACCOUNT', 'A');
define('LOUACB', 'B');
define('LOUACC', 'C');
define('SYSTEM', '@');
define('UNKOWN', '$');
define('ANONYM', 'ANONYM');
define('CHAT', 'CHAT');
define('ALLIANCE', 'ALLIANCE');
define('IGNORE', 'IGNORE');
define('LISTS', 'LISTS');
define('ATTACK', 'ATTACK');
define('ALLYATT', 'ALLYATT');
define('PLAYER', 'PLAYER');
define('CITY', 'CITY');
define('USER', 'USER');
define('REPORT', 'REPORT');
define('REPORTHEADER', 'REPORTHEADER');
define('SMS', 'SMS');
define('INCOMMING', 'INCOMMING');
define('SIEGE', 'SIEGE');
define('COMMAND', 'COMMAND');
define('EMAIL', 'EMAIL');
define('REDIS', 'REDIS');
define('BOT', 'BOT');
define('CRON', 'CRON');
define('TICK', 'TICK');
define('STATISTICS', 'STATISTICS');
define('STAT_POINTS', 0);
define('STAT_RESSOURCES', 1);
define('STAT_TS', 2);
define('STAT_OFFENCE', 3);
define('STAT_DEFENCE', 4);
define('CONTINENT', 'CONTINENT');
define('IGMFOLDER', 'IGMFOLDER');
define('IGMIN', 'IGMIN');
define('IGMOUT', 'IGMOUT');
define('IGMUNKNOWN', 'IGMUNKNOWN');
define('IGMMESSAGE', 'IGMMESSAGE');
define('RANGE', 'RANGE');
define('KICKED', 'KICKED');
define('CLOSED', 'CLOSED');
define('EMPTY', 'EMPTY');
define('DROPED', 'DROPED');
define('SYS', 'SYS');
define('VERSION', 'VERSION');
define('SCOUT', 1);
define('PLUNDER', 2);
define('ASSAULT', 3);
define('SUPPORT', 4);
define('SIEGE', 5);
define('RAID', 8);
define('SETTLE', 9);
define('BOSS_RAID', 10);
define('CITY_STATE', '0');
define('CASTLE_STATE', '1');
define('PALACE_STATE', '2');
define('WATER_STATE', '1');

// redis database
define('REDIS_CONNECTION', ((CLI) ? '/var/run/redis/redis.sock' : '127.0.0.1')); // localhost or socket
#define('REDIS_CONNECTION', '127.0.0.1'); // localhost only
define('REDIS_NAMESPACE', 'lou:'); // use custom prefix on all keys
define('REDIS_DB', 1);
define('REDIS_LOG_FILE', LOG_PATH.'redis.txt');

// rights
define('ALLOW_SYS',  1);
define('ALLOW_LEAD', 1+2+4);
define('ALLOW_OFF',  1+2+4+8);  //1+2+4+8)
define('ALLOW_ALL',  1+2+4+8+16+32);   //1+2+4+8+16+32

// email
define('EMAIL_LOG_FILE', LOG_PATH.'email.txt');
define('EMAIL_SPAMTTL', 30);
define('EMAIL_SYS',  1);
define('EMAIL_LEAD', 1+2+4);
define('EMAIL_OFF',  1+2+4+8);
define('EMAIL_ALL',  1+2+4+8+16+32);
define('EMAIL_ALERT_OFF',  0);
define('EMAIL_ALERT_OWN',  1);
define('EMAIL_ALERT_ALL',  2);
define('EMAIL_SHARE_OFF',  0);
define('EMAIL_SHARE_ON',   1);
define('EMAIL_IS_SENDMAIL', true);
define('EMAIL_STATUS_OPEN',         'OPEN');
define('EMAIL_STATUS_TRANSMITTED',  'TRANSMITTED');
define('EMAIL_STATUS_BUFFERED',     'BUFFERED');
define('EMAIL_STATUS_ANSWERED',     'ANSWERED');


// read argv
function arguments($argv) { 
  $ARG = array();
  if(is_array($argv)) foreach ($argv as $arg) { 
    if (strpos($arg, '--') === 0) { 
      $compspec = explode('=', $arg); 
      $key = str_replace('--', '', array_shift($compspec)); 
      $value = join('=', $compspec); 
      $ARG[$key] = $value; 
    } elseif (strpos($arg, '-') === 0) { 
      $key = str_replace('-', '', $arg); 
      if (!isset($ARG[$key])) $ARG[$key] = true; 
    } 
  } 
  return new ArrayObject($ARG, ArrayObject::ARRAY_AS_PROPS); 
}
$_ARG = arguments($argv);
$_GAMEDATA = new ArrayObject(json_decode(file_get_contents(PERM_DATA . 'game.data.min.' . BOT_LANG), true), ArrayObject::ARRAY_AS_PROPS);
?>
