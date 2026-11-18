<?php
/**
 * Plugin Name: DSVS WP Plugin
 * Description: Zeigt die C2-Ligatabelle des DSV Schwaben per Shortcode an.
 * Version: 0.1.0
 * License: GPL-2.0-or-later
 */
if (!defined('ABSPATH')) exit;

define('DSVS_VERSION','0.1.0');
define('DSVS_URL','https://dsv-schwaben.de/sb_spielplan.php?p=209--2026/2027-92');
define('DSVS_TEAM','TSV Böbingen II');
define('DSVS_CACHE_SECONDS',15 * MINUTE_IN_SECONDS);
define('DSVS_PLUGIN_DIR',plugin_dir_path(__FILE__));
define('DSVS_PLUGIN_URL',plugin_dir_url(__FILE__));

require_once DSVS_PLUGIN_DIR.'includes/class-dsvs-fetcher.php';
require_once DSVS_PLUGIN_DIR.'includes/class-dsvs-parser.php';

function dsvs_enqueue_assets() { wp_enqueue_style('dsvs-style', DSVS_PLUGIN_URL.'assets/css/dsvs.css', array(), DSVS_VERSION); }
function dsvs_table_shortcode() {
    dsvs_enqueue_assets();
    $html=DSVS_Fetcher::get(DSVS_URL);
    if (is_wp_error($html)) return '<p class="dsvs-error">Ligatabelle konnte momentan nicht geladen werden.</p>';
    $rows=DSVS_Parser::standings($html);
    if (!$rows) return '<p class="dsvs-error">Ligatabelle wurde auf der DSVS-Seite nicht gefunden.</p>';
    ob_start(); ?>
    <div class="dsvs-table-wrap"><table class="dsvs-table"><thead><tr><th>Platz</th><th>Mannschaft</th><th>S</th><th>G</th><th>U</th><th>V</th><th>Pkt.</th><th>Diff.</th><th>Partien</th></tr></thead><tbody>
    <?php foreach ($rows as $r): $own=!strcasecmp($r[1],DSVS_TEAM); ?>
      <tr<?php echo $own?' class="dsvs-own-team"':''; ?>><?php foreach($r as $i=>$v): ?><td<?php echo $i===1?' class="dsvs-team"':''; ?>><?php echo esc_html($v); ?></td><?php endforeach; ?></tr>
    <?php endforeach; ?>
    </tbody></table></div>
    <?php return ob_get_clean();
}
add_shortcode('dsv_tabelle','dsvs_table_shortcode');
