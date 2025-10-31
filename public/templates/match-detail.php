<?php
/**
 * 試合詳細ページテンプレート
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$db_manager = new TT_Stats_DB_Manager();
$tables = $db_manager->get_table_names();

$match_id = isset($match_id) ? intval($match_id) : 0;
if (!$match_id) {
    echo '<p>試合が見つかりませんでした。</p>';
    return;
}

// 試合情報を取得
$match = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$tables['matches']} WHERE match_id = %d",
    $match_id
));

if (!$match) {
    echo '<p>試合が見つかりませんでした。</p>';
    return;
}

// 参加者と順位を取得
$participants = $wpdb->get_results($wpdb->prepare(
    "SELECT mp.*, p.name, p.name_kana
     FROM {$tables['match_participants']} mp
     INNER JOIN {$tables['players']} p ON mp.player_id = p.player_id
     WHERE mp.match_id = %d
     ORDER BY mp.final_rank ASC",
    $match_id
));

// 対戦結果を取得
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT r.*, 
            p1.name as player1_name, 
            p2.name as player2_name
     FROM {$tables['match_results']} r
     INNER JOIN {$tables['players']} p1 ON r.player1_id = p1.player_id
     INNER JOIN {$tables['players']} p2 ON r.player2_id = p2.player_id
     WHERE r.match_id = %d
     ORDER BY r.result_date DESC, r.result_id DESC",
    $match_id
));
?>

<div class="tt-stats-container tt-stats-match-detail">
    <div class="tt-stats-match-header" style="background: #fff; border-radius: 8px; padding: 30px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h1 style="margin: 0 0 15px; font-size: 28px;"><?php echo esc_html($match->match_name); ?></h1>
        
        <div style="color: #666; line-height: 1.8;">
            <p><strong>📅 開催日:</strong> <?php echo esc_html($match->match_date); ?></p>
            
            <?php if ($match->venue): ?>
                <p><strong>📍 会場:</strong> <?php echo esc_html($match->venue); ?></p>
            <?php endif; ?>
            
            <p><strong>🏆 種別:</strong> 
                <?php 
                $type_labels = array(
                    'tournament' => 'トーナメント',
                    'league' => 'リーグ戦',
                    'other' => 'その他'
                );
                echo $type_labels[$match->match_type] ?? $match->match_type;
                ?>
            </p>
            
            <?php if ($match->description): ?>
                <p style="margin-top: 15px; white-space: pre-wrap;"><?php echo esc_html($match->description); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (!empty($participants)): ?>
        <div class="tt-stats-participants" style="background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
            <h3 style="margin-top: 0;">🏅 成績</h3>
            <ul class="tt-stats-result-list">
                <?php foreach ($participants as $participant): ?>
                    <li class="tt-stats-result-list-item">
                        <span class="tt-stats-result-rank" style="display: inline-block; min-width: 100px;">
                            <?php 
                            if ($participant->final_rank == 1) echo '🏆 優勝';
                            elseif ($participant->final_rank == 2) echo '🥈 準優勝';
                            elseif ($participant->final_rank >= 3 && $participant->final_rank <= 4) echo '🥉 ベスト4';
                            elseif ($participant->final_rank >= 5 && $participant->final_rank <= 8) echo 'ベスト8';
                            elseif ($participant->final_rank >= 9 && $participant->final_rank <= 16) echo 'ベスト16';
                            elseif ($participant->final_rank == 99) echo '予選敗退';
                            else echo $participant->final_rank . '位';
                            ?>
                        </span>
                        <a href="?tt_stats_type=player&tt_stats_id=<?php echo $participant->player_id; ?>" 
                           class="tt-stats-player-link">
                            <?php echo esc_html($participant->name); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($results)): ?>
        <div class="tt-stats-results-detail" style="background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
            <h3 style="margin-top: 0;">⚔️ 対戦結果</h3>
            
            <?php foreach ($results as $result): ?>
                <div style="border: 1px solid #e0e0e0; border-radius: 4px; padding: 15px; margin-bottom: 15px;">
                    <?php if ($result->round_info): ?>
                        <div style="color: #666; font-size: 14px; margin-bottom: 10px;">
                            <?php echo esc_html($result->round_info); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                        <div style="flex: 1; min-width: 120px;">
                            <a href="?tt_stats_type=player&tt_stats_id=<?php echo $result->player1_id; ?>" 
                               style="color: #2271b1; text-decoration: none; font-weight: 600;">
                                <?php echo esc_html($result->player1_name); ?>
                            </a>
                        </div>
                        
                        <div style="font-size: 20px; font-weight: bold; padding: 0 20px;">
                            <?php echo intval($result->player1_games); ?> - <?php echo intval($result->player2_games); ?>
                        </div>
                        
                        <div style="flex: 1; min-width: 120px; text-align: right;">
                            <a href="?tt_stats_type=player&tt_stats_id=<?php echo $result->player2_id; ?>" 
                               style="color: #2271b1; text-decoration: none; font-weight: 600;">
                                <?php echo esc_html($result->player2_name); ?>
                            </a>
                        </div>
                    </div>
                    
                    <?php if ($result->notes): ?>
                        <div style="margin-top: 10px; color: #666; font-size: 14px;">
                            <?php echo esc_html($result->notes); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="javascript:history.back()" class="button" style="display: inline-block; padding: 12px 30px; background: #2271b1; color: #fff; text-decoration: none; border-radius: 4px;">
            ← 戻る
        </a>
    </div>
</div>
