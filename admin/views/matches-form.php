<?php
/**
 * 試合登録・編集フォーム
 */

if (!defined('ABSPATH')) {
    exit;
}

$match = null;
if ($action === 'edit' && $match_id) {
    $match = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$tables['matches']} WHERE match_id = %d",
        $match_id
    ));
}

$is_edit = ($action === 'edit' && $match);
$page_title = $is_edit ? '試合編集' : '試合追加';
?>

<div class="wrap">
    <h1><?php echo $page_title; ?></h1>
    
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
    
    <?php if ($is_edit): ?>
        <div style="background: #e7f3ff; border-left: 4px solid #2271b1; padding: 12px 15px; margin: 20px 0;">
            試合情報を保存した後、「参加者管理」から参加選手と順位を登録してください。
        </div>
    <?php endif; ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('save_match'); ?>
        <?php if ($is_edit): ?>
            <input type="hidden" name="match_id" value="<?php echo $match->match_id; ?>">
        <?php endif; ?>
        
        <table class="form-table tt-stats-form-table">
            <tbody>
                <!-- 試合名 -->
                <tr>
                    <th scope="row">
                        <label for="match_name">試合名 <span style="color: red;">*</span></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="match_name" 
                               name="match_name" 
                               value="<?php echo $is_edit ? esc_attr($match->match_name) : ''; ?>" 
                               class="regular-text" 
                               required
                               placeholder="例: 2025年全日本選手権大会">
                    </td>
                </tr>
                
                <!-- 開催日 -->
                <tr>
                    <th scope="row">
                        <label for="match_date">開催日 <span style="color: red;">*</span></label>
                    </th>
                    <td>
                        <input type="date" 
                               id="match_date" 
                               name="match_date" 
                               value="<?php echo $is_edit ? esc_attr($match->match_date) : ''; ?>" 
                               required>
                    </td>
                </tr>
                
                <!-- 会場 -->
                <tr>
                    <th scope="row">
                        <label for="venue">会場</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="venue" 
                               name="venue" 
                               value="<?php echo $is_edit ? esc_attr($match->venue) : ''; ?>" 
                               class="regular-text"
                               placeholder="例: 東京体育館">
                    </td>
                </tr>
                
                <!-- 試合種別 -->
                <tr>
                    <th scope="row">
                        <label for="match_type">試合種別 <span style="color: red;">*</span></label>
                    </th>
                    <td>
                        <select id="match_type" name="match_type" required>
                            <option value="tournament" <?php echo ($is_edit && $match->match_type === 'tournament') ? 'selected' : ''; ?>>トーナメント</option>
                            <option value="league" <?php echo ($is_edit && $match->match_type === 'league') ? 'selected' : ''; ?>>リーグ戦</option>
                            <option value="other" <?php echo ($is_edit && $match->match_type === 'other') ? 'selected' : ''; ?>>その他</option>
                        </select>
                    </td>
                </tr>
                
                <!-- 説明 -->
                <tr>
                    <th scope="row">
                        <label for="description">説明</label>
                    </th>
                    <td>
                        <textarea id="description" 
                                  name="description" 
                                  rows="5" 
                                  class="large-text"
                                  placeholder="試合の詳細や特記事項を記入してください"><?php echo $is_edit ? esc_textarea($match->description) : ''; ?></textarea>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <!-- 保存ボタン -->
        <p class="submit">
            <input type="submit" name="save_match" class="button button-primary" value="<?php echo $is_edit ? '更新' : '登録'; ?>">
            <?php if ($is_edit): ?>
                <a href="<?php echo admin_url('admin.php?page=tt-stats-matches&action=participants&match_id=' . $match->match_id); ?>" class="button">参加者管理に進む →</a>
            <?php endif; ?>
            <a href="<?php echo admin_url('admin.php?page=tt-stats-matches'); ?>" class="button">キャンセル</a>
        </p>
    </form>
</div>
