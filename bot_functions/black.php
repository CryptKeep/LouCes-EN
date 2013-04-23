<?php
global $bot;
$bot->add_category('black', array(), PUBLICY);

// crons
$bot->add_thread_event(Cron::HOURLY,                                 // Cron key
                       "GetBlackUpdate",                             // command key
                       "LouBot_black_continent_player_update_cron",  // callback function
function ($bot, $data) {
  global $redis;
  if (!$redis->status()) return;
  $continents = $redis->smembers("continents");
  $alliance_key = "alliance:{$bot->ally_id}";
  $black_key = "black";
  $settler_key = "settler";
  if (!($forum_id = $redis->get("{$black_key}:{$alliance_key}:forum:id"))) {
    $forum_id = $bot->forum->get_forum_id_by_name(BOT_BLACK_FORUM, true);
    $redis->set("{$black_key}:{$alliance_key}:forum:id", $forum_id);
  }
  
  sort($continents);
  if (is_array($continents) && $bot->forum->exist_forum_id($forum_id)) {
#  if (is_array($continents) && $forum_id) {
    $executeThread = array();
    $childs = array_chunk($continents, MAXCHILDS, true);
    $bot->log("Fork: starting fork " . count($childs) . " childs!");
    foreach($childs as $c_id => $c_continents) {
      // define child
      #$bot->lou->check();
      $thread = new executeThread("{$black_key}Thread-" . $c_id);
      $thread->worker = function($_this, $bot, $continents, $forum_id) {
        global $_black_user_points, $_black_user_state, $_black_user_last;
        // working child
        $error = 0;
        $redis = RedisWrapper::getInstance();
        $last_update = $redis->smembers('stats:ContinentPlayerUpdate');
        sort($last_update);
        $last_update = end($last_update);
        $alliance_key = "alliance:{$bot->ally_id}";
        $black_key = "black";
        $settler_key = "settler";
        $inactive_chunks = 20;      // 30
        $str_time = (string)time();
        $bot->log("Fork: " . $_this->getName() .": start");
        foreach ($continents as $continent) {
          // ** continents
          if ($continent >= 0) {
            $thread_name = 'Continent: '.$continent;
            $bot->debug("Black forum {$thread_name}: start");
            $continent_key = "continent:{$continent}";
            if (!($thread_id = $redis->get("{$black_key}:{$alliance_key}:forum:{$continent_key}:id"))) {
              $thread_id = $bot->forum->get_forum_thread_id_by_title($forum_id, $thread_name, true);
              $redis->set("{$black_key}:{$alliance_key}:forum:{$continent_key}:id", $thread_id);
            }
            $update = false;
#            if ($thread_id) {
              if ($bot->forum->exist_forum_thread_id($forum_id, $thread_id)) {
              // ** members 3days
              $residents = array();
              $aresidents = $redis->smembers("{$settler_key}:{$alliance_key}:{$continent_key}:residents");
              $redis->rename("{$black_key}:{$alliance_key}:{$continent_key}:residents", "{$black_key}:{$alliance_key}:{$continent_key}:_residents");
              $_start = mktime(date("H"), 0, 0, date("n"), date("j") -3, date("Y"));
              $_end   = mktime(date("H"), 0, 0, date("n"), date("j"), date("Y"));
              if (is_array($aresidents)) {
                foreach($aresidents as $resident) {
                  $user_id = $redis->hget('users', $resident);
                  $user_key = "user:{$user_id}";
                  $user_stats = array_flip($redis->zrangebyscore("{$user_key}:{$continent_key}:stats", "({$_start}", "({$_end}", array('withscores' => TRUE)));
                  $_tend    = end($user_stats);
                  $_tstart  = reset($user_stats);
                  if ($_tend == $_tstart) continue;// new settler
                  if (count($user_stats) <= 1) {
                    if ($redis->sadd("{$black_key}:{$alliance_key}:{$continent_key}:_residents", $resident)) $update = true;
                    $redis->sadd("{$black_key}:{$alliance_key}:{$continent_key}:residents", $resident);  
                    array_push($residents, $resident);
                    continue;
                  } else {
                    $_black_user_points = array();
                    array_walk($user_stats, function(&$val, $key) {
                      global $_black_user_points;
                      //alliance_id|city_count|points|rank
                      $val = explode('|', $val);
                      if (!in_array($val[2], $_black_user_points)) array_push($_black_user_points, $val[2]);
                    });
                    if (count($_black_user_points) <= 1) {
                      if ($redis->sadd("{$black_key}:{$alliance_key}:{$continent_key}:_residents", $resident)) $update = true;
                      $redis->sadd("{$black_key}:{$alliance_key}:{$continent_key}:residents", $resident);
                      array_push($residents, $resident);
                    };
                  }
                }
              } 
              $inactive_members = array();
              if (!empty($residents)) {
                sort($residents, SORT_STRING);
                foreach($residents as $k => $v) {
                  $user_id = $redis->hget('users', $v);
                  $user_citys = count($redis->smembers("user:{$user_id}:cities"));
                  $user_k_citys = count($redis->smembers("user:{$user_id}:{$continent_key}:cities"));
                  if ($user_k_citys == 0) continue;
                  $user_key = "user:{$user_id}";
                  $user_stats = array_flip($redis->zrangebyscore("{$user_key}:stats", "(-inf", "(+inf", array('withscores' => TRUE)));
                  $_tend = end($user_stats);
                  reset($user_stats);
                  list(,,$_black_user_last,) = explode('|', $_tend);
                  $_black_user_state = array();
                  array_walk($user_stats, function(&$val, $key) {
                    global $_black_user_state, $_black_user_last;
                    //alliance_id|city_count|points|rank
                    $val = explode('|', $val);
                    if ($val[2] <> $_black_user_last) array_push($_black_user_state, $key);
                  });
                  sort($_black_user_state);
                  $inactive = floor((time() - end($_black_user_state)) / 86400);
                  if ($inactive == 0) $inactive_text = " - [i]Active, on another Continent![/i]";
                  else if ($inactive >= 365 ) continue; //$inactive_text = " - [i]Fehler![/i]";
                  else $inactive_text = " - [b]{$inactive}[/b] Day(s) Inactive!";
                  $user_points = $redis->hget("user:{$user_id}:data", 'points');
                  $user_k_points = $redis->hget("user:{$user_id}:{$continent_key}:data", 'points');
                  $inactive_members[] = "[player]{$v}[/player]" . " - ({$user_k_points}/{$user_points}) {$user_k_citys}/{$user_citys} Cities{$inactive_text}";
                }
              }
      #       $redis->del("{$black_key}:{$alliance_key}:{$continent_key}:_residents");
              // ** all users
              // ** members 3days
              $residents2 = array();
              $bresidents = $redis->smembers("{$continent_key}:residents");
              $redis->rename("{$black_key}:{$continent_key}:residents", "{$black_key}:{$continent_key}:_residents");
              $_start = mktime(date("H"), 0, 0, date("n"), date("j") -3, date("Y"));
              $_end   = mktime(date("H"), 0, 0, date("n"), date("j"), date("Y"));
              if (is_array($bresidents)) {
                foreach($bresidents as $resident) {
                  if(in_array($resident, $aresidents)) continue; // ally member
                  $user_id = $redis->hget('users', $resident);
                  $user_key = "user:{$user_id}";
                  $user_stats = array_flip($redis->zrangebyscore("{$user_key}:{$continent_key}:stats", "({$_start}", "({$_end}", array('withscores' => TRUE)));
                  $_tend    = end($user_stats);
                  $_tstart  = reset($user_stats);
                  if ($_tend == $_tstart) continue;// new settler
                  if (count($user_stats) <= 1) {
                    if ($redis->sadd("{$black_key}:{$continent_key}:_residents", $resident)) $update = true;
                    $redis->sadd("{$black_key}:{$continent_key}:residents", $resident);
                    array_push($residents2, $resident);
                    continue;
                  } else {
                    $_black_user_points = array();
                    array_walk($user_stats, function(&$val, $key) {
                      global $_black_user_points;
                      //alliance_id|city_count|points|rank
                      $val = explode('|', $val);
                      if (!in_array($val[2], $_black_user_points)) array_push($_black_user_points, $val[2]);
                    });
                    if (count($_black_user_points) <= 1) {
                      if ($redis->sadd("{$black_key}:{$continent_key}:_residents", $resident)) $update = true;
                      $redis->sadd("{$black_key}:{$continent_key}:residents", $resident);
                      array_push($residents2, $resident);
                    };
                  }
                }
              } 
              $inactive_allys = array();
              if (!empty($residents2)) {
                sort($residents2, SORT_STRING);
                foreach($residents2 as $k => $v) {
                  $user_id = $redis->hget('users', $v);
                  $user_citys = count($redis->smembers("user:{$user_id}:cities"));
                  $user_k_citys = count($redis->smembers("user:{$user_id}:{$continent_key}:cities"));
                  if ($user_k_citys == 0) continue;
                  $user_key = "user:{$user_id}";
                  $user_stats = array_flip($redis->zrangebyscore("{$user_key}:stats", "(-inf", "(+inf", array('withscores' => TRUE)));
                  $_tend = end($user_stats);
                  reset($user_stats);
                  list(,,$_black_user_last,) = explode('|', $_tend);
                  $_black_user_state = array();
                  array_walk($user_stats, function(&$val, $key) {
                    global $_black_user_state, $_black_user_last;
                    //alliance_id|city_count|points|rank
                    $val = explode('|', $val);
                    if ($val[2] <> $_black_user_last) array_push($_black_user_state, $key);
                  });
                  sort($_black_user_state);
                  $ally_id = $redis->hget("user:{$user_id}:data", 'alliance');
                  $user_points = $redis->hget("user:{$user_id}:data", 'points');
                  $user_K_points = $redis->hget("user:{$user_id}:{$continent_key}:data", 'points');
                  $ally_name = $redis->hget("alliance:{$ally_id}:data", 'name');
                  $inactive = floor((time() - end($_black_user_state)) / 86400);
                  if ($inactive == 0) $inactive_text = " - [i]Active, on another Continent![/i]";
                  else if ($inactive >= 365 ) continue; //$inactive_text = " - [i]Fehler![/i]";
                  else $inactive_text = " - [b]{$inactive}[/b] Day(s) Inactive!";
                  if ($ally_name) $inactive_allys[$ally_name][] = "[player]{$v}[/player]" . " - ({$user_K_points}/{$user_points}) {$user_k_citys}/{$user_citys} Cities{$inactive_text}";
                  elseif (intval($user_points) > 100) $inactive_allys['zzzzzzzzzz'][] = "[player]{$v}[/player]" . " - ({$user_K_points}/{$user_points}) {$user_k_citys}/{$user_citys} Cities{$inactive_text}";
                }
              }
      #       $redis->del("{$black_key}:{$continent_key}:_residents");
              // ** create and/or edit
              // new first post = residents
// post txt
$post_residents = "[b][u][i][alliance]{$bot->ally_shortname}[/alliance][/i][/u][/b] Inactive Players (3 days) on the {$thread_name}

".((!empty($inactive_members)) ? implode("
", $inactive_members) : "[i]No Players[/i]").'

';
              // ** forum
              $post = array();
              $_post_id = 0;
              $post[$_post_id ++] = $post_residents;
              // new first post = residents2
// post txt
$post_inactive_head = "[b][u]%%ALLY%%[/u][/b] Inactive Players (3 days) on the {$thread_name}

";
$post_inactive_footer = '

';

              if (!empty($inactive_allys)) {
                asort($inactive_allys);
                foreach ($inactive_allys as $ally => $inactive_strings) { 
                  $chunks = array();
                  $post_inactive = array();
                  $chunks = array_chunk($inactive_strings, $inactive_chunks);
                  if(is_array($chunks)) foreach($chunks as $page => $inactive) {
                    $post_inactive[$page] = ($page == 0) ? (($ally == 'zzzzzzzzzz') ? str_replace('%%ALLY%%', 'Others > 100Pts.', $post_inactive_head) : str_replace('%%ALLY%%', "[i][alliance]{$ally}[/alliance][/i]", $post_inactive_head)) : "";
                    $post_inactive[$page] .= implode("
", $inactive);
                    $post_inactive[$page] .= ($page == (count($chunks)-1)) ? $post_inactive_footer : "";
                  }
                  // ** forum
                  foreach($post_inactive as $_post_inactive) {
                    $post[$_post_id ++] = $_post_inactive;
                  }
                }
              } else {
                $post[$_post_id ++] = str_replace('%%ALLY%%', 'Other', $post_inactive_head) . "[i]No Players[/i]" . $post_settlers_footer;
              }
              // new last post = update
              // post txt
              $post_update = "[u]Last Update[/u]: [i]" . date('d.m.Y H:i:s', $str_time) . "[/i] | [u]DataBase[/u]: [i]" . date('d.m.Y H:i:s', $last_update) . "[/i]";
        
              // ** forum            
              foreach ($post as $_post_id_post => $_post) {
                if ($_id = $bot->forum->get_thread_post_id_by_num($forum_id, $thread_id, $_post_id_post)) {
                  if (!$bot->forum->edit_alliance_forum_post($forum_id, $thread_id, $_id, $_post)) {
                    $bot->log("Black forum {$thread_name}/{$thread_id}/{$_post_id_post}: edit post error!");
                    $bot->debug($_post);
                    $error = 3;
                  }
                } else {
                  if (!$bot->forum->create_alliance_forum_post($forum_id, $thread_id, $_post)) {
                    $bot->log("Black forum {$thread_name}/{$thread_id}: create post error!");
                    $bot->debug($_post);
                    $error = 3;
                  } 
                }
              }
              $_posts_count = $bot->forum->get_thread_post_count($forum_id, $thread_id);
              if ($update && $_posts_count >= count($post)) {
                $bot->log("Black forum {$thread_name}: update(".count($residents).'|'.count($residents2).') posts:' . $_posts_count . '|' . count($post));
                for($idx = count($post); $idx <= $_posts_count; $idx ++) {
                  $bot->forum->delete_alliance_forum_threads_post($forum_id, $thread_id, $bot->forum->get_thread_post_id_by_num($forum_id, $thread_id, $idx));
                }
                if (!$bot->forum->create_alliance_forum_post($forum_id, $thread_id, $post_update)) {
                  $bot->log("Black forum {$thread_name}/{$thread_id}: create post error!");
                  $bot->debug($post_update);
                  $error = 3;
                }
              } else {
                $post[$_post_id] = $post_update;
                $bot->log("Black forum {$thread_name}: info(".count($residents).'|'.count($residents2).') posts:' . $_posts_count . '|' . count($post));
                for($idx = count($post); $idx <= $_posts_count; $idx ++) {
                  $bot->forum->delete_alliance_forum_threads_post($forum_id, $thread_id, $bot->forum->get_thread_post_id_by_num($forum_id, $thread_id, $idx));
                }
                if ($_id = $bot->forum->get_thread_post_id_by_num($forum_id, $thread_id, $_post_id)) {
                  if (!$bot->forum->edit_alliance_forum_post($forum_id, $thread_id, $_id, $post[$_post_id])) {
                    $bot->log("Black forum {$thread_name}/{$thread_id}/{$_post_id}: edit post error!");
                    $bot->debug($post[$_post_id]);
                    $error = 3;
                  }
                } else {
                  if (!$bot->forum->create_alliance_forum_post($forum_id, $thread_id, $post[$_post_id])) {
                    $bot->log("Black forum {$thread_name}/{$thread_id}: create post error!");
                    $bot->debug($post[$_post_id]);
                    $error = 3;
                  }
                }
              }
            } else {
              $error = 4;
              $bot->log("Black forum {$thread_name}: error!");
              $redis->del("{$black_key}:{$alliance_key}:forum:{$continent_key}:id");
            }
          }
        }
        exit($error);
      };
      $thread->start($thread, $bot, $c_continents, $forum_id);
      $bot->debug("Started " . $thread->getName() . " with PID " . $thread->getPid() . "...");
      array_push($executeThread, $thread);
    }  
    foreach($executeThread as $thread) {
      pcntl_waitpid($thread->getPid(), $status, WUNTRACED);
      $bot->debug("Stopped " . $thread->getPid() . '@'. $thread->getName() . (!pcntl_wifexited($status) ? ' with' : ' without') . " errors!");
      if (pcntl_wifsignaled($status)) $bot->log($thread->getPid() . '@'. $thread->getName() . " stopped with state #" . pcntl_wexitstatus($status) . " errors!");
    }
    $bot->log("Fork: closing, all childs done!");
    unset($executeThread);
    $redis->reinstance();  
  } else { 
    $bot->log("Black error: no forum '" . BOT_BLACK_FORUM . "'");
    $redis->del("{$black_key}:{$alliance_key}:forum:id");
  }
}, 'black');

// callbacks
$bot->add_privmsg_hook("RebaseBlackForum",            // command key
                       "LouBot_rebase_black_forum",   // callback function
                       true,                          // is a command PRE needet?
                       '',                            // optional regex for key
function ($bot, $data) {
  global $redis;
  if (!$redis->status()) return;
  if($bot->is_op_user($data['user'])) {
    $continents = $redis->smembers("continents");
    $alliance_key = "alliance:{$bot->ally_id}";
    $black_key = "black";
    
    if (!($forum_id = $redis->get("{$black_key}:{$alliance_key}:forum:id"))) {
      $forum_id = $bot->forum->get_forum_id_by_name(BOT_BLACK_FORUM);
    } else $redis->del("{$black_key}:{$alliance_key}:forum:id");
    sort($continents);
    if (is_array($continents) && $bot->forum->exist_forum_id($forum_id)) {
      foreach ($continents as $continent) {
        // ** continents
        if ($continent >= 0) {
          $thread_name = 'C'.$continent;
          $bot->debug("Black forum {$thread_name}: delete");
          $continent_key = "continent:{$continent}";
          if (!($thread_id = $redis->get("{$black_key}:{$alliance_key}:forum:{$continent_key}:id"))) {
            $thread_id = $bot->forum->get_forum_thread_id_by_title($forum_id, $thread_name);
          } else $redis->del("{$black_key}:{$alliance_key}:forum:{$continent_key}:id");
         if ($thread_id) $thread_ids[] = $thread_id;
        }
      }
      if ($bot->forum->delete_alliance_forum_threads($forum_id, $thread_ids)) {
        $bot->add_privmsg("Step1# ".BOT_BLACK_FORUM." deleted!", $data['user']);
        $bot->call_event(array('type' => CRON, 'name' => Cron::HOURLY), 'GetBlackUpdate');
        $bot->add_privmsg("Step2# ".BOT_BLACK_FORUM." rebase done!", $data['user']);
      }
      else $bot->add_privmsg("Mistake with the deletes from: ".BOT_BLACK_FORUM."", $data['user']);
    }
  } else $bot->add_privmsg("Ne Ne Ne!", $data['user']);
}, 'operator');

$bot->add_privmsg_hook("ReloadBlackForum",            // command key
                       "LouBot_reload_black_forum",   // callback function
                       true,                          // is a command PRE needet?
                       '',                            // optional regex for key
function ($bot, $data) {
  global $redis;
  if (!$redis->status()) return;
  if($bot->is_op_user($data['user'])) {
    $alliance_key = "alliance:{$bot->ally_id}";
    $black_key = "black";
    $black_key_keys = $redis->getkeys("{$black_key}:{$alliance_key}:forum:*");
    if (!empty($black_key_keys)) foreach($black_key_keys as $black_key_key) {
      $redis->del("{$black_key_key}");
    }
    $bot->add_privmsg("Step1# ".BOT_BLACK_FORUM." REDIS ids deleted!", $data['user']);
    $bot->call_event(array('type' => CRON, 'name' => Cron::HOURLY), 'GetBlackUpdate');
    $bot->add_privmsg("Step2# ".BOT_BLACK_FORUM." reload done!", $data['user']);
  } else $bot->add_privmsg("Ne Ne Ne!", $data['user']);
}, 'operator');
?>
