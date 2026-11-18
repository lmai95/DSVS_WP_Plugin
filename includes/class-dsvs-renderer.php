<?php
if (!defined('ABSPATH')) exit;
final class DSVS_Renderer {
    public static function owned($team) { foreach(DSVS_TEAMS as $t) if(!strcasecmp($team,$t)) return true; return false; }
    public static function error() { return '<p class="dsvs-error">Die DSVS-Daten konnten momentan nicht geladen werden.</p>'; }
    public static function standings($rows) {
        ob_start(); ?><div class="dsvs-table-wrap"><table class="dsvs-table dsvs-standings"><thead><tr>
        <?php foreach(array('Platz','Mannschaft','S','G','U','V','Pkt.','Diff.','Partien') as $h): ?><th><?php echo esc_html($h); ?></th><?php endforeach; ?>
        </tr></thead><tbody><?php foreach($rows as $r): $own=self::owned($r[1]); ?><tr<?php echo $own?' class="dsvs-own-team"':''; ?>>
        <?php foreach($r as $i=>$v): ?><td<?php echo $i===1?' class="dsvs-team"':''; ?>><?php echo esc_html($v); ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div><?php return ob_get_clean();
    }
    public static function games($games, $title, $id = '') {
        ob_start(); ?><section<?php echo $id?' id="'.esc_attr($id).'"':''; ?> class="dsvs-games"><h3><?php echo esc_html($title); ?></h3>
        <?php if(!$games): ?><p>Keine passenden Spiele gefunden.</p><?php else: ?><div class="dsvs-table-wrap"><table class="dsvs-table"><thead><tr><th>ST</th><th>Termin</th><th>Heim</th><th>Ergebnis</th><th>Gast</th></tr></thead><tbody>
        <?php foreach($games as $g): $own=self::owned($g['home'])||self::owned($g['away']); ?><tr<?php echo $own?' class="dsvs-own-team"':''; ?>><td><?php echo esc_html($g['matchday']); ?></td><td><?php echo esc_html($g['date'].' '.$g['time']); ?></td><td class="dsvs-team"><?php echo esc_html($g['home']); ?></td><td class="dsvs-result"><?php echo esc_html($g['result']); ?></td><td class="dsvs-team"><?php echo esc_html($g['away']); ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
        <small class="dsvs-updated">Zuletzt geprüft: <?php echo esc_html(current_time('H:i:s')); ?> Uhr</small></section><?php return ob_get_clean();
    }
}
