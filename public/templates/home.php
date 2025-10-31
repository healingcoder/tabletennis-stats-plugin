<?php
/**
 * トップページテンプレート
 * ショートコード: [tt_stats_home]
 */

if (!defined('ABSPATH')) {
    exit;
}

$search_handler = new TT_Stats_Search_Handler();
$recent_matches = $search_handler->get_recent_matches(5);

/**
 * 順位を表示用に変換
 */
function tt_get_rank_display($rank) {
    if ($rank == 1) return '🏆 優勝';
    if ($rank == 2) return '🥈 準優勝';
    if ($rank >= 3 && $rank <= 4) return '🥉 ベスト4';
    if ($rank >= 5 && $rank <= 8) return 'ベスト8';
    if ($rank >= 9 && $rank <= 16) return 'ベスト16';
    if ($rank == 99) return '予選敗退';
    return $rank . '位';
}
?>

<div class="tt-stats-container tt-stats-home">
    <h2>📋 最新の試合結果</h2>
    
    <?php if (!empty($recent_matches)): ?>
        <?php foreach ($recent_matches as $match): ?>
            <?php
            $top_players = $search_handler->get_match_top_players($match->match_id, 16);
            ?>
            <div class="tt-stats-match-card">
                <h3>
                    <a href="?tt_stats_type=match&tt_stats_id=<?php echo $match->match_id; ?>">
                        <?php echo esc_html($match->match_name); ?>
                    </a>
                </h3>
                <div class="tt-stats-match-date">
                    📅 <?php echo esc_html($match->match_date); ?>
                    <?php if ($match->venue): ?>
                        | 📍 <?php echo esc_html($match->venue); ?>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($top_players)): ?>
                    <ul class="tt-stats-ranking-list">
                        <?php foreach ($top_players as $participant): ?>
                            <li class="tt-stats-ranking-item">
                                <span class="tt-stats-rank">
                                    <?php echo tt_get_rank_display($participant->final_rank); ?>
                                </span>
                                <a href="?tt_stats_type=player&tt_stats_id=<?php echo $participant->player_id; ?>" 
                                   class="tt-stats-player-link">
                                    <?php echo esc_html($participant->name); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>参加者情報がまだ登録されていません。</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="tt-stats-match-card">
            <p>まだ試合が登録されていません。</p>
        </div>
    <?php endif; ?>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="<?php echo esc_url(add_query_arg('page', 'search')); ?>" 
           class="tt-stats-search-button" 
           style="display: inline-block; width: auto; padding: 15px 40px;">
            🔍 選手・試合を検索
        </a>
    </div>
</div>
