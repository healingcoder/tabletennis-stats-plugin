<?php
/**
 * 対戦結果一覧表示
 */

if (!defined('ABSPATH')) {
    exit;
}

// フィルター
$filter_match_id = isset($_GET['match_id']) ? intval($_GET['match_id']) : 0;

// ページネーション
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

$where = '1=1';
if ($filter_match_id) {
    $where = $wpdb->prepare('r.match_id = %d', $filter_match_id);
}

$total = $wpdb->get_var("SELECT COUNT(*) FROM {$tables['match_results']} r WHERE {$where}");
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT r.*, 
                m.match_name, m.match_date,
                p1.name as player1_name, 
                p2.name as player2_name
         FROM {$tables['match_results']} r
         INNER JOIN {$tables['matches']} m ON r.match_id = m.match_id
         INNER JOIN {$tables['players']} p1 ON r.player1_id = p1.player_id
         INNER JOIN {$tables['players']} p2 ON r.player2_id = p2.player_id
         WHERE {$where}
         ORDER BY m.match_date DESC, r.result_id DESC
         LIMIT %d OFFSET %d",
        $per_page,
        $offset
    )
);
$total_pages = ceil($total / $per_page);

// 試合一覧（フィルター用）
$matches = $wpdb->get_results(
    "SELECT match_id, match_name, match_date FROM {$tables['matches']} ORDER BY match_date DESC LIMIT 100"
);
?>

<div class="wrap">
    <h1 class="wp-heading-inline">対戦結果管理</h1>
    <a href="<?php echo admin_url('admin.php?page=tt-stats-results&action=add'); ?>" class="page-title-action">新規追加</a>
    
    <?php if ($message): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error); ?></p>
        </div>
    <?php endif; ?>
    
    <hr class="wp-header-end">
    
    <!-- フィルターフォーム -->
    <form method="get" style="margin: 20px 0; background: #fff; padding: 15px; border: 1px solid #ccd0d4;">
        <input type="hidden" name="page" value="tt-stats-results">
        <label for="match-filter"><strong>試合で絞り込み:</strong></label>
        <select id="match-filter" name="match_id" style="margin-left: 10px;">
            <option value="">すべての試合</option>
            <?php foreach ($matches as $match): ?>
                <option value="<?php echo $match->match_id; ?>" <?php selected($filter_match_id, $match->match_id); ?>>
                    <?php echo esc_html($match->match_name); ?> (<?php echo esc_html($match->match_date); ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <input type="submit" class="button" value="絞り込み">
        <?php if ($filter_match_id): ?>
            <a href="<?php echo admin_url('admin.php?page=tt-stats-results'); ?>" class="button">クリア</a>
        <?php endif; ?>
    </form>
    
    <!-- 統計情報 -->
    <div style="background: #fff; border: 1px solid #ccd0d4; padding: 15px; margin-bottom: 20px;">
        <strong>登録対戦結果数:</strong> <?php echo number_format($total); ?>件
    </div>
    
    <!-- 対戦結果一覧テーブル -->
    <?php if (!empty($results)): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>試合名</th>
                    <th style="width: 150px;">回戦</th>
                    <th>選手1</th>
                    <th style="width: 80px;">スコア</th>
                    <th>選手2</th>
                    <th style="width: 120px;">日時</th>
                    <th style="width: 150px;">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $result): ?>
                    <tr>
                        <td><?php echo $result->result_id; ?></td>
                        <td>
                            <strong><?php echo esc_html($result->match_name); ?></strong><br>
                            <small><?php echo esc_html($result->match_date); ?></small>
                        </td>
                        <td><?php echo esc_html($result->round_info); ?></td>
                        <td>
                            <?php echo esc_html($result->player1_name); ?>
                            <?php if ($result->winner_id == $result->player1_id): ?>
                                <span style="color: #00a32a; font-weight: bold;">★</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center; font-weight: bold; font-size: 16px;">
                            <?php echo $result->player1_games; ?> - <?php echo $result->player2_games; ?>
                        </td>
                        <td>
                            <?php echo esc_html($result->player2_name); ?>
                            <?php if ($result->winner_id == $result->player2_id): ?>
                                <span style="color: #00a32a; font-weight: bold;">★</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $result->result_date ? date('Y/m/d H:i', strtotime($result->result_date)) : '-'; ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=tt-stats-results&action=edit&result_id=' . $result->result_id); ?>" class="button button-small">編集</a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=tt-stats-results&action=delete&result_id=' . $result->result_id), 'delete_result_' . $result->result_id); ?>" 
                               class="button button-small" 
                               onclick="return confirm('本当に削除しますか？');">削除</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- ページネーション -->
        <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    $base_url = add_query_arg('paged', '%#%');
                    if ($filter_match_id) {
                        $base_url = add_query_arg('match_id', $filter_match_id, $base_url);
                    }
                    $page_links = paginate_links(array(
                        'base' => $base_url,
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    echo $page_links;
                    ?>
                </div>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <div style="background: #fff; border: 1px solid #ccd0d4; padding: 40px; text-align: center;">
            <p>対戦結果が登録されていません。</p>
            <a href="<?php echo admin_url('admin.php?page=tt-stats-results&action=add'); ?>" class="button button-primary">最初の対戦結果を登録する</a>
        </div>
    <?php endif; ?>
</div>
