<?php
global $bot;
$bot->add_category('user', array(), PUBLICY);
// crons

// callbacks
$bot->add_msg_hook(array(PRIVATEIN, ALLYIN),
                   "UV",               // command key
                   "LouBot_uv",        // callback function
                   false,              // is a command PRE needet?
                   '/^[!]?UV$/i',      // optional regex for key
function ($bot, $data) {
  global $redis;
  if (!$redis->status()) return;
  if ($bot->is_ally_user($data['user']) && !$bot->is_himself($data['user'])) {
    if ($data['command'][0] != PRE) {
      $user = false;
      if (!empty($data['params'][0])) {
        if ($bot->is_ally_user($data['params'][0]))
          $user = $data['params'][0];
        else {
          $message = "Alias [i]" . ucfirst(mb_strtolower($data['params'][0])) . "[/i] is not occupied!";
          if ($data["channel"] == ALLYIN)
            $bot->add_allymsg($message);
          else 
            $bot->add_privmsg($message, $data['user']);
          return true;
        }
      } else $user = $data['user'];
      $uid = $bot->get_user_id($user);
      $uvid = $redis->get("user:{$uid}:uv");
      if ($uvid) {
        $uv = $redis->hget("user:{$uvid}:data", 'name');
        $nick = $redis->hget("user:{$uid}:data", 'name');
        if ($data["channel"] == ALLYIN)
          $bot->add_allymsg("{$nick}'s UV: [player]{$uv}[/player]");
        else 
          $bot->add_privmsg((($nick == $data['user']) ? "Your UV " : "{$nick}'s UV ") . "[player]{$uv}[/player]", $data['user']);
        return true;
      } else {
        $nick = $redis->hget("user:{$uid}:data", 'name');
        if ($data["channel"] == ALLYIN)
          $bot->add_allymsg("{$nick} did not set an UV!");
        else 
          $bot->add_privmsg((($nick == $data['user']) ? "You havent set a UV!" : "{$nick} did not set a UV!"), $data['user']);
        return true;
      }
    } else if ($bot->is_ally_user($data['params'][0])) {
      $uid = $bot->get_user_id($data['user']);
      $uvid = $bot->get_user_id($data['params'][0]);
      if ($uid == $uvid) {
        if ($data["channel"] == ALLYIN)
          $bot->add_allymsg("{$data['user']}, you cannot set yourself UV!.");
        else 
          $bot->add_privmsg("You cannot set yourself UV!", $data['user']);        
        return true;
      }
      $uv = $redis->hget("user:{$uvid}:data", 'name');
      $redis->set("user:{$uid}:uv", $uvid);
      if ($data["channel"] == ALLYIN)
        $bot->add_allymsg("{$data['user']}'s UV [player]{$uv}[/player] gesetzt.");
      else 
        $bot->add_privmsg("Your UV [player]{$uv}[/player] is a done deal.", $data['user']);        
      return true;
    } else if (strtoupper($data['params'][0]) == 'DEL') {
      $uid = $bot->get_user_id($data['user']);
      $uvid = $redis->get("user:{$uid}:uv");
      if ($uvid) {
        $uv = $redis->hget("user:{$uvid}:data", 'name');
        $nick = $redis->hget("user:{$uid}:data", 'name');
        $del = $redis->del("user:{$uid}:uv");
        if ($data["channel"] == ALLYIN)
          $bot->add_allymsg("{$data['user']}'s UV [player]{$uv}[/player] deleted.");
        else 
          $bot->add_privmsg("Your UV [player]{$uv}[/player] got successfully deleted.", $data['user']);
        return true;
      } else {
        if ($data["channel"] == ALLYIN)
          $bot->add_allymsg("{$data['user']} did not set a UV!");
        else 
          $bot->add_privmsg("You havent set a UD", $data['user']);
        return true;
      }
    }
    $bot->add_privmsg('Alias error: wrong input!', $data['user']);

  } else $bot->add_privmsg("No No No!", $data['user']);
}, 'user');

