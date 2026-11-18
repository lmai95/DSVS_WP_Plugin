<?php
if (!defined('ABSPATH')) exit;
final class DSVS_Parser {
    private static function dom($html) {
        if (!class_exists('DOMDocument')) return false;
        $dom=new DOMDocument(); $old=libxml_use_internal_errors(true); $dom->loadHTML('<?xml encoding="UTF-8">'.$html); libxml_clear_errors(); libxml_use_internal_errors($old);
        return array($dom,new DOMXPath($dom));
    }
    private static function cells($xp,$tr) {
        $out=array(); foreach($xp->query('./th|./td',$tr) as $cell){$v=preg_replace('/\s+/u',' ',trim($cell->textContent)); if($v!=='')$out[]=$v;} return $out;
    }
    public static function standings($html) {
        $doc=self::dom($html); if(!$doc)return array(); list($dom,$xp)=$doc;
        foreach($xp->query('//table') as $table){$text=preg_replace('/\s+/u',' ',$table->textContent); if(stripos($text,'Mannschaft')===false||stripos($text,'Partien')===false)continue;
            $rows=array(); foreach($xp->query('.//tr',$table) as $tr){$c=self::cells($xp,$tr); if(count($c)>=9&&preg_match('/^\d+$/',$c[0]))$rows[]=array_slice($c,0,9);} if($rows)return $rows;}
        return array();
    }
    private static function game($c) {
        if(count($c)<5||!preg_match('/^\d+$/',$c[0]))return false; $di=false;
        foreach($c as $i=>$v){if(preg_match('/\d{2}\.\d{2}\.\d{4}/',$v)){$di=$i;break;}} if($di===false)return false;
        if(!preg_match('/(\d{2}\.\d{2}\.\d{4}).*?(\d{2}:\d{2})/u',$c[$di],$m))return false;
        $after=array_slice($c,$di+1); $ri=false; foreach($after as $i=>$v){if(preg_match('/^(?:\d+\s*:\s*\d+|:)$/',$v)){$ri=$i;break;}}
        if($ri===false||$ri<1||!isset($after[$ri+1]))return false; $dt=DateTime::createFromFormat('!d.m.Y H:i',$m[1].' '.$m[2],wp_timezone()); if(!$dt)return false;
        return array('matchday'=>(int)$c[0],'date'=>$m[1],'time'=>$m[2],'timestamp'=>$dt->getTimestamp(),'home'=>$after[$ri-1],'result'=>preg_replace('/\s+/','',''.$after[$ri]),'away'=>$after[$ri+1]);
    }
    private static function games($html,$current) {
        $doc=self::dom($html); if(!$doc)return array(); list($dom,$xp)=$doc; $games=array();
        foreach($xp->query('//table') as $table){$text=preg_replace('/\s+/u',' ',$table->textContent); $is_current=stripos($text,'AKTUELLE ERGEBNISSE')!==false; if($current!==$is_current)continue;
            foreach($xp->query('.//tr',$table) as $tr){$g=self::game(self::cells($xp,$tr));if($g)$games[]=$g;}}
        $unique=array(); foreach($games as $g)$unique[$g['matchday'].'|'.$g['date'].'|'.$g['home'].'|'.$g['away']]=$g; return array_values($unique);
    }
    public static function current_games($html){return self::games($html,true);}
    public static function schedule($html){$g=self::games($html,false);usort($g,array(__CLASS__,'sort_games'));return $g;}
    public static function sort_games($a,$b){if($a['timestamp']===$b['timestamp'])return strcmp($a['home'],$b['home']);return $a['timestamp']<$b['timestamp']?-1:1;}
    public static function is_owned($name){foreach(DSVS_TEAMS as $team)if(!strcasecmp(trim($name),trim($team)))return true;return false;}
    public static function filter_team($games,$team=''){$teams=$team!==''?array($team):DSVS_TEAMS;return array_values(array_filter($games,function($g)use($teams){foreach($teams as $t)if(!strcasecmp($g['home'],$t)||!strcasecmp($g['away'],$t))return true;return false;}));}
    public static function upcoming($games,$team='',$limit=4,$default_owned=false){if($team!==''||$default_owned)$games=self::filter_team($games,$team);$now=current_time('timestamp');$games=array_values(array_filter($games,function($g)use($now){return $g['timestamp']>=$now&&$g['result']===':'&&strcasecmp($g['home'],'SPIELFREI')&&strcasecmp($g['away'],'SPIELFREI')&&strcasecmp($g['home'],'FREILOS')&&strcasecmp($g['away'],'FREILOS');}));return array_slice($games,0,$limit);}
}
