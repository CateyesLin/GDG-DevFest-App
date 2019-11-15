<?php

$result = array(
  'tracks' => array(),
  'sessions' => array(),
  'speakers' => array(),
  'sponsors' => array(),
  'teams' => array(),
);

$src = 'https://sessionize.com/api/v2/i4umgqvi/view/All';
$data = file_get_contents($src);
$json = json_decode($data);

/* 1. 處理 tracks */
foreach ($json->{'rooms'} as $room) {
  preg_match('/.*\((.+)\).*/', $room->{'name'}, $matches);
  $track_name = $matches[1];
  if (null == $track_name) {
    $track_name = $room->{'name'};
  }

  $result['tracks'][] = array(
    'id' => '' . $room->{'id'},
    'title' => $track_name
  );
}


/* 2. 處理 sessions */
// 預處理處理 tags
$tag_data = array();
foreach ($json->{'categories'} as $item_group) {
  foreach($item_group->{'items'} as $item) {
    $tag_data[$item->{'id'}] = $item->{'name'};
  }
}

$session_data = array();

foreach ($json->{'sessions'} as $session) {
  $session_data[$session->{'id'}] = $session->{'title'};

  $start_timestamp = strtotime($session->{'startsAt'});
  $end_timestamp = strtotime($session->{'endsAt'});

  $tags = array();
  foreach($session->{'categoryItems'} as $tag_id) {
    $tag = $tag_data[$tag_id];
    if (false === in_array($tag, $tags)) {
      $tags[] = $tag_data[$tag_id];
    }
  }

  $result['sessions'][] = array(
    'session_id' => $session->{'id'},
    'session_title' => $session->{'title'},
    'session_start_time' => date('Y-m-d H:i:s', $start_timestamp),
    'session_total_time' => "" . ($end_timestamp - $start_timestamp) / 60,
    'session_desc' => $session->{'description'},
    // 一個 session 可能不只一個講者
    'speaker_id' => $session->{'speakers'}[0],
    'track_id' => '' . $session->{'roomId'},
    'tags' => $tags,
    'links' => array(
      'presentation' => '',
      'video' => '',
      'hackmd' => ''
    )
  );
}

/* 3. 處理 speakers */
foreach ($json->{'speakers'} as $speaker) {
  $result['speakers'][] = array(
    'speaker_id' => $speaker->{'id'},
    'speaker_name' => $speaker->{'fullName'},
    'speaker_image' => $speaker->{'profilePicture'},
    'speaker_desc' => $speaker->{'bio'},
    // 一個講者一天可能不只講一個
    'speaker_session' => $session_data[$speaker->{'sessions'}[0]],
    'fb_url' => null,
    'github_url' => null,
    'linkedin_url' => null,
    'twitter_url' => null
  );
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
