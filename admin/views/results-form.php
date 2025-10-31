<?php
/**
 * 対戦結果登録・編集フォーム
 */

if (!defined('ABSPATH')) {
    exit;
}

$result = null;
if ($action === 'edit' && $result_id) {
    $result = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$tables['match_results']} WHERE result_id = %d",
        $result_id
    ));
}

$is_edit = ($action === 'edit' && $result);
$page_title = $is_edit ? '対戦結果編集' : '対戦結果追加';

// 試合一覧を取得
$matches = $wpdb->get_results(
    "SELECT match_id, match_name, match_date FROM {$tables['matches']} ORDER BY match_date DESC"
);

// 選手一覧を取得
$players = $wpdb->get_results(
    "SELECT player_id, name, name_kana FROM {$tables['players']} ORDER BY name ASC"
);

// match_idがGETパラメータで渡されている場合
$preselect_match_id = isset($_GET['match_id']) ? intval($_GET['match_id']) : ($is_edit ? $result->match_id : 0);
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
    
    <form method="post" action="">
        <?php wp_nonce_field('save_result'); ?>
        <?php if ($is_edit): ?>
            <input type="hidden" name="result_id" value="<?php echo $result->result_id; ?>">
        <?php endif; ?>
        
        <table class="form-table tt-stats-form-table">
            <tbody>
                <!-- 試合選択 -->
                <tr>
                    <th scope="row">
                        <label for="match_id">試合 <span style="color: red;">*</span></label>
                    </th>
                    <td>
                        <select id="match_id" name="match_id" required style="width: 400px;">
                            <option value="">試合を選択してください</option>
                            <?php foreach ($matches as $match): ?>
                                <option value="<?php echo $match->match_id; ?>" 
                                        <?php selected($preselect_match_id, $match->match_id); ?>>
                                    <?php echo esc_html($match->match_name); ?> (<?php echo esc_html($match->match_date); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                
                <!-- 回戦情報 -->
                <tr>
                    <th scope="row">
                        <label for="round_info">回戦情報</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="round_info" 
                               name="round_info" 
                               value="<?php echo $is_edit ? esc_attr($result->round_info) : ''; ?>" 
                               class="regular-text"
                               placeholder="例: 決勝、準決勝、1回戦など">
                    </td>
                </tr>
                
                <!-- 選手1 -->
                <tr>
                    <th scope="row">
                        <label for="player1_id">選手1 <span style="color: red;">*</span></label>
                    </th>
                    <td>
                        <select id="player1_id" name="player1_id" required style="width: 400px;">
                            <option value="">選手を選択してください</option>
                            <?php foreach ($players as $player): ?>
                                <option value="<?php echo $player->player_id; ?>" 
                                        <?php echo ($is_edit && $result->player1_id == $player->player_id) ? 'selected' : ''; ?>>
                                    <?php echo esc_html($player->name); ?>
                                    <?php if ($player->name_kana): ?>
                                        (<?php echo esc_html($player->name_kana); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                
                <!-- 選手1のゲーム数 -->
                <tr>
                    <th scope="row">
                        <label for="player1_games">選手1のゲーム数 <span style="color: red;">*</span></label>
                    </th>
                    <td>
                        <input type="number" 
                               id="player1_games" 
                               name="player1_games" 
                               value="<?php echo $is_edit ? esc_attr($result->player1_games) : ''; ?>" 
                               min="0" 
                               max="20"
                               required
                               style="width: 100px;"
                               placeholder="4">
                        <p class="description">取得したゲーム数を入力してください。</p>
                    </td>
                </tr>
                
                <!-- VS表示 -->
                <tr>
                    <th scope="row"></th>
                    <td style="text-align: center; font-size: 24px; font-weight: bold; padding: 20px 0;">
                        VS
                    </td>
                </tr>
                
                <!-- 選手2 -->
                <tr>
                    <th scope="row">
                        <label for="player2_id">選手2 <span style="color: red;">*</span></label>
                    </th>
                    <td>
                        <select id="player2_id" name="player2_id" required style="width: 400px;">
                            <option value="">選手を選択してください</option>
                            <?php foreach ($players as $player): ?>
                                <option value="<?php echo $player->player_id; ?>" 
                                        <?php echo ($is_edit && $result->player2_id == $player->player_id) ? 'selected' : ''; ?>>
                                    <?php echo esc_html($player->name); ?>
                                    <?php if ($player->name_kana): ?>
                                        (<?php echo esc_html($player->name_kana); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                
                <!-- 選手2のゲーム数 -->
                <tr>
                    <th scope="row">
                        <label for="player2_games">選手2のゲーム数 <span style="color: red;">*</span></label>
                    </th>
                    <td>
                        <input type="number" 
                               id="player2_games" 
                               name="player2_games" 
                               value="<?php echo $is_edit ? esc_attr($result->player2_games) : ''; ?>" 
                               min="0" 
                               max="20"
                               required
                               style="width: 100px;"
                               placeholder="2">
                        <p class="description">取得したゲーム数を入力してください。</p>
                    </td>
                </tr>
                
                <!-- 日時 -->
                <tr>
                    <th scope="row">
                        <label for="result_date">対戦日時</label>
                    </th>
                    <td>
                        <input type="datetime-local" 
                               id="result_date" 
                               name="result_date" 
                               value="<?php echo $is_edit && $result->result_date ? date('Y-m-d\TH:i', strtotime($result->result_date)) : ''; ?>">
                        <p class="description">対戦が行われた日時を入力してください（省略可）。</p>
                    </td>
                </tr>
                
                <!-- 備考 -->
                <tr>
                    <th scope="row">
                        <label for="notes">備考</label>
                    </th>
                    <td>
                        <textarea id="notes" 
                                  name="notes" 
                                  rows="5" 
                                  class="large-text"
                                  placeholder="試合の詳細、注目ポイントなど"><?php echo $is_edit ? esc_textarea($result->notes) : ''; ?></textarea>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <!-- 保存ボタン -->
        <p class="submit">
            <input type="submit" name="save_result" class="button button-primary" value="<?php echo $is_edit ? '更新' : '登録'; ?>">
            <a href="<?php echo admin_url('admin.php?page=tt-stats-results' . ($preselect_match_id ? '&match_id=' . $preselect_match_id : '')); ?>" class="button">キャンセル</a>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // 同じ選手を選択できないようにバリデーション
    $('form').on('submit', function(e) {
        var player1 = $('#player1_id').val();
        var player2 = $('#player2_id').val();
        
        if (player1 && player2 && player1 === player2) {
            alert('同じ選手を選択することはできません。');
            e.preventDefault();
            return false;
        }
    });
});
</script>
