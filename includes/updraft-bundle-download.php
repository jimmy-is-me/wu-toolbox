<?php
/**
 * UpdraftPlus 備份打包下載模組
 * 功能：
 * - 在 WumetaxToolkit 底下新增「備份檔案下載功能」子選單
 * - 掃描 wp-content/updraft 目錄
 * - 將「最新一組」UpdraftPlus 備份檔打包成 zip 下載
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WU_Updraft_Bundle_Download_Module {

    const ACTION_DOWNLOAD_LATEST = 'wu_upbd_download_latest';
    const NONCE_FIELD            = 'wu_upbd_nonce';
    const NONCE_ACTION           = 'wu_upbd_download_latest';

    public function __construct() {
        // 後台子選單
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ], 20 );
        // 後台表單處理
        add_action( 'admin_post_' . self::ACTION_DOWNLOAD_LATEST, [ $this, 'handle_download_latest' ] );
    }

    /**
     * 新增「備份檔案下載功能」子選單
     */
    public function add_admin_menu() {
        add_submenu_page(
            'wumetax-toolkit',
            'Updraft 備份打包下載',   // 頁面標題
            '備份檔案下載功能',       // 左側子選單文字（你可以改）
            'manage_options',
            'wu-updraft-bundle-download',
            [ $this, 'admin_page' ]
        );
    }

    /**
     * 子選單頁面：包一層外框再呼叫原本的 render_section()
     */
    public function admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        echo '<div class="wrap">';
        echo '<h1>UpdraftPlus 備份打包下載</h1>';
        $this->render_section();
        echo '</div>';
    }

    /**
     * 原本的區塊輸出（保留：方便未來要內嵌到其他頁）
     */
    public function render_section() {
        $dir    = $this->get_updraft_dir();
        $groups = $this->scan_backups();

        ?>
        <div class="card" style="max-width:900px;">
            <h2>UpdraftPlus 備份打包下載</h2>
            <p class="description">
                掃描 <code>wp-content/updraft</code> 目錄，將「最新一組」UpdraftPlus 備份檔（db / plugins / themes / uploads / others）打包成一個 zip 檔，直接下載到你的電腦或 NAS 開的瀏覽器。
            </p>

            <?php if ( ! $dir ) : ?>
                <p><strong>找不到目錄：</strong><code>wp-content/updraft</code>。請確認 UpdraftPlus 使用預設路徑，或修改此模組程式中的路徑取得邏輯。</p>
                <?php return; ?>
            <?php endif; ?>

            <?php if ( empty( $groups ) ) : ?>
                <p>目前在 <code><?php echo esc_html( $dir ); ?></code> 內沒有偵測到任何備份檔。</p>
            <?php else : ?>
                <p>備份目錄：<code><?php echo esc_html( $dir ); ?></code></p>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD ); ?>
                    <input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_DOWNLOAD_LATEST ); ?>">
                    <p>
                        <button type="submit" class="button button-primary">
                            下載「最新一組」Updraft 備份（zip）
                        </button>
                    </p>
                </form>

                <?php $this->render_groups_table( $groups ); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    protected function get_updraft_dir() {
        $dir = WP_CONTENT_DIR . '/updraft'; // UpdraftPlus 預設路徑
        return is_dir( $dir ) ? $dir : null;
    }

    /**
     * 掃描並依「組 key」分群。
     * key 由 UpdraftPlus 檔名中的 timestamp + hash 組成。
     *
     * @return array [ key => [ fileInfo, ... ], ... ]
     */
    protected function scan_backups() {
        $dir = $this->get_updraft_dir();
        if ( ! $dir ) {
            return [];
        }

        $pattern = trailingslashit( $dir ) . '*';
        $files   = glob( $pattern );
        $groups  = [];

        if ( ! $files ) {
            return [];
        }

        foreach ( $files as $file ) {
            if ( ! is_file( $file ) ) {
                continue;
            }

            $basename = basename( $file );

            // 依 UpdraftPlus 檔名慣例抓 key，例如：
            // backup_2025-11-27-1200_xxxxxx-uploads.zip
            if ( preg_match( '/backup_([0-9\-]+-[0-9]{4})_([0-9a-f]+)/', $basename, $m ) ) {
                $key = $m[1] . '_' . $m[2];
            } else {
                // 不符合格式的就丟在 other 群
                $key = 'other';
            }

            $groups[ $key ][] = [
                'path'  => $file,
                'name'  => $basename,
                'size'  => @filesize( $file ),
                'mtime' => @filemtime( $file ),
            ];
        }

        return $groups;
    }

    protected function render_groups_table( array $groups ) {
        ?>
        <h3>偵測到的備份組</h3>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>組 key</th>
                    <th>檔案數</th>
                    <th>總大小</th>
                    <th>最近修改時間</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $groups as $key => $files ) :
                    $sizes   = array_column( $files, 'size' );
                    $mtimes  = array_column( $files, 'mtime' );
                    $total   = array_sum( $sizes );
                    $latest  = $mtimes ? max( $mtimes ) : 0;
                    ?>
                    <tr>
                        <td><code><?php echo esc_html( $key ); ?></code></td>
                        <td><?php echo count( $files ); ?></td>
                        <td><?php echo size_format( $total ); ?></td>
                        <td><?php echo $latest ? esc_html( date_i18n( 'Y-m-d H:i:s', $latest ) ) : '-'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * admin-post handler：打包最新一組備份並輸出 zip。
     */
    public function handle_download_latest() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have permission.', 'wu-toolbox' ) );
        }

        check_admin_referer( self::NONCE_ACTION, self::NONCE_FIELD );

        $groups = $this->scan_backups();
        if ( empty( $groups ) ) {
            wp_die( '沒有可用的備份檔。' );
        }

        // 依 key 排序後取最後一個 key 當「最新」
        ksort( $groups );
        $latest_key = array_key_last( $groups );
        $files      = $groups[ $latest_key ];

        if ( empty( $files ) ) {
            wp_die( '最新那一組內沒有檔案。' );
        }

        if ( ! class_exists( 'ZipArchive' ) ) {
            wp_die( '伺服器未啟用 ZipArchive，無法建立 zip 檔。' );
        }

        // 暫存 zip 檔
        $upload_dir = wp_get_upload_dir();
        $tmp_dir    = is_dir( $upload_dir['basedir'] ) ? $upload_dir['basedir'] : sys_get_temp_dir();

        $zip_path = tempnam( $tmp_dir, 'wu_upbd_' );
        $zip      = new ZipArchive();

        if ( $zip->open( $zip_path, ZipArchive::OVERWRITE ) !== true ) {
            wp_die( '無法建立 zip 檔。' );
        }

        foreach ( $files as $f ) {
            if ( is_readable( $f['path'] ) ) {
                $zip->addFile( $f['path'], basename( $f['path'] ) );
            }
        }

        $zip->close();

        $download_name = sprintf( 'updraft-bundle-%s.zip', $latest_key );

        // 送出下載（避免任何多餘輸出）
        nocache_headers();
        header( 'Content-Type: application/zip' );
        header( 'Content-Disposition: attachment; filename="' . $download_name . '"' );
        header( 'Content-Length: ' . filesize( $zip_path ) );

        readfile( $zip_path );
        @unlink( $zip_path );
        exit;
    }
}

// 初始化模組（跟 404 模組一樣，在檔案最後 new 一次）
new WU_Updraft_Bundle_Download_Module();
