<?php
global $bot;
$bot->add_category('digest', array(), PUBLICY);

// crons
/*
$bot->add_cron_event(Cron::DAILY,                           // Cron key
                    "GenerateDailyDigest",                  // command key
                    "LouBot_generate_daily_digest_cron",    // callback function
function ($bot, $data) {
  global $redis;
  if (!$redis->status()) return;
  $continents = $redis->smembers("continents");
  $alliance_key = "alliance:{$bot->ally_id}";
  $digest_key = "digest";
  $settler_key = "settler";
  if (is_array($continents)) foreach ($continents as $continent) {
    $continent_key = "continent:{$continent}";
    $receivers = $redis->sdiff("{$settler_key}:{$alliance_key}:{$continent_key}:residents", "{$digest_key}:{$alliance_key}:nomail");
    // can check rights with $access = $bot->get_access($data['user'], $allow);
    $bot->log('Digest: found '.count($receivers).' receivers for C'.$continent);
    send_digest($continent, $receivers);
  }
}, 'digest');
*/

// callbacks
$bot->add_msg_hook(array(PRIVATEIN),
                   "digest",               // command key
                   "LouBot_digest",        // callback function
                   true,                   // is a command PRE needet?
                   '/^digest$/i',          // optional regex for key
function ($bot, $data) {
  global $redis, $sms;
  if (!$redis->status()) return;
  $commands = array('off', 'on', 'mail');
  $continents = $redis->smembers("continents");
  $alliance_key = "alliance:{$bot->ally_id}";
  if ($bot->is_ally_user($data['user']) && !$bot->is_himself($data['user'])) {
      if (in_array(strtolower($data['params'][0]), $commands) || (stripos($data['params'][0], 'c') !== false && in_array(substr($data['params'][0], 1), $continents))) {
      $second_argument = strtolower(Lou::prepare_chat($data['params'][1]));
      $digest_key = "digest";
      switch (strtolower($data['params'][0])) {
        case 'off':
          if($redis->sadd("{$digest_key}:{$alliance_key}:nomail", $data['user'])) {
            if ($data["channel"] == ALLYIN) $message = "{$data['user']}, you are out the mailing list registration completed!";
            else $message = 'You are out the mailing list registration completed!';
          } else if($redis->sismember("{$digest_key}:{$alliance_key}:nomail", $data['user'])) {
            if ($data["channel"] == ALLYIN) $message = "{$data['user']}, you are out the mailing list registration completed!";
            else $message = 'You are out the mailing list registration completed!';
          }
          break;
        case 'on':
          if($redis->srem("{$digest_key}:{$alliance_key}:nomail", $data['user'])) {
            if ($data["channel"] == ALLYIN) $message = "{$data['user']}, you are logged on in the mailing list!";
            else $message = 'You are logged on in the mailing list!';
          } else if(!$redis->sismember("{$digest_key}:{$alliance_key}:nomail", $data['user'])) {
            if ($data["channel"] == ALLYIN) $message = "{$data['user']}, you are logged on in the mailing list!";
            else $message = 'You are logged on in the mailing list!';
          }
          break;
        case 'mail':
          if($redis->sismember("{$digest_key}:{$alliance_key}:nomail", $data['user'])) {
            if ($data["channel"] == ALLYIN) $message = "{$data['user']}, you are logged out from the mailing list!";
            else $message = 'You are logged out from the mailing list!';
          } else {
            if ($data["channel"] == ALLYIN) $message = "{$data['user']}, you are logged out from the mailing list!";
            else $message = 'You are logged out from the mailing list!';
          }
          break;
        case (stripos($data['params'][0], 'c') !== false && in_array(substr($data['params'][0], 1), $continents)):
          if ($bot->is_op_user($data['user'])) {
            $continent = intval(substr($data['params'][0], 1));
            $hours = array(1,2,6,12,24,36,72);
            $range = (in_array(intval($data['params'][1]), $hours)) ? intval($data['params'][1]) : 2;
            $message = "You can get the current Digest-{$range}h for C{$continent} delivered!";
            send_digest_3($continent, $data['user'], mktime((date("H") - $range), 0, 0, date("n"), date("j"), date("Y")), $range);
            break;
          } else $message = "Ne Ne Ne!";
      }
    } else $bot->add_privmsg('Digest: wrong parameters ('.Lou::prepare_chat($data['params'][0]).')!', $data['user']);

    if ($data["channel"] == ALLYIN)
      $bot->add_allymsg($data['user'] . ', ' . $message);
    else 
      $bot->add_privmsg($message, $data['user']);
    return true;
  } else $bot->add_privmsg("No you dont!", $data['user']);
}, 'digest');

