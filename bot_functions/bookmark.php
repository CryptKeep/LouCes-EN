<?php
global $bot;
$bot->add_category('bookmark', array('humanice' => false, 'spamsafe' => true), PUBLICY);
//crons

//callbacks
$bot->add_msg_hook(array(PRIVATEIN, ALLYIN),
                   "Bookmark",               // command key
                   "LouBot_bookmark",        // callback function
                   false,                    // is a command PRE needet?
                   '/^[!]?(My)?Bo{1,2}[ck]+mar[ck]+$/',          // optional regex for key
function ($bot, $data) {
  global $redis;
  if (!$redis->status()) return;
  if ($bot->is_ally_user($data['user']) && !$bot->is_himself($data['user'])) {
    $private = (preg_match('/^My/i', $data['command'])) ? true : false;
    $types = array('citys','player','url','alliance','report','coords');
    $mode = 'url';
    $url = false;
    $alliance_key = "alliance:{$bot->ally_id}";
    if (preg_match_all('/\\[('.implode('|',$types).')\\](.{3,})\\[\\/('.implode('|',$types).')\\]/i', trim($data['origin']), $match, PREG_SET_ORDER)) {
      $mode = (in_array($match[0][1], $types) && $match[0][1] == $match[0][3]) ? $match[0][1] : false;
      $url = ($mode == 'url') ? filter_var((preg_match('/^[a-z]*:\\/\\//i', $match[0][2])) ? $match[0][2] : 'http://' . $match[0][2], FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED) : trim($match[0][2]);
    } else {
      $url = filter_var((preg_match('/^[a-z]*:\\/\\//i', trim($data['params'][1]))) ? trim($data['params'][1]) : 'http://' . trim($data['params'][1]), FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
    }
    $uid = $bot->get_user_id($data['user']);
    $short =  (preg_match('/[a-z0-9\._\-]{3,}/i', trim($data['params'][0]))) ? (($private) ? md5(mb_strtolower($uid.'@'.trim($data['params'][0]))): md5(mb_strtolower(trim($data['params'][0])))) : false;

    if ($short) {
      $exists = ($redis->EXISTS("bookmarks:{$alliance_key}:{$short}")) ? true : false;
      if ($exists) {
        $bookmark = $redis->HGETALL("bookmarks:{$alliance_key}:{$short}");
        if ($bookmark['private'] && $uid != $bookmark['lastuser']) { 
          $message = 'This Bookmark [i]' . trim($data['params'][0]) . "[/i] is private!";
          if ($data["channel"] == ALLYIN)
            $bot->add_allymsg($message);
          else 
            $bot->add_privmsg($message, $data['user']);
          return true;
        }
      }
      if ($url  && $mode) {
        if ($exists && $data['command'][0] != PRE) { 
          $message = 'This '.(($private)? 'private ' : '').'Bookmark [i]' . trim($data['params'][0]) . "[/i] already exist!";
          if ($data["channel"] == ALLYIN)
            $bot->add_allymsg($message);
          else 
            $bot->add_privmsg($message, $data['user']);
          return true;
        }
        $redis->HMSET("bookmarks:{$alliance_key}:{$short}", array(
          'name'        => trim($data['params'][0]),
          'url'         => $url,
          'mode'        => $mode,
          'private'     => $private,
          'lastchange'  => date("m/d/Y H:i:s"),
          'lastuser'    => $uid
        ));
        if ($private) {
          $redis->SADD("user:{$uid}:bookmarks", $short);
        }
        $message = 'This '.(($private)? 'private ' : '').'Bookmark [i]' . trim($data['params'][0]) . "[/i] it was placed!";
        if ($data["channel"] == ALLYIN)
          $bot->add_allymsg($message);
        else 
          $bot->add_privmsg($message, $data['user']);
        return true;
      }
      else if ($exists) {
        $message = 'Bookmark [i]' . $bookmark['name'] . "[/i]: [" .$bookmark['mode']."]".$bookmark['url']."[/" .$bookmark['mode']."]";
        if ($data["channel"] == ALLYIN)
          $bot->add_allymsg($message);
        else 
          $bot->add_privmsg($message, $data['user']);
        return true;
      } else {$bot->log('Debug : '.$short);
        $message = 'This '.(($private)? 'private ' : '').'Bookmark [i]' . trim($data['params'][0]) . "[/i] don't exist!";
        if ($data["channel"] == ALLYIN)
          $bot->add_allymsg($message);
        else 
          $bot->add_privmsg($message, $data['user']);
        return true;
      }
    }
      
    $bot->add_privmsg('Bookmark mistake: wrong parameters!', $data['user']);

  } else $bot->add_privmsg("Ne Ne Ne!", $data['user']);
}, 'user');

$bot->add_msg_hook(array(PRIVATEIN, ALLYIN),
                   "DelBookmark",               // command key
                   "LouBot_delbookmark",        // callback function
                   true,                        // is a command PRE needet?
                   '/^(De|Del|Rm)(My)?Bo{1,2}[ck]+mar[ck]+$/',          // optional regex for key
function ($bot, $data) {
  global $redis;
  if (!$redis->status()) return;
  if ($bot->is_ally_user($data['user']) && !$bot->is_himself($data['user'])) {
    $private = (preg_match('/^!(De|Del|Rm)My/i', $data['command'])) ? true : false;
    $uid = $bot->get_user_id($data['user']);
    $short =  (preg_match('/[a-z0-9\._\-]{3,}/', trim($data['params'][0]))) ? (($private) ? md5(mb_strtolower($uid.'@'.trim($data['params'][0]))): md5(mb_strtolower(trim($data['params'][0])))) : false;
    $alliance_key = "alliance:{$bot->ally_id}";
    if ($short) {
      $exists = ($redis->EXISTS("bookmarks:{$alliance_key}:{$short}")) ? true : false;
      if ($exists) {
        $bookmark = $redis->HGET("bookmarks:{$alliance_key}:{$short}");
        if ($bookmark['private'] && $uid != $bookmark['lastuser']) { 
          $message = 'This Bookmark [i]' . trim($data['params'][0]) . "[/i] is private!";
          if ($data["channel"] == ALLYIN)
            $bot->add_allymsg($message);
          else 
            $bot->add_privmsg($message, $data['user']);
          return true;
        }
        $redis->DEL("bookmarks:{$alliance_key}:{$short}");
        if ($private) $redis->SREM("user:{$uid}:bookmarks", $short);
        $message = 'This '.(($private)? 'private ' : '').'Bookmark [i]' . trim($data['params'][0]) . "[/i] was deleted!";
        if ($data["channel"] == ALLYIN)
          $bot->add_allymsg($message);
        else 
          $bot->add_privmsg($message, $data['user']);
        return true;
      } else {
        $message = 'This '.(($private)? 'private ' : '').'Bookmark [i]' . trim($data['params'][0]) . "[/i] don't exist!";
        if ($data["channel"] == ALLYIN)
          $bot->add_allymsg($message);
        else 
          $bot->add_privmsg($message, $data['user']);
        return true;
      }
    }
      
    $bot->add_privmsg('Bookmark mistake: wrong parameters!', $data['user']);

  } else $bot->add_privmsg("Ne Ne Ne!", $data['user']);
}, 'user');

?>