<?php
global $bot;
$bot->add_category('alliance', array(), PUBLICY);
// crons

// callbacks
$bot->add_alliance_hook("Update",                        // command key
                        "LouBot_alliance_update",        // callback function
function ($bot, $data) {
  global $redis;
  if (empty($data['id'])||!$redis->status()) return;
  $alliance_key = "alliance:{$data['id']}";
  $bot->log('Redis update: '.REDIS_NAMESPACE.$alliance_key);
  $redis->hmset("alliances", array(
    $data['name'] => $data['id']
  ));
  $redis->hmset("{$alliance_key}:data", array(
    'name'      => $data['name'],
    'id'        => $data['id'],
    'short'     => $data['short'],
    'announce'  => $data['announce'],
    'desc'      => $data['desc']
    ));
  $redis->rename("{$alliance_key}:member","{$alliance_key}:_member");
  if (is_array($data['member'])) foreach($data['member'] as $member) {
    $redis->sadd("{$alliance_key}:member", $member['name']);
    $redis->hmset("users", array(
      $member['name'] => $member['id']
    ));
    $user_key = "user:{$member['id']}";
    $redis->hmset("{$user_key}:data", array(
      'id'        => $member['id'],
      'name'      => $member['name'],
      'role'      => $member['role'],
      'rank'      => $member['rank'],
      'points'    => $member['points'],
      'state'     => $member['state'],
      'lastlogin' => $member['lastlogin'],
      'title'     => $member['title'],
      'alliance'  => $data['id']
    ));
    $redis->sadd("{$user_key}:alias", mb_strtoupper($member['name']));
    $redis->hmset("aliase", array(
      mb_strtoupper($member['name']) => $member['id']
    ));
  }
  $diff_old = $redis->sdiff("{$alliance_key}:_member","{$alliance_key}:member");
  if (is_array($diff_old)) foreach($diff_old as $old) {
    $bot->log("Redis: try to delete user from alliance and bot: {$old}");
    $uid = $redis->hget('users', $old);
    // needs an extra event for delete user from alliance and bot?
    // aliase
    /*$aliase = $redis->smembers("user:{$uid}:alias");
    if (is_array($aliase)) foreach($aliase as $alias) {
      $redis->hdel('aliase', $alias);
    }
    $redis->del("user:{$uid}:alias");*/
	
    // bookmarks
    $bookmarks = $redis->smembers("user:{$uid}:bookmarks");
    if (is_array($bookmarks)) foreach($bookmarks as $bookmark) {
      $redis->hdel('bookmarks', $bookmark);
    }
    $redis->del("user:{$uid}:bookmarks");
	
	// NoMail
	$redis->srem("settler:{$alliance_key}:nomail", $old);
	    // Warlord
    $continents = $redis->SMEMBERS("continents");
    if (is_array($continents)) foreach ($continents as $continent) {
      $continent_key = "continent:{$continent}";
      $redis->SREM("else:{$alliance_key}:{$continent_key}:warlord", $old);
    }
  }
  $diff_new = $redis->sdiff("{$alliance_key}:member","{$alliance_key}:_member");
  if (is_array($diff_new)) foreach($diff_new as $new) {
    $bot->log("Redis: try to welcome user to alliance and bot: {$new}");
    $uid = $redis->hget('users', $new);
	
	
    // needs an extra event for welcome user to alliance and bot
    #$bot->add_allymsg("Willkommen bei {$bot->ally_name} {$new}!");
  }
  $redis->del("{$alliance_key}:_member");
  if (is_array($data['roles'])) foreach($data['roles'] as $key => $role) {
    $role_key = "{$alliance_key}:roles";
    $redis->hmset($role_key, array(
      $key => $role
    ));
  }
  if (is_array($data['diplomacy'])) {
    $relation_key = "{$alliance_key}:diplomacy";
    $redis->del("{$relation_key}");
    foreach($data['diplomacy'] as $key => $relation) {
    $redis->hmset("{$relation_key}", array(
      $relation['name'] => $relation['state']
    ));
  }
  }
}, 'alliance');

$bot->add_alliance_hook("SetAllyShort",                        // command key
                        "LouBot_alliance_update_shortname",    // callback function
function ($bot, $data) {
  if (empty($data['id'])||$data['id'] != $bot->ally_id||$bot->ally_shortname == $data['short']) return;
  $bot->set_ally_shortname($data['short']);
  $bot->log("Set AllianceShort: " . $bot->ally_shortname);
}, 'alliance');

$bot->add_tick_event(Cron::TICK5,                         // Cron key
                    "GetAllyUpdate",                      // command key
                    "LouBot_alliance_update_cron",        // callback function
function ($bot, $data) {
  $bot->lou->get_self_alliance();
}, 'alliance');
?>