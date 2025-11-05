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
        
        // 参考動画を取得
        $videos = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->tables['player_videos']}
             WHERE player_id = %d
             ORDER BY display_order ASC, video_id ASC",
            $player_id
        ));
        
        // この選手が参加した試合と対戦結果を取得
        $matches_with_results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT DISTINCT m.match_id, m.match_name, m.match_date, mp.final_rank
             FROM {$this->tables['matches']} m
             LEFT JOIN {$this->tables['match_participants']} mp 
                ON m.match_id = mp.match_id AND mp.player_id = %d
             INNER JOIN {$this->tables['match_results']} r 
                ON m.match_id = r.match_id 
                AND (r.player1_id = %d OR r.player2_id = %d)
             ORDER BY m.match_date DESC
             LIMIT 20",
            $player_id,
            $player_id,
            $player_id
        ));
        
        // 各試合の対戦結果を取得してグループ化
        $matches_data = array();
        foreach ($matches_with_results as $match) {
            $results = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT r.*, p1.name as player1_name, p2.name as player2_name
                 FROM {$this->tables['match_results']} r
                 INNER JOIN {$this->tables['players']} p1 ON r.player1_id = p1.player_id
                 INNER JOIN {$this->tables['players']} p2 ON r.player2_id = p2.player_id
                 WHERE r.match_id = %d AND (r.player1_id = %d OR r.player2_id = %d)
                 ORDER BY r.result_id",
                $match->match_id,
                $player_id,
                $player_id
            ));
            
            // ラウンド情報で並び替え
            $round_priority = array(
                '決勝' => 1,
                '準決勝' => 2,
                '3位決定戦' => 3,
                '準々決勝' => 4,
                'ベスト8' => 5,
                'ベスト16' => 6,
                'ベスト32' => 7,
                '1回戦' => 8,
                '2回戦' => 9,
                '3回戦' => 10,
                '予選' => 999,
            );
            
            usort($results, function($a, $b) use ($round_priority) {
                $priority_a = 500;
                $priority_b = 500;
                
                if (!empty($a->round_info)) {
                    $matched_length_a = 0;
                    foreach ($round_priority as $key => $priority) {
                        if (strpos($a->round_info, $key) !== false) {
                            if (strlen($key) > $matched_length_a) {
                                $priority_a = $priority;
                                $matched_length_a = strlen($key);
                            }
                        }
                    }
                }
                
                if (!empty($b->round_info)) {
                    $matched_length_b = 0;
                    foreach ($round_priority as $key => $priority) {
                        if (strpos($b->round_info, $key) !== false) {
                            if (strlen($key) > $matched_length_b) {
                                $priority_b = $priority;
                                $matched_length_b = strlen($key);
                            }
                        }
                    }
                }
                
                return $priority_a - $priority_b;
            });
            
            $matches_data[] = array(
                'match' => $match,
                'results' => $results
            );
        }
        
        $tactics_labels = array(
            'right_pen' => '右ペン',
            'left_pen' => '左ペン',
            'right_shake' => '右シェーク',
            'left_shake' => '左シェーク',
            'other' => 'その他'
        );
        
        $gender_labels = array(
            'male' => '男性',
            'female' => '女性',
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
                        <?php if ($player->gender): ?>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $player->gender == 'male' ? '👨' : ($player->gender == 'female' ? '👩' : '👤'); ?></div>
                                <div class="stat-label"><?php echo $gender_labels[$player->gender] ?? $player->gender; ?></div>
                            </div>
                        <?php endif; ?>
                        
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
                
                <?php if ($player->profile_text || $player->tactics_detail): ?>
                    <h2>📝 プロフィール</h2>
                    <?php if ($player->profile_text): ?>
                        <p style="line-height: 1.8; white-space: pre-wrap; margin-bottom: 15px;"><?php echo esc_html($player->profile_text); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($player->tactics_detail): ?>
                        <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-top: 10px;">
                            <strong style="color: #ff6b35;">戦術詳細:</strong>
                            <p style="margin: 8px 0 0 0; line-height: 1.6; white-space: pre-wrap;"><?php echo esc_html($player->tactics_detail); ?></p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <?php if ($videos): ?>
                <div class="card">
                    <h2>🎥 参考動画</h2>
                    <div id="video-list" style="display: none;">
                        <?php foreach ($videos as $video): ?>
                            <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #e0e0e0;">
                                <?php if ($video->video_title): ?>
                                    <h3 style="font-size: 16px; margin: 0 0 8px 0; color: #333;"><?php echo esc_html($video->video_title); ?></h3>
                                <?php endif; ?>
                                
                                <?php if ($video->video_description): ?>
                                    <p style="font-size: 14px; color: #666; margin: 0 0 10px 0; line-height: 1.6;"><?php echo esc_html($video->video_description); ?></p>
                                <?php endif; ?>
                                
                                <?php 
                                // YouTube URLの場合は埋め込み
                                $video_url = $video->video_url;
                                if (preg_match('/youtube\.com\/watch\?v=([^&]+)/', $video_url, $matches) || 
                                    preg_match('/youtu\.be\/([^?]+)/', $video_url, $matches)) {
                                    $video_id = $matches[1];
                                    ?>
                                    <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 8px;">
                                        <iframe src="https://www.youtube.com/embed/<?php echo esc_attr($video_id); ?>" 
                                                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none;" 
                                                allowfullscreen>
                                        </iframe>
                                    </div>
                                <?php } else { ?>
                                    <a href="<?php echo esc_url($video_url); ?>" target="_blank" style="color: #ff6b35; text-decoration: none;">
                                        🔗 動画を見る
                                    </a>
                                <?php } ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button id="toggle-videos" onclick="toggleVideos()" style="width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                        参考動画を表示 (<?php echo count($videos); ?>件)
                    </button>
                    
                    <script>
                    function toggleVideos() {
                        var videoList = document.getElementById('video-list');
                        var btn = document.getElementById('toggle-videos');
                        if (videoList.style.display === 'none') {
                            videoList.style.display = 'block';
                            btn.textContent = '参考動画を隠す';
                            btn.style.background = '#999';
                        } else {
                            videoList.style.display = 'none';
                            btn.textContent = '参考動画を表示 (<?php echo count($videos); ?>件)';
                            btn.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                        }
                    }
                    </script>
                </div>
            <?php endif; ?>
            
            <?php if ($matches_data): ?>
                <div class="card">
                    <h2>🏆 試合別対戦結果</h2>
                    <?php foreach ($matches_data as $index => $match_data): 
                        $match = $match_data['match'];
                        $results = $match_data['results'];
                        $accordion_id = 'match-' . $match->match_id;
                    ?>
                        <div style="margin-bottom: 15px;">
                            <!-- 試合名ヘッダー（クリック可能） -->
                            <div onclick="toggleMatch('<?php echo $accordion_id; ?>')" 
                                 style="background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%); color: white; padding: 12px 15px; border-radius: 8px; cursor: pointer; position: relative;">
                                <div style="font-size: 16px; font-weight: 600; margin-bottom: 5px; padding-right: 30px;">
                                    📋 <?php echo esc_html($match->match_name); ?>
                                </div>
                                <div style="font-size: 13px; opacity: 0.9;">
                                    📅 <?php echo esc_html($match->match_date); ?>
                                    <?php if ($match->final_rank): ?>
                                        <?php 
                                        $rank_text = '';
                                        if ($match->final_rank == 1) $rank_text = '🏆 優勝';
                                        elseif ($match->final_rank == 2) $rank_text = '🥈 準優勝';
                                        elseif ($match->final_rank >= 3 && $match->final_rank <= 4) $rank_text = '🥉 ベスト4';
                                        elseif ($match->final_rank >= 5 && $match->final_rank <= 8) $rank_text = 'ベスト8';
                                        elseif ($match->final_rank >= 9 && $match->final_rank <= 16) $rank_text = 'ベスト16';
                                        else $rank_text = $match->final_rank . '位';
                                        ?>
                                        - <?php echo $rank_text; ?>
                                    <?php endif; ?>
                                </div>
                                <!-- 開閉アイコン -->
                                <div id="<?php echo $accordion_id; ?>-icon" style="position: absolute; top: 50%; right: 15px; transform: translateY(-50%); font-size: 20px; transition: transform 0.3s;">
                                    ▼
                                </div>
                            </div>
                            
                            <!-- 対戦結果リスト（初期非表示） -->
                            <div id="<?php echo $accordion_id; ?>" style="display: none; padding: 15px; background: #f8f9fa; border-radius: 0 0 8px 8px; margin-top: -8px;">
                                <?php if ($results): ?>
                                    <?php foreach ($results as $result): ?>
                                        <!-- round_info表示 -->
                                        <?php if ($result->round_info): ?>
                                            <div style="background: white; color: #666; font-size: 12px; font-weight: 600; padding: 6px 10px; margin-bottom: 8px; border-radius: 4px; text-align: center;">
                                                <?php echo esc_html($result->round_info); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- 対戦カード -->
                                        <div class="vs-match" style="margin-bottom: 12px;">
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
                                    
                                    <!-- 試合詳細へのリンク -->
                                    <div style="text-align: center; margin-top: 15px;">
                                        <a href="<?php echo home_url('/tt-app/match/' . $match->match_id); ?>" 
                                           style="display: inline-block; padding: 8px 16px; background: white; color: #ff6b35; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 600; border: 2px solid #ff6b35;">
                                            試合詳細を見る →
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <p style="text-align: center; color: #999; font-size: 14px; margin: 10px 0;">対戦結果がありません</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <script>
                function toggleMatch(matchId) {
                    var content = document.getElementById(matchId);
                    var icon = document.getElementById(matchId + '-icon');
                    
                    if (content.style.display === 'none') {
                        content.style.display = 'block';
                        icon.style.transform = 'translateY(-50%) rotate(180deg)';
                    } else {
                        content.style.display = 'none';
                        icon.style.transform = 'translateY(-50%) rotate(0deg)';
                    }
                }
                </script>
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
        
        // ラウンド情報による優先度を定義して並び替え
        // 注意：長いキーワードから順に並べる（「決勝トーナメント」と「決勝」の混同を防ぐため）
        $round_priority = array(
            '決勝トーナメント 決勝' => 1,
            '決勝' => 1,
            '準決勝' => 2,
            '3位決定戦' => 3,
            '準々決勝' => 4,
            'ベスト8' => 5,
            'ベスト16' => 6,
            'ベスト32' => 7,
            'ベスト64' => 8,
            '1回戦' => 9,
            '2回戦' => 10,
            '3回戦' => 11,
            '4回戦' => 12,
            '5回戦' => 13,
            '予選' => 999,  // 予選は最後
        );
        
        usort($results, function($a, $b) use ($round_priority) {
            $priority_a = 500;  // デフォルトは中間値
            $priority_b = 500;
            
            // round_infoから優先度を取得（より長いマッチを優先）
            if (!empty($a->round_info)) {
                $matched_length_a = 0;
                foreach ($round_priority as $key => $priority) {
                    if (strpos($a->round_info, $key) !== false) {
                        // より長いキーワードにマッチした場合のみ更新
                        if (strlen($key) > $matched_length_a) {
                            $priority_a = $priority;
                            $matched_length_a = strlen($key);
                        }
                    }
                }
            }
            
            if (!empty($b->round_info)) {
                $matched_length_b = 0;
                foreach ($round_priority as $key => $priority) {
                    if (strpos($b->round_info, $key) !== false) {
                        // より長いキーワードにマッチした場合のみ更新
                        if (strlen($key) > $matched_length_b) {
                            $priority_b = $priority;
                            $matched_length_b = strlen($key);
                        }
                    }
                }
            }
            
            return $priority_a - $priority_b;
        });
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
            
            <?php if ($ranked_participants || $other_participants): ?>
                <div class="card">
                    <h2>🏅 成績</h2>
                    
                    <!-- ベスト16まで表示 -->
                    <div id="ranked-participants">
                        <?php foreach ($ranked_participants as $participant): ?>
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
                                            elseif ($participant->final_rank >= 9 && $participant->final_rank <= 16) echo 'ベスト16';
                                            ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- その他の選手（非表示） -->
                    <?php if ($other_participants): ?>
                        <div id="other-participants" style="display: none; border-top: 1px solid #e0e0e0; margin-top: 10px; padding-top: 10px;">
                            <?php foreach ($other_participants as $participant): ?>
                                <a href="<?php echo home_url('/tt-app/player/' . $participant->player_id); ?>" class="list-item">
                                    <div class="list-item-title"><?php echo esc_html($participant->name); ?></div>
                                    <div class="list-item-meta">
                                        <span class="badge">
                                            <?php 
                                            if ($participant->final_rank == 99) echo '予選敗退';
                                            elseif ($participant->final_rank) echo $participant->final_rank . '位';
                                            else echo '出場';
                                            ?>
                                        </span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        
                        <button id="toggle-participants" onclick="toggleParticipants()" style="width: 100%; padding: 12px; margin-top: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                            出場選手を表示 (<?php echo count($other_participants); ?>名)
                        </button>
                        
                        <script>
                        function toggleParticipants() {
                            var otherList = document.getElementById('other-participants');
                            var btn = document.getElementById('toggle-participants');
                            if (otherList.style.display === 'none') {
                                otherList.style.display = 'block';
                                btn.textContent = '出場選手を隠す';
                                btn.style.background = '#999';
                            } else {
                                otherList.style.display = 'none';
                                btn.textContent = '出場選手を表示 (<?php echo count($other_participants); ?>名)';
                                btn.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                            }
                        }
                        </script>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($results): 
                $default_display_count = 10;
                $total_results = count($results);
            ?>
                <div class="card">
                    <h2>⚔️ 対戦結果</h2>
                    
                    <div id="results-container">
                        <?php 
                        foreach ($results as $index => $result): 
                            $is_hidden = ($index >= $default_display_count);
                        ?>
                            <div class="vs-match-wrapper <?php echo $is_hidden ? 'hidden-result' : ''; ?>" style="<?php echo $is_hidden ? 'display: none;' : ''; ?>">
                                <?php if ($result->round_info): ?>
                                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-size: 12px; font-weight: 600; padding: 6px 10px; margin-bottom: 8px; border-radius: 6px; text-align: center;">
                                        <?php echo esc_html($result->round_info); ?>
                                    </div>
                                <?php endif; ?>
                                
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
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($total_results > $default_display_count): ?>
                        <button id="toggle-results" onclick="toggleResults()" style="width: 100%; padding: 12px; margin-top: 15px; background: #ff6b35; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                            すべての試合を見る (残り<?php echo $total_results - $default_display_count; ?>試合)
                        </button>
                        
                        <script>
                        function toggleResults() {
                            var hiddenResults = document.querySelectorAll('.hidden-result');
                            var btn = document.getElementById('toggle-results');
                            var isShowingAll = btn.getAttribute('data-showing-all') === 'true';
                            
                            if (!isShowingAll) {
                                hiddenResults.forEach(function(result) {
                                    result.style.display = 'block';
                                });
                                btn.textContent = '試合を折りたたむ';
                                btn.setAttribute('data-showing-all', 'true');
                                btn.style.background = '#999';
                            } else {
                                hiddenResults.forEach(function(result) {
                                    result.style.display = 'none';
                                });
                                btn.textContent = 'すべての試合を見る (残り<?php echo $total_results - $default_display_count; ?>試合)';
                                btn.setAttribute('data-showing-all', 'false');
                                btn.style.background = '#ff6b35';
                                
                                // 対戦結果セクションまでスクロール
                                document.getElementById('results-container').scrollIntoView({ behavior: 'smooth', block: 'start' });
                            }
                        }
                        </script>
                    <?php endif; ?>
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
