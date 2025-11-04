<?php
/**
 * スマホアプリ用フロントエンド管理クラス
 */

if (!defined('ABSPATH')) {
    exit;
}

class TT_Stats_Mobile_App {
    
    private $wpdb;
    private $tables;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        $db_manager = new TT_Stats_DB_Manager();
        $this->tables = $db_manager->get_table_names();
        
        // カスタムリライトルールを追加
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'template_redirect'));
        
        // Ajax アクションを登録
        add_action('wp_ajax_tt_app_search_players', array($this, 'ajax_search_players'));
        add_action('wp_ajax_nopriv_tt_app_search_players', array($this, 'ajax_search_players'));
        
        add_action('wp_ajax_tt_app_search_matches', array($this, 'ajax_search_matches'));
        add_action('wp_ajax_nopriv_tt_app_search_matches', array($this, 'ajax_search_matches'));
        
        add_action('wp_ajax_tt_app_get_player', array($this, 'ajax_get_player'));
        add_action('wp_ajax_nopriv_tt_app_get_player', array($this, 'ajax_get_player'));
        
        add_action('wp_ajax_tt_app_get_match', array($this, 'ajax_get_match'));
        add_action('wp_ajax_nopriv_tt_app_get_match', array($this, 'ajax_get_match'));
    }
    
    /**
     * カスタムリライトルールを追加
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^tt-app/?$',
            'index.php?tt_app=home',
            'top'
        );
        
        add_rewrite_rule(
            '^tt-app/search/?$',
            'index.php?tt_app=search',
            'top'
        );
        
        add_rewrite_rule(
            '^tt-app/player/([0-9]+)/?$',
            'index.php?tt_app=player&tt_app_id=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^tt-app/match/([0-9]+)/?$',
            'index.php?tt_app=match&tt_app_id=$matches[1]',
            'top'
        );
    }
    
    /**
     * クエリ変数を追加
     */
    public function add_query_vars($vars) {
        $vars[] = 'tt_app';
        $vars[] = 'tt_app_id';
        return $vars;
    }
    
    /**
     * テンプレートリダイレクト
     */
    public function template_redirect() {
        $page = get_query_var('tt_app');
        
        if ($page) {
            $this->load_app_template($page);
            exit;
        }
    }
    
    /**
     * アプリテンプレートを読み込み
     */
    private function load_app_template($page) {
        $id = get_query_var('tt_app_id');
        
        // ヘッダーを出力
        $this->render_header();
        
        // コンテンツを出力
        switch ($page) {
            case 'home':
                $this->render_home();
                break;
            case 'search':
                $this->render_search();
                break;
            case 'player':
                $this->render_player($id);
                break;
            case 'match':
                $this->render_match($id);
                break;
            default:
                $this->render_404();
        }
        
        // フッターを出力
        $this->render_footer();
    }
    
    /**
     * ヘッダーを出力
     */
    private function render_header() {
        ?>
        <!DOCTYPE html>
        <html lang="ja">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
            <meta name="apple-mobile-web-app-capable" content="yes">
            <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
            <title>卓球成績アプリ</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Arial, sans-serif;
                    background: #f5f5f5;
                    color: #333;
                    line-height: 1.6;
                    padding-bottom: 70px;
                }
                
                /* ヘッダー */
                .app-header {
                    background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
                    color: white;
                    padding: 15px 20px;
                    position: sticky;
                    top: 0;
                    z-index: 100;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                
                .app-header h1 {
                    font-size: 20px;
                    font-weight: 700;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                
                /* コンテナ */
                .app-container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 15px;
                }
                
                /* カード */
                .card {
                    background: white;
                    border-radius: 12px;
                    padding: 20px;
                    margin-bottom: 15px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                }
                
                .card h2 {
                    font-size: 18px;
                    margin-bottom: 15px;
                    color: #ff6b35;
                    border-bottom: 2px solid #ff6b35;
                    padding-bottom: 8px;
                }
                
                /* 検索フォーム */
                .search-box {
                    position: relative;
                    margin-bottom: 15px;
                }
                
                .search-box input {
                    width: 100%;
                    padding: 15px 45px 15px 20px;
                    border: 2px solid #e0e0e0;
                    border-radius: 25px;
                    font-size: 16px;
                    transition: all 0.3s;
                }
                
                .search-box input:focus {
                    outline: none;
                    border-color: #ff6b35;
                    box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
                }
                
                .search-box button {
                    position: absolute;
                    right: 5px;
                    top: 50%;
                    transform: translateY(-50%);
                    background: #ff6b35;
                    color: white;
                    border: none;
                    border-radius: 50%;
                    width: 40px;
                    height: 40px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    transition: all 0.3s;
                }
                
                .search-box button:active {
                    transform: translateY(-50%) scale(0.95);
                }
                
                /* タブ */
                .tabs {
                    display: flex;
                    gap: 10px;
                    margin-bottom: 20px;
                    overflow-x: auto;
                    -webkit-overflow-scrolling: touch;
                }
                
                .tab-btn {
                    flex: 1;
                    min-width: 120px;
                    padding: 12px 20px;
                    background: white;
                    border: 2px solid #e0e0e0;
                    border-radius: 25px;
                    font-size: 15px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s;
                    white-space: nowrap;
                }
                
                .tab-btn.active {
                    background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
                    color: white;
                    border-color: #ff6b35;
                }
                
                .tab-content {
                    display: none;
                }
                
                .tab-content.active {
                    display: block;
                }
                
                /* リスト */
                .list-item {
                    background: white;
                    border-radius: 10px;
                    padding: 15px;
                    margin-bottom: 10px;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
                    text-decoration: none;
                    display: block;
                    color: #333;
                    transition: all 0.3s;
                }
                
                .list-item:active {
                    transform: scale(0.98);
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }
                
                .list-item-title {
                    font-size: 16px;
                    font-weight: 600;
                    margin-bottom: 5px;
                    color: #333;
                }
                
                .list-item-meta {
                    font-size: 13px;
                    color: #666;
                    display: flex;
                    gap: 10px;
                    flex-wrap: wrap;
                }
                
                .badge {
                    display: inline-block;
                    padding: 4px 10px;
                    border-radius: 12px;
                    font-size: 12px;
                    font-weight: 600;
                    background: #f0f0f0;
                    color: #666;
                }
                
                .badge-gold {
                    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
                    color: #8b6914;
                }
                
                .badge-silver {
                    background: linear-gradient(135deg, #c0c0c0 0%, #e8e8e8 100%);
                    color: #666;
                }
                
                .badge-bronze {
                    background: linear-gradient(135deg, #cd7f32 0%, #e89e5f 100%);
                    color: #5c3a1a;
                }
                
                /* ローディング */
                .loading {
                    text-align: center;
                    padding: 40px 20px;
                    color: #999;
                }
                
                .loading::after {
                    content: '読み込み中...';
                    animation: loading 1.5s infinite;
                }
                
                @keyframes loading {
                    0%, 100% { opacity: 0.5; }
                    50% { opacity: 1; }
                }
                
                /* 空状態 */
                .empty-state {
                    text-align: center;
                    padding: 60px 20px;
                    color: #999;
                }
                
                .empty-state-icon {
                    font-size: 48px;
                    margin-bottom: 15px;
                    opacity: 0.3;
                }
                
                /* ボトムナビゲーション */
                .bottom-nav {
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    background: white;
                    border-top: 1px solid #e0e0e0;
                    display: flex;
                    justify-content: space-around;
                    padding: 10px 0;
                    box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
                }
                
                .nav-item {
                    flex: 1;
                    text-align: center;
                    text-decoration: none;
                    color: #999;
                    padding: 8px;
                    transition: all 0.3s;
                }
                
                .nav-item.active {
                    color: #ff6b35;
                }
                
                .nav-icon {
                    font-size: 24px;
                    display: block;
                    margin-bottom: 4px;
                }
                
                .nav-label {
                    font-size: 11px;
                    display: block;
                }
                
                /* プロフィール */
                .profile-header {
                    text-align: center;
                    padding: 20px 0;
                }
                
                .profile-photo {
                    width: 100px;
                    height: 100px;
                    border-radius: 50%;
                    object-fit: cover;
                    border: 4px solid #ff6b35;
                    margin-bottom: 15px;
                }
                
                .profile-name {
                    font-size: 24px;
                    font-weight: 700;
                    margin-bottom: 5px;
                }
                
                .profile-kana {
                    font-size: 14px;
                    color: #999;
                    margin-bottom: 10px;
                }
                
                .profile-stats {
                    display: flex;
                    gap: 10px;
                    margin-top: 15px;
                }
                
                .stat-item {
                    flex: 1;
                    text-align: center;
                    padding: 12px;
                    background: #f8f8f8;
                    border-radius: 8px;
                }
                
                .stat-value {
                    font-size: 18px;
                    font-weight: 700;
                    color: #ff6b35;
                }
                
                .stat-label {
                    font-size: 12px;
                    color: #999;
                    margin-top: 4px;
                }
                
                /* VS表示 */
                .vs-match {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 15px 0;
                    border-bottom: 1px solid #f0f0f0;
                }
                
                .vs-match:last-child {
                    border-bottom: none;
                }
                
                .vs-player {
                    flex: 1;
                    text-align: center;
                }
                
                .vs-player-name {
                    font-size: 14px;
                    font-weight: 600;
                    margin-bottom: 5px;
                }
                
                .vs-score {
                    font-size: 24px;
                    font-weight: 700;
                    color: #ff6b35;
                    padding: 0 15px;
                }
                
                .vs-winner {
                    color: #4caf50;
                }
                
                .vs-loser {
                    color: #999;
                }
                
                /* レスポンシブ調整 */
                @media (max-width: 360px) {
                    .app-header h1 {
                        font-size: 18px;
                    }
                    
                    .list-item-title {
                        font-size: 15px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="app-header">
                <h1>🏓 卓球成績アプリ</h1>
            </div>
        <?php
    }
    
    /**
     * フッターを出力
     */
    private function render_footer() {
        $current_page = get_query_var('tt_app', 'home');
        ?>
            <nav class="bottom-nav">
                <a href="<?php echo home_url('/tt-app/'); ?>" class="nav-item <?php echo $current_page === 'home' ? 'active' : ''; ?>">
                    <span class="nav-icon">🏠</span>
                    <span class="nav-label">ホーム</span>
                </a>
                <a href="<?php echo home_url('/tt-app/search/'); ?>" class="nav-item <?php echo $current_page === 'search' ? 'active' : ''; ?>">
                    <span class="nav-icon">🔍</span>
                    <span class="nav-label">検索</span>
                </a>
            </nav>
            
            <script>
                // 検索機能
                function searchData(type) {
                    const query = document.getElementById('search-' + type).value;
                    const resultsDiv = document.getElementById('results-' + type);
                    
                    if (!query) {
                        resultsDiv.innerHTML = '<div class="empty-state"><div class="empty-state-icon">🔍</div><p>キーワードを入力してください</p></div>';
                        return;
                    }
                    
                    resultsDiv.innerHTML = '<div class="loading"></div>';
                    
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=tt_app_search_' + type + '&query=' + encodeURIComponent(query)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data.length > 0) {
                            let html = '';
                            data.data.forEach(item => {
                                if (type === 'players') {
                                    html += `
                                        <a href="<?php echo home_url('/tt-app/player/'); ?>${item.player_id}" class="list-item">
                                            <div class="list-item-title">${item.name}</div>
                                            <div class="list-item-meta">
                                                ${item.name_kana ? '<span>' + item.name_kana + '</span>' : ''}
                                                ${item.prefecture ? '<span>📍 ' + item.prefecture + '</span>' : ''}
                                            </div>
                                        </a>
                                    `;
                                } else if (type === 'matches') {
                                    html += `
                                        <a href="<?php echo home_url('/tt-app/match/'); ?>${item.match_id}" class="list-item">
                                            <div class="list-item-title">${item.match_name}</div>
                                            <div class="list-item-meta">
                                                <span>📅 ${item.match_date}</span>
                                                ${item.venue ? '<span>📍 ' + item.venue + '</span>' : ''}
                                            </div>
                                        </a>
                                    `;
                                }
                            });
                            resultsDiv.innerHTML = html;
                        } else {
                            resultsDiv.innerHTML = '<div class="empty-state"><div class="empty-state-icon">😕</div><p>見つかりませんでした</p></div>';
                        }
                    })
                    .catch(error => {
                        resultsDiv.innerHTML = '<div class="empty-state"><div class="empty-state-icon">⚠️</div><p>エラーが発生しました</p></div>';
                    });
                }
                
                // タブ切り替え
                function switchTab(tabName) {
                    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                    
                    document.querySelector(`[onclick="switchTab('${tabName}')"]`).classList.add('active');
                    document.getElementById('tab-' + tabName).classList.add('active');
                }
            </script>
        </body>
        </html>
        <?php
    }
    
    /**
     * ホーム画面を出力
     */
    private function render_home() {
        $recent_matches = $this->wpdb->get_results(
            "SELECT * FROM {$this->tables['matches']} ORDER BY match_date DESC LIMIT 10"
        );
        ?>
        <div class="app-container">
            <div class="card">
                <h2>📋 最近の試合</h2>
                <?php if ($recent_matches): ?>
                    <?php foreach ($recent_matches as $match): ?>
                        <a href="<?php echo home_url('/tt-app/match/' . $match->match_id); ?>" class="list-item">
                            <div class="list-item-title"><?php echo esc_html($match->match_name); ?></div>
                            <div class="list-item-meta">
                                <span>📅 <?php echo esc_html($match->match_date); ?></span>
                                <?php if ($match->venue): ?>
                                    <span>📍 <?php echo esc_html($match->venue); ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🏓</div>
                        <p>試合データがありません</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * 検索画面を出力
     */
    private function render_search() {
        ?>
        <div class="app-container">
            <div class="card">
                <div class="tabs">
                    <button class="tab-btn active" onclick="switchTab('players')">👤 選手</button>
                    <button class="tab-btn" onclick="switchTab('matches')">🏆 試合</button>
                </div>
                
                <!-- 選手検索タブ -->
                <div id="tab-players" class="tab-content active">
                    <div class="search-box">
                        <input type="text" id="search-players" placeholder="選手名を入力..." onkeypress="if(event.key==='Enter') searchData('players')">
                        <button onclick="searchData('players')">🔍</button>
                    </div>
                    <div id="results-players">
                        <div class="empty-state">
                            <div class="empty-state-icon">🔍</div>
                            <p>選手名を入力して検索してください</p>
                        </div>
                    </div>
                </div>
                
                <!-- 試合検索タブ -->
                <div id="tab-matches" class="tab-content">
                    <div class="search-box">
                        <input type="text" id="search-matches" placeholder="試合名を入力..." onkeypress="if(event.key==='Enter') searchData('matches')">
                        <button onclick="searchData('matches')">🔍</button>
                    </div>
                    <div id="results-matches">
                        <div class="empty-state">
                            <div class="empty-state-icon">🔍</div>
                            <p>試合名を入力して検索してください</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * 選手詳細画面を出力
     */
    private function render_player($player_id) {
        $player = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->tables['players']} WHERE player_id = %d",
            $player_id
        ));
        
        if (!$player) {
            $this->render_404();
            return;
        }
        
        // 試合履歴を取得
        $match_history = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT mp.*, m.match_name, m.match_date
             FROM {$this->tables['match_participants']} mp
             INNER JOIN {$this->tables['matches']} m ON mp.match_id = m.match_id
             WHERE mp.player_id = %d
             ORDER BY m.match_date DESC
             LIMIT 20",
            $player_id
        ));
        
        // この選手の対戦結果を取得
        $player_results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT r.*, m.match_name, m.match_date,
                    p1.name as player1_name, p2.name as player2_name
             FROM {$this->tables['match_results']} r
             INNER JOIN {$this->tables['matches']} m ON r.match_id = m.match_id
             INNER JOIN {$this->tables['players']} p1 ON r.player1_id = p1.player_id
             INNER JOIN {$this->tables['players']} p2 ON r.player2_id = p2.player_id
             WHERE r.player1_id = %d OR r.player2_id = %d
             ORDER BY m.match_date DESC, r.result_id DESC
             LIMIT 30",
            $player_id,
            $player_id
        ));
        
        $tactics_labels = array(
            'right_pen' => '右ペン',
            'left_pen' => '左ペン',
            'right_shake' => '右シェーク',
            'left_shake' => '左シェーク',
            'other' => 'その他'
        );
        ?>
        <div class="app-container">
            <div class="card">
                <div class="profile-header">
                    <?php if ($player->photo_url): ?>
                        <img src="<?php echo esc_url($player->photo_url); ?>" alt="<?php echo esc_attr($player->name); ?>" class="profile-photo">
                    <?php else: ?>
                        <div class="profile-photo" style="background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%); display: flex; align-items: center; justify-content: center; font-size: 40px; color: white;">👤</div>
                    <?php endif; ?>
                    
                    <div class="profile-name"><?php echo esc_html($player->name); ?></div>
                    <?php if ($player->name_kana): ?>
                        <div class="profile-kana"><?php echo esc_html($player->name_kana); ?></div>
                    <?php endif; ?>
                    
                    <div class="profile-stats">
                        <?php if ($player->prefecture): ?>
                            <div class="stat-item">
                                <div class="stat-value">📍</div>
                                <div class="stat-label"><?php echo esc_html($player->prefecture); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($player->tactics): ?>
                            <div class="stat-item">
                                <div class="stat-value">🏓</div>
                                <div class="stat-label"><?php echo $tactics_labels[$player->tactics] ?? $player->tactics; ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($player->profile_text): ?>
                    <h2>📝 プロフィール</h2>
                    <p style="line-height: 1.8; white-space: pre-wrap;"><?php echo esc_html($player->profile_text); ?></p>
                <?php endif; ?>
            </div>
            
            <?php if ($player_results): ?>
                <div class="card">
                    <h2>⚔️ 対戦結果</h2>
                    <?php foreach ($player_results as $result): ?>
                        <div class="vs-match">
                            <div class="vs-player <?php echo $result->winner_id == $result->player1_id ? 'vs-winner' : 'vs-loser'; ?>">
                                <a href="<?php echo home_url('/tt-app/player/' . $result->player1_id); ?>" style="text-decoration: none; color: inherit;">
                                    <div class="vs-player-name"><?php echo esc_html($result->player1_name); ?></div>
                                </a>
                            </div>
                            <div class="vs-score">
                                <?php echo intval($result->player1_games); ?> - <?php echo intval($result->player2_games); ?>
                            </div>
                            <div class="vs-player <?php echo $result->winner_id == $result->player2_id ? 'vs-winner' : 'vs-loser'; ?>">
                                <a href="<?php echo home_url('/tt-app/player/' . $result->player2_id); ?>" style="text-decoration: none; color: inherit;">
                                    <div class="vs-player-name"><?php echo esc_html($result->player2_name); ?></div>
                                </a>
                            </div>
                        </div>
                        <?php if ($result->round_info || $result->match_name): ?>
                            <div style="font-size: 12px; color: #999; text-align: center; margin-top: -10px; margin-bottom: 15px;">
                                <?php echo esc_html($result->match_name); ?>
                                <?php if ($result->round_info): ?>
                                    - <?php echo esc_html($result->round_info); ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($match_history): ?>
                <div class="card">
                    <h2>🏆 試合結果</h2>
                    <?php foreach ($match_history as $history): ?>
                        <a href="<?php echo home_url('/tt-app/match/' . $history->match_id); ?>" class="list-item">
                            <div class="list-item-title"><?php echo esc_html($history->match_name); ?></div>
                            <div class="list-item-meta">
                                <span>📅 <?php echo esc_html($history->match_date); ?></span>
                                <?php if ($history->final_rank): ?>
                                    <span class="badge <?php 
                                        if ($history->final_rank == 1) echo 'badge-gold';
                                        elseif ($history->final_rank == 2) echo 'badge-silver';
                                        elseif ($history->final_rank >= 3 && $history->final_rank <= 4) echo 'badge-bronze';
                                    ?>">
                                        <?php 
                                        if ($history->final_rank == 1) echo '🏆 優勝';
                                        elseif ($history->final_rank == 2) echo '🥈 準優勝';
                                        elseif ($history->final_rank >= 3 && $history->final_rank <= 4) echo '🥉 ベスト4';
                                        elseif ($history->final_rank >= 5 && $history->final_rank <= 8) echo 'ベスト8';
                                        else echo $history->final_rank . '位';
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * 試合詳細画面を出力
     */
    private function render_match($match_id) {
        $match = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->tables['matches']} WHERE match_id = %d",
            $match_id
        ));
        
        if (!$match) {
            $this->render_404();
            return;
        }
        
        // 参加者を取得
        $participants = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT mp.*, p.name
             FROM {$this->tables['match_participants']} mp
             INNER JOIN {$this->tables['players']} p ON mp.player_id = p.player_id
             WHERE mp.match_id = %d
             ORDER BY mp.final_rank ASC",
            $match_id
        ));
        
        // 対戦結果を取得
        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT r.*, p1.name as player1_name, p2.name as player2_name
             FROM {$this->tables['match_results']} r
             INNER JOIN {$this->tables['players']} p1 ON r.player1_id = p1.player_id
             INNER JOIN {$this->tables['players']} p2 ON r.player2_id = p2.player_id
             WHERE r.match_id = %d
             ORDER BY r.result_date DESC",
            $match_id
        ));
        ?>
        <div class="app-container">
            <div class="card">
                <h2>🏆 <?php echo esc_html($match->match_name); ?></h2>
                <div class="list-item-meta" style="margin-bottom: 15px;">
                    <span>📅 <?php echo esc_html($match->match_date); ?></span>
                    <?php if ($match->venue): ?>
                        <span>📍 <?php echo esc_html($match->venue); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if ($match->description): ?>
                    <p style="line-height: 1.8; white-space: pre-wrap;"><?php echo esc_html($match->description); ?></p>
                <?php endif; ?>
            </div>
            
            <?php if ($participants): ?>
                <div class="card">
                    <h2>🏅 成績</h2>
                    <?php foreach ($participants as $participant): ?>
                        <a href="<?php echo home_url('/tt-app/player/' . $participant->player_id); ?>" class="list-item">
                            <div class="list-item-title"><?php echo esc_html($participant->name); ?></div>
                            <?php if ($participant->final_rank): ?>
                                <div class="list-item-meta">
                                    <span class="badge <?php 
                                        if ($participant->final_rank == 1) echo 'badge-gold';
                                        elseif ($participant->final_rank == 2) echo 'badge-silver';
                                        elseif ($participant->final_rank >= 3 && $participant->final_rank <= 4) echo 'badge-bronze';
                                    ?>">
                                        <?php 
                                        if ($participant->final_rank == 1) echo '🏆 優勝';
                                        elseif ($participant->final_rank == 2) echo '🥈 準優勝';
                                        elseif ($participant->final_rank >= 3 && $participant->final_rank <= 4) echo '🥉 ベスト4';
                                        elseif ($participant->final_rank >= 5 && $participant->final_rank <= 8) echo 'ベスト8';
                                        else echo $participant->final_rank . '位';
                                        ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($results): ?>
                <div class="card">
                    <h2>⚔️ 対戦結果</h2>
                    <?php foreach ($results as $result): ?>
                        <div class="vs-match">
                            <div class="vs-player <?php echo $result->winner_id == $result->player1_id ? 'vs-winner' : 'vs-loser'; ?>">
                                <a href="<?php echo home_url('/tt-app/player/' . $result->player1_id); ?>" style="text-decoration: none; color: inherit;">
                                    <div class="vs-player-name"><?php echo esc_html($result->player1_name); ?></div>
                                </a>
                            </div>
                            <div class="vs-score">
                                <?php echo intval($result->player1_games); ?> - <?php echo intval($result->player2_games); ?>
                            </div>
                            <div class="vs-player <?php echo $result->winner_id == $result->player2_id ? 'vs-winner' : 'vs-loser'; ?>">
                                <a href="<?php echo home_url('/tt-app/player/' . $result->player2_id); ?>" style="text-decoration: none; color: inherit;">
                                    <div class="vs-player-name"><?php echo esc_html($result->player2_name); ?></div>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * 404ページを出力
     */
    private function render_404() {
        ?>
        <div class="app-container">
            <div class="card">
                <div class="empty-state">
                    <div class="empty-state-icon">😕</div>
                    <h2>ページが見つかりません</h2>
                    <p style="margin-top: 10px;">
                        <a href="<?php echo home_url('/tt-app/'); ?>" style="color: #ff6b35; text-decoration: none;">ホームに戻る</a>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Ajax: 選手検索
     */
    public function ajax_search_players() {
        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        
        if (empty($query)) {
            wp_send_json_success(array());
            return;
        }
        
        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->tables['players']} 
             WHERE name LIKE %s OR name_kana LIKE %s 
             ORDER BY name 
             LIMIT 50",
            '%' . $this->wpdb->esc_like($query) . '%',
            '%' . $this->wpdb->esc_like($query) . '%'
        ));
        
        wp_send_json_success($results);
    }
    
    /**
     * Ajax: 試合検索
     */
    public function ajax_search_matches() {
        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        
        if (empty($query)) {
            wp_send_json_success(array());
            return;
        }
        
        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->tables['matches']} 
             WHERE match_name LIKE %s 
             ORDER BY match_date DESC 
             LIMIT 50",
            '%' . $this->wpdb->esc_like($query) . '%'
        ));
        
        wp_send_json_success($results);
    }
    
    /**
     * Ajax: 選手詳細取得
     */
    public function ajax_get_player() {
        $player_id = isset($_POST['player_id']) ? intval($_POST['player_id']) : 0;
        
        $player = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->tables['players']} WHERE player_id = %d",
            $player_id
        ));
        
        if ($player) {
            wp_send_json_success($player);
        } else {
            wp_send_json_error('選手が見つかりません');
        }
    }
    
    /**
     * Ajax: 試合詳細取得
     */
    public function ajax_get_match() {
        $match_id = isset($_POST['match_id']) ? intval($_POST['match_id']) : 0;
        
        $match = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->tables['matches']} WHERE match_id = %d",
            $match_id
        ));
        
        if ($match) {
            wp_send_json_success($match);
        } else {
            wp_send_json_error('試合が見つかりません');
        }
    }
}