if(!function_exists('send_digest_3')) {
  function send_digest_3($continents, $receivers, $start = false, $range = 24) {
    global $bot, $redis;
    if(!is_array($continents)) $continents = array($continents);
    if(!is_array($receivers)) $receivers = array($receivers);
    $digest_key = "digest";
    $digest = array();
    $_start = ($start) ? $start : mktime(date("H"), 0, 0, date("n"), (date("j") - 1), date("Y"));
    $_end = mktime(date("H"), 0, 0, date("n"), date("j"), date("Y"));
    foreach($continents as $continent) {
      $digest[] = "[u]Digest of the last {$range} Std. for C{$continent}[/u]\n\n";
      $continent_key = "continent:{$continent}";
      $bot->log('Digest: send '.count($receivers).' messages to C'.$continent);
      $keys = $redis->clearkey($redis->getkeys("{$digest_key}:{$continent_key}:*"), "/{$digest_key}:{$continent_key}:/");
      if(is_array($keys)) foreach($keys as $key) {
        $_digs = array_flip($redis->zrangebyscore("{$digest_key}:{$continent_key}:{$key}", "{$_start}", "{$_end}", array('withscores' => TRUE)));
        switch($key) {
          case 'cities:overtake':
            $$key = "[b]Cities took over:[/b]\n";
            if(!empty($_digs)) { foreach($_digs as $_time => $_dig) {
              $obj = json_decode($_dig);
              foreach($obj as $k => $v) {
                $city_id = $redis->hget("cities", $k);
                $city_data = $redis->hgetall("city:{$city_id}:data");
                $user_new = $redis->hgetall("user:{$v[0]}:data");
                $ally_new_name = $redis->hget("alliance:{$user_new['alliance']}:data", 'name');
                $user_old = $redis->hgetall("user:{$v[1]}:data");
                $ally_old_name = $redis->hget("alliance:{$user_old['alliance']}:data", 'name');
                $icon = ($bot->ally_id == $user_new['alliance'] || $bot->ally_id == $user_old['alliance']) ? '∗ ' : '  ';
                $$key .= "{$icon}[i]{$city_data['category']}[/i] - [city]{$city_data['pos']}[/city] ({$city_data['points']}) - [i]{$city_data['name']}[/i] - " . (($user_old['name']) ?  "[s][player]{$user_old['name']}[/player][/s]" . (($ally_old_name) ? "[s][[alliance]{$ally_old_name}[/alliance]][/s]":"") : '[i]Lawless[/i]');
                $$key .= " ⇒ it came from [player]{$user_new['name']}[/player]" . (($ally_new_name) ? "[[alliance]{$ally_new_name}[/alliance]]":"") . " taken on";
                $$key .= "\n";
              }
             } $digest[] = $$key;
            } else unset($$key);
            break;
          case 'cities:new':
            $$key = "[b]New Cities:[/b]\n";
            if(!empty($_digs)) { foreach($_digs as $_time => $_dig) {
              $obj = json_decode($_dig);
              foreach($obj as $k) {
                $city_id = $redis->hget("cities", $k);
                $city_data = $redis->hgetall("city:{$city_id}:data");
                $user_new = $redis->hgetall("user:{$city_data['user_id']}:data");
                $ally_new_name = $redis->hget("alliance:{$city_data['alliance_id']}:data", 'name');
                $icon = ($bot->ally_id == $city_data['alliance_id']) ? '∗ ' : '  ';
                $$key .= "{$icon}[i]{$city_data['category']}[/i] - [city]{$city_data['pos']}[/city] ({$city_data['points']}) - [i]{$city_data['name']}[/i] - " . (($user_new['name']) ?  "[player]{$user_new['name']}[/player]" . (($ally_new_name) ? "[[alliance]{$ally_new_name}[/alliance]]":"") : '[i]Lawless[/i]');
                $$key .= "\n";
              }
             } $digest[] = $$key;
            } else unset($$key);
            break;
          case 'cities:palace':
            $$key = "[b]New Palace:[/b]\n";
            if(!empty($_digs)) { foreach($_digs as $_time => $_dig) {
              $obj = json_decode($_dig);
              foreach($obj as $k => $v) {
                $city_id = $redis->hget("cities", $k);
                $city_data = $redis->hgetall("city:{$city_id}:data");
                $user_new = $redis->hgetall("user:{$city_data['user_id']}:data");
                $ally_new_name = $redis->hget("alliance:{$city_data['alliance_id']}:data", 'name');
                $icon = ($bot->ally_id == $city_data['alliance_id']) ? '∗ ' : '  ';
                $$key .= "{$icon}[i]{$city_data['category']}[/i] - [city]{$city_data['pos']}[/city] ({$city_data['points']}) - [i]{$city_data['name']}[/i] - " . (($user_new['name']) ?  "[player]{$user_new['name']}[/player]" . (($ally_new_name) ? "[[alliance]{$ally_new_name}[/alliance]]":"") : '[i]Lawless[/i]');
                $$key .= "\n";
              }
             } $digest[] = $$key;
            } else unset($$key);
            break;
          case 'cities:castles':
            $$key = "[b]New Castles:[/b]\n";
            if(!empty($_digs)) { foreach($_digs as $_time => $_dig) {
              $obj = json_decode($_dig);
              foreach($obj as $k => $v) {
                $city_id = $redis->hget("cities", $k);
                $city_data = $redis->hgetall("city:{$city_id}:data");
                $user_new = $redis->hgetall("user:{$city_data['user_id']}:data");
                $ally_new_name = $redis->hget("alliance:{$city_data['alliance_id']}:data", 'name');
                $icon = ($bot->ally_id == $city_data['alliance_id']) ? '∗ ' : '  ';
                $$key .= "{$icon}[i]{$city_data['category']}[/i] - [city]{$city_data['pos']}[/city] ({$city_data['points']}) - [i]{$city_data['name']}[/i] - " . (($user_new['name']) ?  "[player]{$user_new['name']}[/player]" . (($ally_new_name) ? "[[alliance]{$ally_new_name}[/alliance]]":"") : '[i]Lawless[/i]');
                $$key .= "\n";
              }
             } $digest[] = $$key;
            } else unset($$key);
            break;
          case 'cities:lawless':
            $$key = "[b]New Lawless:[/b]\n";
            if(!empty($_digs)) { foreach($_digs as $_time => $_dig) {
              $obj = json_decode($_dig);
                foreach($obj as $k) {
                  $city_id = $redis->hget("cities", $k);
                  $city_data = $redis->hgetall("city:{$city_id}:data");
                  $user_old = $redis->hgetall("user:{$city_data['ll_user_id']}:data");
                  $ally_old_name = $redis->hget("alliance:{$city_data['ll_alliance_id']}:data", 'name');
                  $icon = ($bot->ally_id == $city_data['ll_alliance_id']) ? '∗ ' : '  ';
                  $$key .= "{$icon}[i]{$city_data['category']}[/i] - [city]{$city_data['pos']}[/city] ({$city_data['points']}/{$city_data['ll_points']}) - [i]{$city_data['ll_name']}[/i] - [player]{$user_old['name']}[/player]" . (($ally_old_name) ? "[[alliance]{$ally_old_name}[/alliance]]":"");
                  $$key .= "\n";
                }
              } $digest[] = $$key;
            } else unset($$key);
            break;
          case 'cities:rename':
            $$key = "[b]Cities to designation:[/b]\n";
            if(!empty($_digs)) { foreach($_digs as $_time => $_dig) {
              $obj = json_decode($_dig);
                foreach($obj as $k => $v) {
                  $city_id = $redis->hget("cities", $k);
                  $city_data = $redis->hgetall("city:{$city_id}:data");
                  $user_new = $redis->hgetall("user:{$city_data['user_id']}:data");
                  $ally_new_name = $redis->hget("alliance:{$city_data['alliance_id']}:data", 'name');
                  $icon = ($bot->ally_id == $city_data['alliance_id']) ? '∗ ' : '  ';
                  $$key .= "{$icon}[i]{$city_data['category']}[/i] - [city]{$city_data['pos']}[/city] ({$city_data['points']}) - [s]{$v[1]}[/s] - It was renamed in [i]{$v[0]}[/i] - " . (($user_new['name']) ?  "[player]{$user_new['name']}[/player]" . (($ally_new_name) ? "[[alliance]{$ally_new_name}[/alliance]]":"") : '[i]Lawless[/i]');
                  $$key .= "\n";
                }
              } $digest[] = $$key;
            } else unset($$key);
            break;
          case 'aliances:new':
            $$key = "[b]New Alliances:[/b]\n";
            if(!empty($_digs)) { foreach($_digs as $_time => $_dig) {
              $obj = json_decode($_dig);
                foreach($obj as $k) {
                  $alliance_id = $redis->hget("alliances", $k);
                  $ally_data = $redis->hgetall("alliance:{$alliance_id}:{$continent_key}:data");
                  $icon = ($bot->ally_id == $alliance_id) ? '∗ ' : '  ';
                  $$key .= "{$icon}[i][alliance]{$k}[/alliance][/i] - Points:{$ally_data['points']} Players:{$ally_data['members']} Cities:{$ally_data['cities']}";
                  $$key .= "\n";
                }
              } $digest[] = $$key;
            } else unset($$key);
            break;
          case 'aliances:left':
            $$key = "[b]Alliances thats resigned:[/b]\n";
            if(!empty($_digs)) { foreach($_digs as $_time => $_dig) {
              $obj = json_decode($_dig);
                foreach($obj as $k) {
                  $$key .= "{$icon}[s][alliance]{$k}[/alliance][/s]";
                  $$key .= "\n";
                }
              } $digest[] = $$key;
            } else unset($$key);
            break;
          case 'residents:new':
            $$key = "[b]New Player:[/b]\n";
            if(!empty($_digs)) { foreach($_digs as $_time => $_dig) {
              $obj = json_decode($_dig);
                foreach($obj as $k) {
                  $user_id = $redis->hget("users", $k);
                  $user_new = $redis->hgetall("user:{$user_id}:data");
                  $ally_new_name = $redis->hget("alliance:{$user_new['alliance_id']}:data", 'name');
                  $$key .= "{$icon}[i][player]{$k}[/player][/i]" . (($ally_new_name) ? "[[alliance]{$ally_new_name}[/alliance]]":"");
                  $$key .= "\n";
                }
              } $digest[] = $$key;
            } else unset($$key);
            break;
          case 'residents:left':
            $$key = "[b]Players that have resigned:[/b]\n";
            if(!empty($_digs)) { foreach($_digs as $_time => $_dig) {
              $obj = json_decode($_dig);
                foreach($obj as $k) {
                  $user_id = $redis->hget("users", $k);
                  $user_old = $redis->hgetall("user:{$user_id}:data");
                  $ally_old_name = $redis->hget("alliance:{$user_old['alliance_id']}:data", 'name');
                  $icon = ($bot->ally_id == $user_old['alliance_id']) ? '∗ ' : '  ';
                  $$key .= "{$icon}[s][player]{$k}[/player][/s]" . (($ally_old_name) ? "[[alliance]{$ally_old_name}[/alliance]]":"");
                  $$key .= "\n";
                }
              } $digest[] = $$key;
            } else unset($$key);
            break;
        }
      }
      $digest[] .= "";
    }
    $bot->igm->send(implode(';',$receivers), "♲ Digest-{$range}h for C" . implode(', C', $continents), implode('', $digest));
  }
}
?>