$bot->add_msg_hook(array(PRIVATEIN, ALLYIN),
                   "Alias",               // command key
                   "LouBot_alias",        // callback function
                   false,                 // is a command PRE needet?
                   '/^[!]?Alias$/',       // optional regex for key
function ($bot, $data) {
  global $redis, $bwords;
  if (empty($bwords)) {
    $lines = file(PERM_DATA.'blacklist.'.BOT_LANG, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line_num => $line) {
      if ($line[0] != '#') $bwords[$line_num] = trim(strtoupper(htmlspecialchars($line)));
    }
  }
  if (!$redis->status()) return;
  if ($bot->is_ally_user($data['user']) && !$bot->is_himself($data['user'])) {
    if ($data['command'][0] != PRE) {
      $user = false;
      if (!empty($data['params'][0])) {
        if ($bot->is_ally_user($data['params'][0]))
          $user = $data['params'][0];
        else {
          $message = "Alias [i]" . ucfirst(mb_strtolower($data['params'][0])) . "[/i] is not occupied";
          if ($data["channel"] == ALLYIN)
            $bot->add_allymsg($message);
          else 
            $bot->add_privmsg($message, $data['user']);
          return true;
        }
		
      } else $user = $data['user'];
      $uid = $bot->get_user_id($user);
      $aliase = $redis->smembers("user:{$uid}:alias");
      $nick = $redis->hget("user:{$uid}:data", 'name');
      if ($data["channel"] == ALLYIN)
        $bot->add_allymsg("{$nick}'s Aliase: [i]" . ucwords(mb_strtolower(implode(', ', $aliase))) . "[/i]");
      else 
        $bot->add_privmsg((($nick == $data['user']) ? "Your Aliases: [i]" : "{$nick}'s Aliases: [i]") . ucwords(mb_strtolower(implode(', ', $aliase))) . "[/i]", $data['user']);
      return true;
    } else if (!$bot->is_ally_user($data['params'][0])) {
if (!preg_match('/^[a-zA-Z]{1}[a-zA-Z0-9-_.]{1,15}$/', $data['params'][0]) || strtoupper($data['params'][0]) == 'DEL' || array_search(strtoupper($data['params'][0]), $bwords) !== false) {
	        $message = 'Alias [i]' . mb_strtolower($data['params'][0]) . '[/i] is of no valid structure!';
        if ($data["channel"] == ALLYIN)
          $bot->add_allymsg($message);
        else 
          $bot->add_privmsg($message, $data['user']);
        return true;
      }
      $uid = $bot->get_user_id($data['user']);
      $alias = mb_strtoupper($data['params'][0]);
      $insert = $redis->hmset("aliase", array(
        $alias => $uid
      ));
      if ($insert) {
        $redis->sadd("user:{$uid}:alias", $alias);
        $_alias = ucfirst(mb_strtolower($alias));
        if ($data["channel"] == ALLYIN)
          $bot->add_allymsg("{$data['user']}'s Alias [i]{$_alias}[/i] set.");
        else 
          $bot->add_privmsg("Your Alias [i]{$_alias}[/i] is set.", $data['user']);        
        return true;
      }
    } else {
      $uid = $bot->get_user_id($data['params'][0]);
      $nick = $redis->hget("user:{$uid}:data", 'name');
      if ($data["channel"] == ALLYIN)
        $bot->add_allymsg('Alias [i]' . ucfirst(mb_strtolower($data['params'][0])) . "[/i] is occupied from {$nick}!");
      else 
        $bot->add_privmsg('Alias [i]' . ucfirst(mb_strtolower($data['params'][0])) . "[/i] is occupied from ".(($nick == $data['user']) ? 'Dir' : $nick)." !", $data['user']);
      return true;
    }
    $bot->add_privmsg('Alias error: wrong input!', $data['user']);

  } else $bot->add_privmsg("No No No!", $data['user']);
}, 'user');

