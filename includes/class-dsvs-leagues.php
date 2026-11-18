<?php
if (!defined('ABSPATH')) exit;
final class DSVS_Leagues {
    private static $leagues = array(
        'OL' => array('name'=>'Oberliga','id'=>86,'table'=>true),
        'A' => array('name'=>'A-Liga','id'=>87,'table'=>true),
        'B' => array('name'=>'B-Liga','id'=>88,'table'=>true),
        'C1' => array('name'=>'C1-Liga','id'=>90,'table'=>true),
        'C2' => array('name'=>'C2-Liga','id'=>92,'table'=>true),
        'LC' => array('name'=>'Liga Cup','id'=>93,'table'=>false),
        'CLVH' => array('name'=>'C-Liga Vergleichskampf (Hinrunde)','id'=>96,'table'=>false),
        'CLVR' => array('name'=>'C-Liga Vergleichskampf (Rückrunde)','id'=>97,'table'=>false),
    );
    public static function all() { $out=array(); foreach(self::$leagues as $key=>$v){$v['key']=$key;$v['url']=self::url($v['id']);$out[$key]=$v;} return $out; }
    public static function get($key) {
        $key = strtoupper(trim((string)$key));
        $aliases = array('AL'=>'A','OBERLIGA'=>'OL','A-LIGA'=>'A','B-LIGA'=>'B','C1-LIGA'=>'C1','C2-LIGA'=>'C2','LIGA CUP'=>'LC');
        if (isset($aliases[$key])) $key=$aliases[$key];
        $all=self::all(); return isset($all[$key]) ? $all[$key] : false;
    }
    private static function url($id) { return 'https://dsv-schwaben.de/sb_spielplan.php?p=209--2026/2027-' . absint($id); }
}
