<?php
/**
 * 選手一覧表示
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">選手管理</h1>
    <a href="<?php echo admin_url('admin.php?page=tt-stats-players&action=add'); ?>" class="page-title-action">新規追加</a>
    
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
        <input type="hidden" name="page" value="tt-stats-players">
        <p class="search-box">
            <label class="screen-reader-text" for="player-search-input">選手を検索:</label>
            <input type="search" id="player-search-input" name="s" value="<?php echo esc_attr($search); ?>" placeholder="選手名で検索">
            <input type="submit" id="search-submit" class="button" value="検索">
        </p>
    </form>
    
    <!-- 統計情報 -->
    <div style="background: #fff; border: 1px solid #ccd0d4; padding: 15px; margin-bottom: 20px;">
        <strong>登録選手数:</strong> <?php echo number_format($total); ?>名
    </div>
    
    <!-- 選手一覧テーブル -->
    <?php if (!empty($players)): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>選手名</th>
                    <th>ふりがな</th>
                    <th style="width: 80px;">性別</th>
                    <th style="width: 120px;">出身</th>
                    <th style="width: 120px;">戦術</th>
                    <th style="width: 150px;">登録日</th>
                    <th style="width: 150px;">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($players as $player): ?>
                    <tr>
                        <td><?php echo $player->player_id; ?></td>
                        <td>
                            <strong>
                                <a href="<?php echo admin_url('admin.php?page=tt-stats-players&action=edit&player_id=' . $player->player_id); ?>">
                                    <?php echo esc_html($player->name); ?>
                                </a>
                            </strong>
                        </td>
                        <td><?php echo esc_html($player->name_kana); ?></td>
                        <td>
                            <?php 
                            $gender_labels = array('male' => '男性', 'female' => '女性', 'other' => 'その他');
                            echo $gender_labels[$player->gender] ?? $player->gender;
                            ?>
                        </td>
                        <td><?php echo esc_html($player->prefecture); ?></td>
                        <td>
                            <?php 
                            $tactics_labels = array(
                                'right_pen' => '右ペン',
                                'left_pen' => '左ペン',
                                'right_shake' => '右シェーク',
                                'left_shake' => '左シェーク',
                                'other' => 'その他'
                            );
                            echo $tactics_labels[$player->tactics] ?? '';
                            ?>
                        </td>
                        <td><?php echo date('Y/m/d', strtotime($player->created_at)); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=tt-stats-players&action=edit&player_id=' . $player->player_id); ?>" class="button button-small">編集</a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=tt-stats-players&action=delete&player_id=' . $player->player_id), 'delete_player_' . $player->player_id); ?>" 
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
            <p>選手が登録されていません。</p>
            <a href="<?php echo admin_url('admin.php?page=tt-stats-players&action=add'); ?>" class="button button-primary">最初の選手を登録する</a>
        </div>
    <?php endif; ?>
</div>