$bot->add_msg_hook(array(PRIVATEIN, ALLYIN),
                   "DeAlias",             // command key
                   "LouBot_dealias",      // callback function
                   true,                  // is a command PRE needet?
                   '',                     // optional regex for key
function ($bot, $data) {
  global $redis;
  if (!$redis->status()) return;
  if ($bot->is_ally_user($data['user']) && !$bot->is_himself($data['user'])) {
    if ($bot->is_ally_user($data['params'][0])) {
      $uid = $bot->get_user_id($data['params'][0]);
      $nick = $redis->hget("user:{$uid}:data", 'name');
      if (mb_strtoupper($data['user']) == mb_strtoupper($data['params'][0])) {
        $message = 'Alias [i]' . ucfirst(mb_strtolower($data['params'][0])) . "[/i] cannot be deleted!";
        if ($data["channel"] == ALLYIN)
          $bot->add_allymsg($message);
        else 
          $bot->add_privmsg($message, $data['user']);
      } else if ($nick != $data['user']) {
        $message = 'Alias [i]' . ucfirst(mb_strtolower($data['params'][0])) . "[/i] is occupied from {$nick} !";
        if ($data["channel"] == ALLYIN)
          $bot->add_allymsg($message);
        else 
          $bot->add_privmsg($message, $data['user']);
      } else {
        $alias = mb_strtoupper($data['params'][0]);
        $redis->hdel("aliase", $alias);
        $redis->srem("user:{$uid}:alias", $alias);
        $message = ' Alias [i]' . ucfirst(mb_strtolower($data['params'][0])) . "[/i] succesfully deleted!";
        if ($data["channel"] == ALLYIN)
          $bot->add_allymsg('Der'.$message);
        else 
          $bot->add_privmsg('Dein'.$message, $data['user']);
      }
      return true;
    } else {
      $message = 'Alias [i]' . ucfirst(mb_strtolower($data['params'][0])) . "[/i] is not occupied yet!";
      if ($data["channel"] == ALLYIN)
        $bot->add_allymsg($message);
      else 
        $bot->add_privmsg($message);
      return true;
    }
    $bot->add_privmsg('Alias error: wrong input!', $data['user']);

  } else $bot->add_privmsg("No No No!", $data['user']);
}, 'user');

$bot->add_msg_hook(array(PRIVATEIN, ALLYIN),
                   "Seen",                 // command key
                   "LouBot_seen",          // callback function
                   true,                  // is a command PRE needet?
                   '/^(lastseen|seen)$/i',// optional regex for key
function ($bot, $data) {
  global $redis;
  if (!$redis->status()) return;
  if ($bot->is_ally_user($data['user']) && !$bot->is_himself($data['user'])) {
    if ($bot->is_ally_user($data['params'][0])) {
      $uid = $bot->get_user_id($data['params'][0]);
      $nick = $redis->hget("user:{$uid}:data", 'name');
      $lastlogin = $redis->hget("user:{$uid}:data", 'lastlogin');
      $summer = substr(date('O', strtotime($lastlogin)),0,3);
      $date = date('d.M.Y H:i:s', strtotime("$lastlogin $summer hours"));
      $message = ucfirst(mb_strtolower($data['params'][0])) . "'s last Login was {$date}";
        if ($data["channel"] == ALLYIN)
          $bot->add_allymsg($message);
        else 
          $bot->add_privmsg($message, $data['user']);
    }
  } else $bot->add_privmsg("No No No!", $data['user']);
}, 'user');

$bot->add_msg_hook(array(PRIVATEIN, ALLYIN),
                   "Chat",                 // command key
                   "LouBot_chat",          // callback function
                   true,                  // is a command PRE needet?
                   '/^chat$/',             // optional regex for key
function ($bot, $data) {
  global $redis;
  if (!$redis->status()) return;
  if ($bot->is_ally_user($data['user']) && !$bot->is_himself($data['user'])) {
    if ($bot->is_ally_user($data['params'][0])) {
      $uid = $bot->get_user_id($data['params'][0]);
      $nick = $redis->hget("user:{$uid}:data", 'name');
      $lastchat = $redis->hget("user:{$uid}:data", 'lastchat');
      $date = date('d.M.Y H:i:s', strtotime($lastchat));
      $message = ucfirst(mb_strtolower($data['params'][0])) . "'s last Chat was {$date}";
        if ($data["channel"] == ALLYIN)
          $bot->add_allymsg($message);
        else 
          $bot->add_privmsg($message, $data['user']);
    }
  } else $bot->add_privmsg("No No No!", $data['user']);
}, 'user');

$bot->add_msg_hook(array(PRIVATEIN, ALLYIN),
                   "LastChat",                 // command key
                   "LouBot_last_chat",         // callback function
                   false,                      // is a command PRE needet?
                   '/.*/i',                    // optional regex for key
function ($bot, $data) {
  global $redis;
  if (!$redis->status()) return;
  if ($bot->is_ally_user($data['user']) && !$bot->is_himself($data['user'])) {
    $uid = $bot->get_user_id($data['user']);
    $redis->hmset("user:{$uid}:data", array(
      'lastchat' => date("m/d/Y H:i:s")
    ));
  };
}, 'user');
?>