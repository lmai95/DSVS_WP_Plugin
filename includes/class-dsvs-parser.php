<?php
if (!defined('ABSPATH')) exit;
final class DSVS_Parser {
    public static function standings($html) {
        if (!class_exists('DOMDocument')) return array();
        $dom=new DOMDocument(); $prev=libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html); libxml_clear_errors(); libxml_use_internal_errors($prev);
        $xp=new DOMXPath($dom);
        foreach($xp->query('//table') as $table) {
            $txt=preg_replace('/\s+/u',' ',trim($table->textContent));
            if (stripos($txt,'Platz')===false || stripos($txt,'Mannschaft')===false || stripos($txt,'Partien')===false) continue;
            $rows=array();
            foreach($xp->query('.//tr',$table) as $tr) {
                $cells=array(); foreach($xp->query('./th|./td',$tr) as $td) $cells[]=trim(preg_replace('/\s+/u',' ',$td->textContent));
                // Expected standings row: rank, team, S, G, U, V, points, diff, legs.
                if(count($cells)>=9 && ctype_digit($cells[0])) $rows[]=array_slice($cells,0,9);
            }
            if($rows) return $rows;
        }
        return array();
    }
}
