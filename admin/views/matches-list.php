<?php
/**
 * 試合一覧表示
 */

if (!defined('ABSPATH')) {
    exit;
}

// ページネーション
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

// 検索
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$where = '1=1';
if ($search) {
    $where = $wpdb->prepare(
        'match_name LIKE %s',
        '%' . $wpdb->esc_like($search) . '%'
    );
}

$total = $wpdb->get_var("SELECT COUNT(*) FROM {$tables['matches']} WHERE {$where}");
$matches = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$tables['matches']} WHERE {$where} ORDER BY match_date DESC, match_id DESC LIMIT %d OFFSET %d",
        $per_page,
        $offset
    )
);
$total_pages = ceil($total / $per_page);
?>

<div class="wrap">
    <h1 class="wp-heading-inline">試合管理</h1>
    <a href="<?php echo admin_url('admin.php?page=tt-stats-matches&action=add'); ?>" class="page-title-action">新規追加</a>
    
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
    
    <!-- 検索フォーム -->
    <form method="get" style="margin: 20px 0;">
        <input type="hidden" name="page" value="tt-stats-matches">
        <p class="search-box">
            <label class="screen-reader-text" for="match-search-input">試合を検索:</label>
            <input type="search" id="match-search-input" name="s" value="<?php echo esc_attr($search); ?>" placeholder="試合名で検索">
            <input type="submit" id="search-submit" class="button" value="検索">
        </p>
    </form>
    
    <!-- 統計情報 -->
    <div style="background: #fff; border: 1px solid #ccd0d4; padding: 15px; margin-bottom: 20px;">
        <strong>登録試合数:</strong> <?php echo number_format($total); ?>件
    </div>
    
    <!-- 試合一覧テーブル -->
    <?php if (!empty($matches)): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>試合名</th>
                    <th style="width: 120px;">開催日</th>
                    <th style="width: 200px;">会場</th>
                    <th style="width: 100px;">種別</th>
                    <th style="width: 80px;">参加者</th>
                    <th style="width: 200px;">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($matches as $match): ?>
                    <?php
                    // 参加者数を取得
                    $participant_count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tables['match_participants']} WHERE match_id = %d",
                        $match->match_id
                    ));
                    ?>
                    <tr>
                        <td><?php echo $match->match_id; ?></td>
                        <td>
                            <strong>
                                <a href="<?php echo admin_url('admin.php?page=tt-stats-matches&action=edit&match_id=' . $match->match_id); ?>">
                                    <?php echo esc_html($match->match_name); ?>
                                </a>
                            </strong>
                        </td>
                        <td><?php echo esc_html($match->match_date); ?></td>
                        <td><?php echo esc_html($match->venue); ?></td>
                        <td>
                            <?php 
                            $type_labels = array(
                                'tournament' => 'トーナメント',
                                'league' => 'リーグ戦',
                                'other' => 'その他'
                            );
                            echo $type_labels[$match->match_type] ?? $match->match_type;
                            ?>
                        </td>
                        <td><?php echo number_format($participant_count); ?>名</td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=tt-stats-matches&action=edit&match_id=' . $match->match_id); ?>" class="button button-small">編集</a>
                            <a href="<?php echo admin_url('admin.php?page=tt-stats-matches&action=participants&match_id=' . $match->match_id); ?>" class="button button-small">参加者</a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=tt-stats-matches&action=delete&match_id=' . $match->match_id), 'delete_match_' . $match->match_id); ?>" 
                               class="button button-small" 
                               onclick="return confirm('本当に削除しますか？関連する参加者情報と対戦結果も削除されます。');">削除</a>
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
                    $page_links = paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
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
            <p>試合が登録されていません。</p>
            <a href="<?php echo admin_url('admin.php?page=tt-stats-matches&action=add'); ?>" class="button button-primary">最初の試合を登録する</a>
        </div>
    <?php endif; ?>
</div>
