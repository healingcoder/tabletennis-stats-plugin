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

// 対戦結果を取得し、ラウンド順に並び替え
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

// ラウンド情報による優先度を定義して並び替え
$round_priority = array(
    '決勝' => 1,
    '準決勝' => 2,
    '3位決定戦' => 3,
    '準々決勝' => 4,
    'ベスト8' => 4,
    'ベスト16' => 5,
    'ベスト32' => 6,
    'ベスト64' => 7,
    '1回戦' => 8,
    '2回戦' => 9,
    '3回戦' => 10,
    '4回戦' => 11,
    '5回戦' => 12,
);

usort($results, function($a, $b) use ($round_priority) {
    $priority_a = 999;
    $priority_b = 999;
    
    // round_infoから優先度を取得
    foreach ($round_priority as $key => $priority) {
        if (strpos($a->round_info, $key) !== false) {
            $priority_a = $priority;
            break;
        }
    }
    
    foreach ($round_priority as $key => $priority) {
        if (strpos($b->round_info, $key) !== false) {
            $priority_b = $priority;
            break;
        }
    }
    
    return $priority_a - $priority_b;
});
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
    
    <?php if (!empty($participants)): 
        // 順位付き選手（ベスト16まで）とその他の選手に分ける
        $ranked_participants = array();
        $other_participants = array();
        
        foreach ($participants as $participant) {
            if ($participant->final_rank >= 1 && $participant->final_rank <= 16) {
                $ranked_participants[] = $participant;
            } else {
                $other_participants[] = $participant;
            }
        }
    ?>
        <div class="tt-stats-participants" style="background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
            <h3 style="margin-top: 0;">🏅 成績</h3>
            <ul class="tt-stats-result-list" id="ranked-participants">
                <?php foreach ($ranked_participants as $participant): ?>
                    <li class="tt-stats-result-list-item">
                        <span class="tt-stats-result-rank" style="display: inline-block; min-width: 100px;">
                            <?php 
                            if ($participant->final_rank == 1) echo '🏆 優勝';
                            elseif ($participant->final_rank == 2) echo '🥈 準優勝';
                            elseif ($participant->final_rank >= 3 && $participant->final_rank <= 4) echo '🥉 ベスト4';
                            elseif ($participant->final_rank >= 5 && $participant->final_rank <= 8) echo 'ベスト8';
                            elseif ($participant->final_rank >= 9 && $participant->final_rank <= 16) echo 'ベスト16';
                            ?>
                        </span>
                        <a href="?tt_stats_type=player&tt_stats_id=<?php echo $participant->player_id; ?>" 
                           class="tt-stats-player-link">
                            <?php echo esc_html($participant->name); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <?php if (!empty($other_participants)): ?>
                <ul class="tt-stats-result-list" id="other-participants" style="display: none; margin-top: 15px; border-top: 1px solid #e0e0e0; padding-top: 15px;">
                    <?php foreach ($other_participants as $participant): ?>
                        <li class="tt-stats-result-list-item">
                            <span class="tt-stats-result-rank" style="display: inline-block; min-width: 100px;">
                                <?php 
                                if ($participant->final_rank == 99) echo '予選敗退';
                                elseif ($participant->final_rank) echo $participant->final_rank . '位';
                                else echo '出場';
                                ?>
                            </span>
                            <a href="?tt_stats_type=player&tt_stats_id=<?php echo $participant->player_id; ?>" 
                               class="tt-stats-player-link">
                                <?php echo esc_html($participant->name); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <div style="text-align: center; margin-top: 15px;">
                    <button id="toggle-all-participants" 
                            style="padding: 8px 20px; background: #f0f0f0; border: 1px solid #ccc; border-radius: 4px; cursor: pointer; font-size: 14px;">
                        出場選手を表示 (<?php echo count($other_participants); ?>名)
                    </button>
                </div>
                
                <script>
                document.getElementById('toggle-all-participants').addEventListener('click', function() {
                    var otherList = document.getElementById('other-participants');
                    if (otherList.style.display === 'none') {
                        otherList.style.display = 'block';
                        this.textContent = '出場選手を隠す';
                        this.style.background = '#e0e0e0';
                    } else {
                        otherList.style.display = 'none';
                        this.textContent = '出場選手を表示 (<?php echo count($other_participants); ?>名)';
                        this.style.background = '#f0f0f0';
                    }
                });
                </script>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($results)): 
        $default_display_count = 10;
        $total_results = count($results);
    ?>
        <div class="tt-stats-results-detail" style="background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
            <h3 style="margin-top: 0;">⚔️ 対戦結果</h3>
            
            <div id="results-container">
                <?php 
                foreach ($results as $index => $result): 
                    $is_hidden = ($index >= $default_display_count);
                ?>
                    <div class="result-item <?php echo $is_hidden ? 'hidden-result' : ''; ?>" 
                         style="border: 1px solid #e0e0e0; border-radius: 4px; padding: 15px; margin-bottom: 15px; <?php echo $is_hidden ? 'display: none;' : ''; ?>">
                        <?php if ($result->round_info): ?>
                            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-size: 14px; font-weight: 600; padding: 8px 12px; margin: -15px -15px 15px -15px; border-radius: 4px 4px 0 0;">
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
            
            <?php if ($total_results > $default_display_count): ?>
                <div style="text-align: center; margin-top: 15px;">
                    <button id="toggle-all-results" 
                            style="padding: 10px 30px; background: #2271b1; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 600;">
                        すべての試合を見る (残り<?php echo $total_results - $default_display_count; ?>試合)
                    </button>
                </div>
                
                <script>
                document.getElementById('toggle-all-results').addEventListener('click', function() {
                    var hiddenResults = document.querySelectorAll('.hidden-result');
                    var isShowingAll = this.getAttribute('data-showing-all') === 'true';
                    
                    if (!isShowingAll) {
                        hiddenResults.forEach(function(result) {
                            result.style.display = 'block';
                        });
                        this.textContent = '試合を折りたたむ';
                        this.setAttribute('data-showing-all', 'true');
                        this.style.background = '#135e96';
                    } else {
                        hiddenResults.forEach(function(result) {
                            result.style.display = 'none';
                        });
                        this.textContent = 'すべての試合を見る (残り<?php echo $total_results - $default_display_count; ?>試合)';
                        this.setAttribute('data-showing-all', 'false');
                        this.style.background = '#2271b1';
                        
                        // 最初の試合までスクロール
                        document.getElementById('results-container').scrollIntoView({ behavior: 'smooth' });
                    }
                });
                </script>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="javascript:history.back()" class="button" style="display: inline-block; padding: 12px 30px; background: #2271b1; color: #fff; text-decoration: none; border-radius: 4px;">
            ← 戻る
        </a>
    </div>
</div>
