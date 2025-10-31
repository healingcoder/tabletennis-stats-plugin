<?php
/**
 * 選手詳細ページテンプレート
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$db_manager = new TT_Stats_DB_Manager();
$tables = $db_manager->get_table_names();

$player_id = isset($player_id) ? intval($player_id) : 0;
if (!$player_id) {
    echo '<p>選手が見つかりませんでした。</p>';
    return;
}

// 選手情報を取得
$player = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$tables['players']} WHERE player_id = %d",
    $player_id
));

if (!$player) {
    echo '<p>選手が見つかりませんでした。</p>';
    return;
}

// 動画を取得
$videos = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$tables['player_videos']} WHERE player_id = %d ORDER BY display_order ASC",
    $player_id
));

// 試合参加履歴を取得
$match_history = $wpdb->get_results($wpdb->prepare(
    "SELECT mp.*, m.match_name, m.match_date, m.venue
     FROM {$tables['match_participants']} mp
     INNER JOIN {$tables['matches']} m ON mp.match_id = m.match_id
     WHERE mp.player_id = %d
     ORDER BY m.match_date DESC
     LIMIT 20",
    $player_id
));

// 戦術ラベル
$tactics_labels = array(
    'right_pen' => '右ペン',
    'left_pen' => '左ペン',
    'right_shake' => '右シェーク',
    'left_shake' => '左シェーク',
    'other' => 'その他'
);

$gender_labels = array(
    'male' => '男性',
    'female' => '女性',
    'other' => 'その他'
);
?>

<div class="tt-stats-container tt-stats-player-detail">
    <div class="tt-stats-player-header">
        <?php if ($player->photo_url): ?>
            <img src="<?php echo esc_url($player->photo_url); ?>" 
                 alt="<?php echo esc_attr($player->name); ?>" 
                 style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; margin-bottom: 20px;">
        <?php endif; ?>
        
        <h1 class="tt-stats-player-name"><?php echo esc_html($player->name); ?></h1>
        
        <div class="tt-stats-player-info">
            <?php if ($player->name_kana): ?>
                <p><strong>ふりがな:</strong> <?php echo esc_html($player->name_kana); ?></p>
            <?php endif; ?>
            
            <p><strong>性別:</strong> <?php echo $gender_labels[$player->gender] ?? $player->gender; ?></p>
            
            <?php if ($player->prefecture): ?>
                <p><strong>出身:</strong> <?php echo esc_html($player->prefecture); ?></p>
            <?php endif; ?>
            
            <?php if ($player->tactics): ?>
                <p><strong>戦術:</strong> <?php echo $tactics_labels[$player->tactics] ?? $player->tactics; ?></p>
            <?php endif; ?>
            
            <?php if ($player->tactics_detail): ?>
                <div style="margin-top: 15px;">
                    <strong>戦術詳細:</strong>
                    <p style="white-space: pre-wrap; margin-top: 5px;"><?php echo esc_html($player->tactics_detail); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (!empty($videos)): ?>
        <div class="tt-stats-player-videos">
            <h3>📹 参考動画</h3>
            <?php foreach ($videos as $video): ?>
                <div class="tt-stats-video-item">
                    <?php if ($video->video_title): ?>
                        <div class="tt-stats-video-title"><?php echo esc_html($video->video_title); ?></div>
                    <?php endif; ?>
                    
                    <?php
                    // YouTube動画IDを抽出して埋め込み
                    $video_url = $video->video_url;
                    $youtube_id = '';
                    
                    if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $video_url, $matches)) {
                        $youtube_id = $matches[1];
                    } elseif (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $video_url, $matches)) {
                        $youtube_id = $matches[1];
                    }
                    
                    if ($youtube_id): ?>
                        <div class="tt-stats-video-embed">
                            <iframe 
                                src="https://www.youtube.com/embed/<?php echo esc_attr($youtube_id); ?>" 
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen>
                            </iframe>
                        </div>
                    <?php else: ?>
                        <p><a href="<?php echo esc_url($video->video_url); ?>" target="_blank">動画を見る</a></p>
                    <?php endif; ?>
                    
                    <?php if ($video->video_description): ?>
                        <p style="margin-top: 10px; color: #666;"><?php echo esc_html($video->video_description); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="tt-stats-match-results">
        <h3>🏆 試合結果</h3>
        
        <?php if (!empty($match_history)): ?>
            <ul class="tt-stats-result-list">
                <?php foreach ($match_history as $history): ?>
                    <li class="tt-stats-result-list-item">
                        <a href="?tt_stats_type=match&tt_stats_id=<?php echo $history->match_id; ?>" 
                           class="tt-stats-result-match-name">
                            <?php echo esc_html($history->match_name); ?>
                        </a>
                        
                        <?php if ($history->final_rank): ?>
                            <span class="tt-stats-result-rank">
                                <?php 
                                if ($history->final_rank == 1) echo '🏆 優勝';
                                elseif ($history->final_rank == 2) echo '🥈 準優勝';
                                elseif ($history->final_rank >= 3 && $history->final_rank <= 4) echo '🥉 ベスト4';
                                elseif ($history->final_rank >= 5 && $history->final_rank <= 8) echo 'ベスト8';
                                elseif ($history->final_rank >= 9 && $history->final_rank <= 16) echo 'ベスト16';
                                elseif ($history->final_rank == 99) echo '予選敗退';
                                else echo $history->final_rank . '位';
                                ?>
                            </span>
                        <?php endif; ?>
                        
                        <p style="color: #666; margin-top: 5px;">
                            📅 <?php echo esc_html($history->match_date); ?>
                            <?php if ($history->venue): ?>
                                | 📍 <?php echo esc_html($history->venue); ?>
                            <?php endif; ?>
                        </p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>まだ試合結果が登録されていません。</p>
        <?php endif; ?>
    </div>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="javascript:history.back()" class="button">← 戻る</a>
    </div>
</div>
