<?php
global $bot;
$bot->add_category('statistic', array(), PUBLICY);

// crons
$bot->add_thread_event(Cron::HOURLY, // Cron key
                    "GetMilitaryUpdate", // command key
                    "LouBot_military_continent_update_cron", // callback function
function ($bot, $data) {
  global $redis;
  if (!$redis->status()) return;
  $continents = $redis->smembers("continents");
  $alliance_key = "alliance:{$bot->ally_id}";
  $military_key = "military";
  $settler_key = "settler";
  $else_key = "else";
  if (!($forum_id = $redis->get("{$military_key}:{$alliance_key}:forum:id"))) {
    $forum_id = $bot->forum->get_forum_id_by_name(BOT_STATISTICS_FORUM, true);
    $redis->set("{$military_key}:{$alliance_key}:forum:id", $forum_id);
  }
  
  sort($continents);
  if (is_array($continents) && $bot->forum->exist_forum_id($forum_id)) {
# if (is_array($continents) && $forum_id) {
    $executeThread = array();
    $childs = array_chunk($continents, MAXCHILDS, true);
    $bot->log("Fork: starting fork " . count($childs) . " childs!");
    foreach($childs as $c_id => $c_continents) {
      // define child
      #$bot->lou->check();
      $thread = new executeThread("{$military_key}Thread-" . $c_id);
      $thread->worker = function($_this, $bot, $continents, $forum_id) {
        // working child
        $error = 0;
        $redis = RedisWrapper::getInstance();
        $last_update = $redis->smembers('stats:ContinentPlayerUpdate');
        sort($last_update);
        $last_update = end($last_update);
        $alliance_key = "alliance:{$bot->ally_id}";
        $military_key = "military";
        $settler_key = "settler";
		$else_key = "else";
        $military_chars = 2900;
        $str_time = (string)time();
        $bot->log("Fork: " . $_this->getName() .": start");
        foreach ($continents as $continent) {
          // ** continents
          if ($continent >= 0) {
            $thread_name = 'Continent: '.$continent;
            $bot->debug("Military forum {$thread_name}: start");
            $continent_key = "continent:{$continent}";
            if (!($thread_id = $redis->get("{$military_key}:{$alliance_key}:forum:{$continent_key}:id"))) {
              $thread_id = $bot->forum->get_forum_thread_id_by_title($forum_id, $thread_name, true);
              $redis->set("{$military_key}:{$alliance_key}:forum:{$continent_key}:id", $thread_id);
            }
            $update = false;
            $off_entrys = array();
            $deff_entrys = array();
            $castles = array();
            $wcastles = array();
            $palasts = array();
            $pcities = array();
            $ally_castle_user = array();
            $ally_wcastle_user = array();
            $ally_palast_user = array();
            $ally_cities_user = array();
            $post_residents = array();
            $post_ally = array();
            $post_chunks = array();
# if ($thread_id) {
            if ($bot->forum->exist_forum_thread_id($forum_id, $thread_id)) {
              // ** military top ten offence
              $offence = $redis->hgetall("{$continent_key}:offence");
              $cities = $redis->smembers("{$continent_key}:cities");
              if (is_array($cities)) foreach($cities as $city) {
                $_city = $redis->hgetall('city:'.$redis->hget('cities', $city).':data');
                $_alliance_id = (string) (intval($_city['alliance_id']) >= 1) ? $_city['alliance_id'] : '0';
                if ($_city['state'] == CITY_STATE) {
                  $pcities[$_alliance_id] ++;
                  $ally_cities_user[$_alliance_id][$_city['user_id']] = $redis->hget("user:{$_city['user_id']}:data", 'name');
                }
                elseif ($_city['state'] == CASTLE_STATE) {
                  if ($_city['water'] == WATER_STATE) $wcastles[$_alliance_id] ++;
                  else $castles[$_alliance_id] ++;
                  if ($_city['water'] == WATER_STATE && empty($ally_wcastle_user[$_alliance_id][$_city['user_id']])) {
                    $ally_wcastle_user[$_alliance_id][$_city['user_id']] = $redis->hget("user:{$_city['user_id']}:data", 'name');
                  }
                  else if ($_city['water'] != WATER_STATE && empty($ally_castle_user[$_alliance_id][$_city['user_id']])) {
                    $ally_castle_user[$_alliance_id][$_city['user_id']] = $redis->hget("user:{$_city['user_id']}:data", 'name');
                  }
                }
                elseif ($_city['state'] == PALACE_STATE) {
                  $palasts[$_alliance_id] ++;
                  if (empty($ally_palast_user[$_alliance_id][$_city['user_id']])) {
                    $ally_palast_user[$_alliance_id][$_city['user_id']] = $redis->hget("user:{$_city['user_id']}:data", 'name');
                  }
                }
              }
              $sum_ts = 0;
              $ts = array();
              $alliance = array();
              if (is_array($offence)) foreach($offence as $k => $v) {
                $val = explode('|', $v);
                $ts[$k] += $val[0];
                $sum_ts += $val[0];
                $alliance[$k] = array($k,$val[0],$val[1]);
              }
              $sum_ts_string = preg_replace("/(?<=\d)(?=(\d{3})+(?!\d))/", ".", $sum_ts);
                           
              // ** Ally Castles
              // ** create and/or edit
              // new first post = residents
// post txt
$post_residents[0] = "[b][u][i][alliance]{$bot->ally_shortname}[/alliance][/i][/u][/b] Offensive - Members on {$thread_name}
[u][i]Castles:[/i][/u]  (".((!empty($castles[$bot->ally_id])) ? $castles[$bot->ally_id] : 0).")
".((!empty($ally_castle_user[$bot->ally_id])) ? "[player]".implode('[/player]; [player]', array_values($ally_castle_user[$bot->ally_id]))."[/player]" : "[i]No Castles[/i]")."
";
$post_residents[1] = "
[u][i]Water Castles:[/i][/u]  (".((!empty($wcastles[$bot->ally_id])) ? $wcastles[$bot->ally_id] : 0).")
".((!empty($ally_wcastle_user[$bot->ally_id])) ? "[player]".implode('[/player]; [player]', array_values($ally_wcastle_user[$bot->ally_id]))."[/player]" : "[i]No Water Castles[/i]")."
";
$post_residents[2] = "
[u][i]Palaces:[/i][/u]  (".((!empty($palasts[$bot->ally_id])) ? $palasts[$bot->ally_id] : 0).")
".((!empty($ally_palast_user[$bot->ally_id])) ? "[player]".implode('[/player]; [player]', array_values($ally_palast_user[$bot->ally_id]))."[/player]" : "[i]No Palaces[/i]")."
";

$post_residents[3] = "
[b][u][i][alliance]{$bot->ally_shortname}[/alliance][/i][/u][/b] Defensive - Members on {$thread_name}
[u][i]Cities:[/i][/u]  (".((!empty($pcities[$bot->ally_id])) ? $pcities[$bot->ally_id] : 0).")
".((!empty($ally_cities_user[$bot->ally_id])) ? "[player]".implode('[/player]; [player]', array_values($ally_cities_user[$bot->ally_id]))."[/player]" : "[i]No Cities[/i]")."
";

$warlords = $redis->SMEMBERS("{$else_key}:{$alliance_key}:{$continent_key}:warlord");
$post_residents[4] = "[b][u]{$bot->ally_shortname}[/u][/b] Warlords on {$thread_name}
".((!empty($warlords)) ? "[player]".implode('[/player]; [player]', $warlords)."[/player]" : "[i]No Warlords[/i]")."
";

              
              // ** create and/or edit
              // new second post = offence
              
// post txt
$post_topten = "[b][u]Top Offensive[/u][/b] - TS on {$thread_name}  ({$sum_ts_string} TS)";
/*
".((!empty($off_entrys)) ? implode("
", $off_entrys) : "[i]No TS[/i]").'

';*/
              // ** military top ten defence
              $defence = $redis->hgetall("{$continent_key}:defence");
              $sum_ts = 0;
              $ts = array();
              $alliance = array();
              if (is_array($defence)) foreach($defence as $k => $v) {
                $val = explode('|', $v);
                $ts[$k] += $val[0];
                $alliance[$k] = array($k,$val[0],$val[1]);
                $sum_ts += $val[0];
              }
              $sum_ts_string = preg_replace("/(?<=\d)(?=(\d{3})+(?!\d))/", ".", $sum_ts);
              
              // ** create and/or edit
              // new seond post = defence
// post txt
$post_topten_deff = "[b][u]Top Defensive[/u][/b] - TS on {$thread_name}  ({$sum_ts_string} TS)";
/*
".((!empty($deff_entrys)) ? implode("
", $deff_entrys) : "[i]keine TS[/i]").'

';*/
              // ** forum
              $post = array();
              $_post_id = 0;
              
              for($i = 0, $size = count($post_residents); $i < $size; ++$i) {
                if ((strlen($post_residents[$i]) > $military_chars) || empty($post_residents[$i + 1]) || (strlen($post_residents[$i] . $post_residents[$i + 1]) > $military_chars)) {
                  if (strlen($post_residents[$i]) > $military_chars) {
                    $post_chunks = explode('***chunk***', wordwrap ($post_residents[$i], $military_chars, '***chunk***'));
                    for($_i = 0, $_size = count($post_chunks); $_i < $_size; ++$_i) {
                      $post[$_post_id ++] = $post_chunks[$_i];
                    }
                  } else {
                    $post[$_post_id ++] = $post_residents[$i];
                  }
                } else {
                  $post_residents[$i + 1] = $post_residents[$i] . $post_residents[$i + 1];
                }
              }
              
              $post[$_post_id ++] = $post_topten;
              $post[$_post_id ++] = $post_topten_deff;
              
              if (!empty($ally_castle_user)) foreach($ally_castle_user as $_ally => $_ally_castle_user) {
                // ** Castles
                // ** create and/or edit
                // new first post = residents
                if ($_ally == $bot->ally_id || empty($_ally_castle_user)) continue;
                $ally_name = $redis->hget("alliance:{$_ally}:data", 'name');
                $ally_name = (!$ally_name) ? 'No Alliance' : "[alliance]{$ally_name}[/alliance]";
// post txt
$post_ally[0] = "[b][u]{$ally_name}[/u][/b] Offensive - Player on the {$thread_name}
[u][i]Castles:[/i][/u] (".((!empty($castles[$_ally])) ? $castles[$_ally] : 0).")
".((!empty($ally_castle_user[$_ally])) ? "[player]".implode('[/player]; [player]', array_values($ally_castle_user[$_ally]))."[/player]" : "[i]No Castles[/i]")."
";
$post_ally[1] = "
[u][i]Water Castles:[/i][/u] (".((!empty($wcastles[$_ally])) ? $wcastles[$_ally] : 0).")
".((!empty($ally_wcastle_user[$_ally])) ? "[player]".implode('[/player]; [player]', array_values($ally_wcastle_user[$_ally]))."[/player]" : "[i]No Water Castles[/i]")."
";
$post_ally[2] = "
[u][i]Palaces:[/i][/u] (".((!empty($palasts[$_ally])) ? $palasts[$_ally] : 0).")
".((!empty($ally_palast_user[$_ally])) ? "[player]".implode('[/player]; [player]', array_values($ally_palast_user[$_ally]))."[/player]" : "[i]No Palaces[/i]")."
";
$post_ally[3] = "
[b][u]{$ally_name}[/u][/b] Defensive - Players on the {$thread_name}
[u][i]Cities:[/i][/u] (".((!empty($pcities[$_ally])) ? $pcities[$_ally] : 0).")
".((!empty($ally_cities_user[$_ally]) && $ally_name != 'No Alliance') ? "[player]".implode('[/player]; [player]', array_values($ally_cities_user[$_ally]))."[/player]" : (($ally_name != 'No Alliance') ? "[i]No Cities[/i]" : "[i]without evaluation[/i]"))."
";
                for($i = 0, $size = count($post_ally); $i < $size; ++$i) {
                  if ((strlen($post_ally[$i]) > $military_chars) || empty($post_ally[$i + 1]) || (strlen($post_ally[$i] . $post_ally[$i + 1]) > $military_chars)) {
                    if (strlen($post_ally[$i]) > $military_chars) {
                      $post_chunks = explode('***chunk***', wordwrap ($post_ally[$i], $military_chars, '***chunk***'));
                      for($_i = 0, $_size = count($post_chunks); $_i < $_size; ++$_i) {
                        $post[$_post_id ++] = $post_chunks[$_i];
                      }
                    } else {
                      $post[$_post_id ++] = $post_ally[$i];
                    }
                  } else {
                    $post_ally[$i + 1] = $post_ally[$i] . $post_ally[$i + 1];
                  }
                }
              }

              // new last post = update
              // post txt
              $post_update = "[u]Last Update[/u]: [i]" . date('d.m.Y H:i:s', $str_time) . "[/i] | [u]DataBase[/u]: [i]" . date('d.m.Y H:i:s', $last_update) . "[/i]";
        
              // ** forum
              foreach ($post as $_post_id_post => $_post) {
                if ($_id = $bot->forum->get_thread_post_id_by_num($forum_id, $thread_id, $_post_id_post)) {
                  if (!$bot->forum->edit_alliance_forum_post($forum_id, $thread_id, $_id, $_post)) {
                    $bot->log("Military forum {$thread_name}/{$thread_id}/{$_post_id_post}: edit post error!");
                    $bot->debug($_post);
                    $error = 3;
                  }
                } else {
                  if (!$bot->forum->create_alliance_forum_post($forum_id, $thread_id, $_post)) {
                    $bot->log("Military forum {$thread_name}/{$thread_id}: create post error!");
                    $bot->debug($_post);
                    $error = 3;
                  }
                }
              }
              $_posts_count = $bot->forum->get_thread_post_count($forum_id, $thread_id);
              if ($update && $_posts_count >= count($post)) {
                $bot->log("Military forum {$thread_name}: update(".count($cities).'|'.count($castles).') posts:' . $_posts_count . '|' . count($post));
                for($idx = count($post); $idx <= $_posts_count; $idx ++) {
                  $bot->forum->delete_alliance_forum_threads_post($forum_id, $thread_id, $bot->forum->get_thread_post_id_by_num($forum_id, $thread_id, $idx));
                }
                if (!$bot->forum->create_alliance_forum_post($forum_id, $thread_id, $post_update)) {
                  $bot->log("Military forum {$thread_name}/{$thread_id}: create post error!");
                  $bot->debug($post_update);
                  $error = 3;
                }
              } else {
                $post[$_post_id] = $post_update;
                $bot->log("Military forum {$thread_name}: info(".count($cities).'|'.count($castles).') posts:' . $_posts_count . '|' . count($post));
                for($idx = count($post); $idx <= $_posts_count; $idx ++) {
                   $bot->forum->delete_alliance_forum_threads_post($forum_id, $thread_id, $bot->forum->get_thread_post_id_by_num($forum_id, $thread_id, $idx));
                }
                if ($_id = $bot->forum->get_thread_post_id_by_num($forum_id, $thread_id, $_post_id)) {
                  if (!$bot->forum->edit_alliance_forum_post($forum_id, $thread_id, $_id, $post[$_post_id])) {
                    $bot->log("Military forum {$thread_name}/{$thread_id}/{$_post_id}: edit post error!");
                    $bot->debug($post[$_post_id]);
                    $error = 3;
                  }
                } else {
                  if (!$bot->forum->create_alliance_forum_post($forum_id, $thread_id, $post[$_post_id])) {
                    $bot->log("Military forum {$thread_name}/{$thread_id}: create post error!");
                    $bot->debug($post[$_post_id]);
                    $error = 3;
                  }
                }
              }
            } else {
              $error = 4;
              $bot->log("Military forum {$thread_name}: error!");
              $redis->del("{$military_key}:{$alliance_key}:forum:{$continent_key}:id");
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
    $bot->log("Military error: no forum '" . BOT_STATISTICS_FORUM . "'");
    $redis->del("{$military_key}:{$alliance_key}:forum:id");
  }
}, 'statistic');















$bot->add_privmsg_hook("RebaseStatsForum", // command key
                       "LouBot_rebase_stats_forum", // callback function
                       true, // is a command PRE needet?
                       '', // optional regex for key
function ($bot, $data) {
  global $redis;
  if (!$redis->status()) return;
  if($bot->is_op_user($data['user'])) {
    $continents = $redis->smembers("continents");
    $alliance_key = "alliance:{$bot->ally_id}";
    $military_key = "military";
    
    if (!($forum_id = $redis->get("{$military_key}:{$alliance_key}:forum:id"))) {
      $forum_id = $bot->forum->get_forum_id_by_name(BOT_STATISTICS_FORUM);
    } else $redis->del("{$military_key}:{$alliance_key}:forum:id");
    sort($continents);
    if (is_array($continents) && $bot->forum->exist_forum_id($forum_id)) {
      foreach ($continents as $continent) {
        // ** continents
        if ($continent >= 0) {
          $thread_name = 'C'.$continent;
          $bot->debug("Military forum {$thread_name}: delete");
          $continent_key = "continent:{$continent}";
          if (!($thread_id = $redis->get("{$military_key}:{$alliance_key}:forum:{$continent_key}:id"))) {
            $thread_id = $bot->forum->get_forum_thread_id_by_title($forum_id, $thread_name);
          } else $redis->del("{$military_key}:{$alliance_key}:forum:{$continent_key}:id");
		  $bot->debug("Military forum ({$forum_id}) {$thread_name}: delete ({$thread_id})");
          if ($thread_id) $thread_ids[] = $thread_id;
        }
      }
      if ($bot->forum->delete_alliance_forum_threads($forum_id, $thread_ids)) {
        $bot->add_privmsg("Step1# ".BOT_STATISTICS_FORUM." deleted!", $data['user']);
        $bot->call_event(array('type' => CRON, 'name' => Cron::HOURLY), 'GetMilitaryUpdate');
        $bot->add_privmsg("Step2# ".BOT_STATISTICS_FORUM." rebase done!", $data['user']);
      }
      else $bot->add_privmsg("Mistake with the deletes from: ".BOT_STATISTICS_FORUM."", $data['user']);
    }
  } else $bot->add_privmsg("Ne Ne Ne!", $data['user']);
}, 'operator');

$bot->add_privmsg_hook("ReloadStatsForum", // command key
                       "LouBot_reload_stats_forum", // callback function
                       true, // is a command PRE needet?
                       '', // optional regex for key
function ($bot, $data) {
  global $redis;
  if (!$redis->status()) return;
  if($bot->is_op_user($data['user'])) {
    $alliance_key = "alliance:{$bot->ally_id}";
    $military_key = "military";
    $military_key_keys = $redis->getkeys("{$military_key}:{$alliance_key}:forum:*");
    if (!empty($military_key_keys)) foreach($military_key_keys as $military_key_key) {
      $redis->del("{$military_key_key}");
    }
    $bot->add_privmsg("Step1# ".BOT_STATISTICS_FORUM." REDIS ids deleted!", $data['user']);
    $bot->call_event(array('type' => CRON, 'name' => Cron::HOURLY), 'GetMilitaryUpdate');
    $bot->add_privmsg("Step2# ".BOT_STATISTICS_FORUM." reload done!", $data['user']);
  } else $bot->add_privmsg("Ne Ne Ne!", $data['user']);
}, 'operator');
?>