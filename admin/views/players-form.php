<?php
/**
 * 選手登録・編集フォーム
 */

if (!defined('ABSPATH')) {
    exit;
}

$is_edit = ($action === 'edit' && $player);
$page_title = $is_edit ? '選手編集' : '選手追加';
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
        <?php wp_nonce_field('save_player'); ?>
        <?php if ($is_edit): ?>
            <input type="hidden" name="player_id" value="<?php echo $player->player_id; ?>">
        <?php endif; ?>
        
        <table class="form-table tt-stats-form-table">
            <tbody>
                <!-- 選手名 -->
                <tr>
                    <th scope="row">
                        <label for="name">選手名 <span style="color: red;">*</span></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="<?php echo $is_edit ? esc_attr($player->name) : ''; ?>" 
                               class="regular-text" 
                               required>
                    </td>
                </tr>
                
                <!-- ふりがな -->
                <tr>
                    <th scope="row">
                        <label for="name_kana">ふりがな</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="name_kana" 
                               name="name_kana" 
                               value="<?php echo $is_edit ? esc_attr($player->name_kana) : ''; ?>" 
                               class="regular-text"
                               placeholder="やまだ たろう">
                    </td>
                </tr>
                
                <!-- 性別 -->
                <tr>
                    <th scope="row">
                        <label for="gender">性別 <span style="color: red;">*</span></label>
                    </th>
                    <td>
                        <select id="gender" name="gender" required>
                            <option value="">選択してください</option>
                            <option value="male" <?php echo ($is_edit && $player->gender === 'male') ? 'selected' : ''; ?>>男性</option>
                            <option value="female" <?php echo ($is_edit && $player->gender === 'female') ? 'selected' : ''; ?>>女性</option>
                            <option value="other" <?php echo ($is_edit && $player->gender === 'other') ? 'selected' : ''; ?>>その他</option>
                        </select>
                    </td>
                </tr>
                
                <!-- 出身 -->
                <tr>
                    <th scope="row">
                        <label for="prefecture">出身（都道府県）</label>
                    </th>
                    <td>
                        <select id="prefecture" name="prefecture">
                            <option value="">選択してください</option>
                            <?php
                            $prefectures = array(
                                '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
                                '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
                                '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県',
                                '岐阜県', '静岡県', '愛知県', '三重県',
                                '滋賀県', '京都府', '大阪府', '兵庫県', '奈良県', '和歌山県',
                                '鳥取県', '島根県', '岡山県', '広島県', '山口県',
                                '徳島県', '香川県', '愛媛県', '高知県',
                                '福岡県', '佐賀県', '長崎県', '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
                            );
                            foreach ($prefectures as $pref) {
                                $selected = ($is_edit && $player->prefecture === $pref) ? 'selected' : '';
                                echo "<option value=\"{$pref}\" {$selected}>{$pref}</option>";
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                
                <!-- 戦術 -->
                <tr>
                    <th scope="row">
                        <label for="tactics">戦術</label>
                    </th>
                    <td>
                        <select id="tactics" name="tactics">
                            <option value="">選択してください</option>
                            <option value="right_pen" <?php echo ($is_edit && $player->tactics === 'right_pen') ? 'selected' : ''; ?>>右ペン</option>
                            <option value="left_pen" <?php echo ($is_edit && $player->tactics === 'left_pen') ? 'selected' : ''; ?>>左ペン</option>
                            <option value="right_shake" <?php echo ($is_edit && $player->tactics === 'right_shake') ? 'selected' : ''; ?>>右シェーク</option>
                            <option value="left_shake" <?php echo ($is_edit && $player->tactics === 'left_shake') ? 'selected' : ''; ?>>左シェーク</option>
                            <option value="other" <?php echo ($is_edit && $player->tactics === 'other') ? 'selected' : ''; ?>>その他</option>
                        </select>
                    </td>
                </tr>
                
                <!-- 戦術詳細 -->
                <tr>
                    <th scope="row">
                        <label for="tactics_detail">戦術詳細</label>
                    </th>
                    <td>
                        <textarea id="tactics_detail" 
                                  name="tactics_detail" 
                                  rows="5" 
                                  class="large-text"
                                  placeholder="プレースタイルや特徴を詳しく記入してください"><?php echo $is_edit ? esc_textarea($player->tactics_detail) : ''; ?></textarea>
                        <p class="description">プレースタイルや特徴、得意技などを記入してください。</p>
                    </td>
                </tr>
                
                <!-- プロフィール写真URL -->
                <tr>
                    <th scope="row">
                        <label for="photo_url">プロフィール写真URL</label>
                    </th>
                    <td>
                        <input type="url" 
                               id="photo_url" 
                               name="photo_url" 
                               value="<?php echo $is_edit ? esc_url($player->photo_url) : ''; ?>" 
                               class="regular-text"
                               placeholder="https://example.com/photo.jpg">
                        <p class="description">画像のURLを入力してください。</p>
                    </td>
                </tr>
                
                <!-- プロフィールテキスト -->
                <tr>
                    <th scope="row">
                        <label for="profile_text">その他プロフィール</label>
                    </th>
                    <td>
                        <textarea id="profile_text" 
                                  name="profile_text" 
                                  rows="5" 
                                  class="large-text"><?php echo $is_edit ? esc_textarea($player->profile_text) : ''; ?></textarea>
                        <p class="description">経歴や実績など、その他の情報を記入してください。</p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <!-- 参考動画セクション -->
        <h2>参考動画</h2>
        <table class="form-table">
            <tbody id="video-list">
                <?php
                $video_count = !empty($videos) ? count($videos) : 1;
                for ($i = 0; $i < max($video_count, 1); $i++) {
                    $video = isset($videos[$i]) ? $videos[$i] : null;
                    ?>
                    <tr class="video-row">
                        <th scope="row">動画 <?php echo $i + 1; ?></th>
                        <td>
                            <p>
                                <label>タイトル</label><br>
                                <input type="text" 
                                       name="video_titles[]" 
                                       value="<?php echo $video ? esc_attr($video->video_title) : ''; ?>" 
                                       class="regular-text"
                                       placeholder="例: 全日本選手権決勝">
                            </p>
                            <p>
                                <label>URL</label><br>
                                <input type="url" 
                                       name="video_urls[]" 
                                       value="<?php echo $video ? esc_url($video->video_url) : ''; ?>" 
                                       class="regular-text"
                                       placeholder="https://www.youtube.com/watch?v=...">
                            </p>
                            <p>
                                <label>説明</label><br>
                                <textarea name="video_descriptions[]" 
                                          rows="2" 
                                          class="large-text"><?php echo $video ? esc_textarea($video->video_description) : ''; ?></textarea>
                            </p>
                            <button type="button" class="button remove-video" onclick="removeVideo(this)">削除</button>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        
        <p>
            <button type="button" id="add-video" class="button">+ 動画を追加</button>
        </p>
        
        <!-- 保存ボタン -->
        <p class="submit">
            <input type="submit" name="save_player" class="button button-primary" value="<?php echo $is_edit ? '更新' : '登録'; ?>">
            <a href="<?php echo admin_url('admin.php?page=tt-stats-players'); ?>" class="button">キャンセル</a>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    var videoIndex = <?php echo $video_count; ?>;
    
    $('#add-video').on('click', function() {
        videoIndex++;
        var html = '<tr class="video-row">' +
            '<th scope="row">動画 ' + videoIndex + '</th>' +
            '<td>' +
                '<p><label>タイトル</label><br>' +
                '<input type="text" name="video_titles[]" class="regular-text" placeholder="例: 全日本選手権決勝"></p>' +
                '<p><label>URL</label><br>' +
                '<input type="url" name="video_urls[]" class="regular-text" placeholder="https://www.youtube.com/watch?v=..."></p>' +
                '<p><label>説明</label><br>' +
                '<textarea name="video_descriptions[]" rows="2" class="large-text"></textarea></p>' +
                '<button type="button" class="button remove-video" onclick="removeVideo(this)">削除</button>' +
            '</td>' +
        '</tr>';
        
        $('#video-list').append(html);
    });
});

function removeVideo(button) {
    if (confirm('この動画を削除しますか？')) {
        jQuery(button).closest('.video-row').remove();
    }
}
</script>
