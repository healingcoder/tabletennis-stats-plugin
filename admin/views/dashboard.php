<?php
/**
 * 管理画面 - ダッシュボード
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$db_manager = new TT_Stats_DB_Manager();
$tables = $db_manager->get_table_names();

// 統計情報を取得
$total_players = $wpdb->get_var("SELECT COUNT(*) FROM {$tables['players']}");
$total_matches = $wpdb->get_var("SELECT COUNT(*) FROM {$tables['matches']}");
$total_results = $wpdb->get_var("SELECT COUNT(*) FROM {$tables['match_results']}");
$recent_matches = $wpdb->get_results(
    "SELECT * FROM {$tables['matches']} ORDER BY match_date DESC LIMIT 5"
);
?>

<div class="wrap">
    <h1>卓球成績管理システム - ダッシュボード</h1>
    
    <div class="tt-stats-dashboard">
        <div class="tt-stats-cards">
            <div class="tt-stats-card">
                <h3>登録選手数</h3>
                <p class="tt-stats-number"><?php echo number_format($total_players); ?></p>
                <a href="<?php echo admin_url('admin.php?page=tt-stats-players'); ?>" class="button">選手管理</a>
            </div>
            
            <div class="tt-stats-card">
                <h3>登録試合数</h3>
                <p class="tt-stats-number"><?php echo number_format($total_matches); ?></p>
                <a href="<?php echo admin_url('admin.php?page=tt-stats-matches'); ?>" class="button">試合管理</a>
            </div>
            
            <div class="tt-stats-card">
                <h3>対戦結果数</h3>
                <p class="tt-stats-number"><?php echo number_format($total_results); ?></p>
                <a href="<?php echo admin_url('admin.php?page=tt-stats-results'); ?>" class="button">対戦結果</a>
            </div>
        </div>
        
        <div class="tt-stats-recent">
            <h2>最近の試合</h2>
            <?php if ($recent_matches): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>試合名</th>
                            <th>開催日</th>
                            <th>会場</th>
                            <th>種別</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_matches as $match): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($match->match_name); ?></strong>
                                </td>
                                <td><?php echo esc_html($match->match_date); ?></td>
                                <td><?php echo esc_html($match->venue); ?></td>
                                <td><?php echo esc_html($match->match_type); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>まだ試合が登録されていません。</p>
                <a href="<?php echo admin_url('admin.php?page=tt-stats-matches'); ?>" class="button button-primary">試合を登録する</a>
            <?php endif; ?>
        </div>
        
        <div class="tt-stats-quick-links">
            <h2>クイックリンク</h2>
            <ul>
                <li><a href="<?php echo admin_url('admin.php?page=tt-stats-players'); ?>" class="button">📝 選手を追加</a></li>
                <li><a href="<?php echo admin_url('admin.php?page=tt-stats-matches'); ?>" class="button">🏆 試合を追加</a></li>
                <li><a href="<?php echo admin_url('admin.php?page=tt-stats-results'); ?>" class="button">⚔️ 対戦結果を追加</a></li>
                <li><a href="<?php echo admin_url('admin.php?page=tt-stats-import'); ?>" class="button">📥 データをインポート</a></li>
            </ul>
        </div>
        
        <div class="tt-stats-info">
            <h2>使い方</h2>
            <ol>
                <li><strong>選手を登録:</strong> 「選手管理」から選手情報を登録します。</li>
                <li><strong>試合を登録:</strong> 「試合管理」から試合情報を登録します。</li>
                <li><strong>参加者を登録:</strong> 試合に参加した選手と順位を登録します。</li>
                <li><strong>対戦結果を登録:</strong> 「対戦結果」から詳細な試合結果を登録します。</li>
                <li><strong>公開:</strong> ショートコード [tt_stats_home] を固定ページに貼り付けて公開します。</li>
            </ol>
            
            <h3>ショートコード一覧</h3>
            <ul>
                <li><code>[tt_stats_home]</code> - トップページ（最近の試合表示）</li>
                <li><code>[tt_stats_search]</code> - 検索ページ</li>
                <li><code>[tt_stats_player id="123"]</code> - 選手詳細ページ</li>
                <li><code>[tt_stats_match id="456"]</code> - 試合詳細ページ</li>
            </ul>
        </div>
    </div>
</div>

<style>
.tt-stats-dashboard {
    margin-top: 20px;
}

.tt-stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.tt-stats-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    text-align: center;
}

.tt-stats-card h3 {
    margin-top: 0;
    font-size: 16px;
    color: #666;
}

.tt-stats-number {
    font-size: 48px;
    font-weight: bold;
    color: #2271b1;
    margin: 10px 0;
}

.tt-stats-recent,
.tt-stats-quick-links,
.tt-stats-info {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin-bottom: 20px;
}

.tt-stats-quick-links ul {
    list-style: none;
    padding: 0;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.tt-stats-quick-links li {
    margin: 0;
}

.tt-stats-info ol,
.tt-stats-info ul {
    line-height: 1.8;
}

.tt-stats-info code {
    background: #f0f0f1;
    padding: 2px 6px;
    border-radius: 3px;
}
</style>
