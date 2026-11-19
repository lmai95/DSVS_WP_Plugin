<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class DSVS_Settings {
    const OPTION_NAME = 'dsvs_settings';
    public static function init() { add_action('admin_menu',array(__CLASS__,'add_page')); add_action('admin_init',array(__CLASS__,'register')); }
    public static function defaults() { return array('club_name'=>'TSV Böbingen','highlighted_teams'=>"TSV Böbingen I\nTSV Böbingen II",'season'=>'2026/2027'); }
    public static function all() { return wp_parse_args((array)get_option(self::OPTION_NAME,array()),self::defaults()); }
    public static function club_name() { $s=self::all(); return $s['club_name']; }
    public static function season() { $s=self::all(); return preg_match('/^\d{4}\/\d{4}$/',$s['season'])?$s['season']:'2026/2027'; }
    public static function highlighted_teams() { $s=self::all(); $lines=preg_split('/\R/u',$s['highlighted_teams']); return array_values(array_filter(array_map('trim',$lines))); }
    public static function is_highlighted_team($team_name) { foreach(self::highlighted_teams() as $team){if(0===strcasecmp(trim($team_name),$team)){return true;}} return false; }
    public static function register() { register_setting('dsvs_settings_group',self::OPTION_NAME,array('sanitize_callback'=>array(__CLASS__,'sanitize'),'default'=>self::defaults())); }
    public static function sanitize($input) { $defaults=self::defaults(); $output=array(); $output['club_name']=sanitize_text_field($input['club_name']??$defaults['club_name']); $season=sanitize_text_field($input['season']??$defaults['season']); $output['season']=preg_match('/^\d{4}\/\d{4}$/',$season)?$season:$defaults['season']; $teams=preg_split('/\R/u',(string)($input['highlighted_teams']??'')); $teams=array_values(array_unique(array_filter(array_map('sanitize_text_field',$teams)))); $output['highlighted_teams']=implode("\n",$teams); return $output; }
    public static function add_page() { add_options_page('DSVS Liga','DSVS Liga','manage_options','dsvs-settings',array(__CLASS__,'render')); }
    public static function render() { if(!current_user_can('manage_options')){return;} $s=self::all(); ?>
        <div class="wrap"><h1>DSVS Liga Einstellungen</h1><p>Hier wird festgelegt, welcher Verein und welche Mannschaften in Tabellen und Spielplänen hervorgehoben werden.</p>
        <form method="post" action="options.php"><?php settings_fields('dsvs_settings_group'); ?>
        <table class="form-table" role="presentation">
        <tr><th scope="row"><label for="dsvs-club">Vereinsname</label></th><td><input id="dsvs-club" class="regular-text" name="<?php echo esc_attr(self::OPTION_NAME); ?>[club_name]" value="<?php echo esc_attr($s['club_name']); ?>"><p class="description">Dient zur Dokumentation und für zukünftige Vereinsansichten.</p></td></tr>
        <tr><th scope="row"><label for="dsvs-teams">Mannschaften hervorheben</label></th><td><textarea id="dsvs-teams" class="large-text code" rows="7" name="<?php echo esc_attr(self::OPTION_NAME); ?>[highlighted_teams]"><?php echo esc_textarea($s['highlighted_teams']); ?></textarea><p class="description">Eine Mannschaft pro Zeile. Schreibweise muss der DSVS-Seite entsprechen.</p></td></tr>
        <tr><th scope="row"><label for="dsvs-season">Saison</label></th><td><input id="dsvs-season" class="regular-text" name="<?php echo esc_attr(self::OPTION_NAME); ?>[season]" value="<?php echo esc_attr($s['season']); ?>" pattern="[0-9]{4}/[0-9]{4}"><p class="description">Format: 2026/2027. Die Liga-IDs bleiben saisonabhängig in der Liga-Konfiguration hinterlegt.</p></td></tr>
        </table><?php submit_button(); ?></form>
        <hr><h2>Bestehende Shortcodes</h2><code>[dsv_tabelle liga="C2"]</code><br><code>[dsv_aktuelle_spiele liga="C2"]</code><br><code>[dsv_kommende_spiele liga="C2" anzahl="6"]</code><br><code>[dsv_rangliste liga="C2" anzahl="20" eigene="true"]</code></div><?php }
}
