<?php
if (!defined('ABSPATH')) exit;
final class DSVS_Parser {
    private static function dom($html) {
        if (!class_exists('DOMDocument')) return false;
        $dom = new DOMDocument(); $old = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors(); libxml_use_internal_errors($old);
        return array($dom, new DOMXPath($dom));
    }
    private static function cells($xp, $tr) {
        $out = array();
        foreach ($xp->query('./th|./td', $tr) as $cell) {
            $value = preg_replace('/\s+/u', ' ', trim($cell->textContent));
            if ($value !== '') $out[] = $value;
        }
        return $out;
    }
    public static function standings($html) {
        $doc = self::dom($html); if (!$doc) return array(); list($dom, $xp) = $doc;
        foreach ($xp->query('//table') as $table) {
            $text = preg_replace('/\s+/u', ' ', $table->textContent);
            if (stripos($text, 'Mannschaft') === false || stripos($text, 'Partien') === false) continue;
            $rows = array();
            foreach ($xp->query('.//tr', $table) as $tr) {
                $c = self::cells($xp, $tr);
                if (count($c) >= 9 && preg_match('/^\d+$/', $c[0])) $rows[] = array_slice($c, 0, 9);
            }
            if ($rows) return $rows;
        }
        return array();
    }
    private static function parse_game_cells($c) {
        if (count($c) < 5 || !preg_match('/^\d+$/', $c[0])) return false;
        $date_index = false;
        foreach ($c as $i => $v) if (preg_match('/\d{2}\.\d{2}\.\d{4}/', $v)) { $date_index = $i; break; }
        if ($date_index === false) return false;
        $date_text = $c[$date_index];
        if (!preg_match('/(\d{2}\.\d{2}\.\d{4}).*?(\d{2}:\d{2})/u', $date_text, $m)) return false;
        $after = array_slice($c, $date_index + 1); $result_i = false;
        foreach ($after as $i => $v) if (preg_match('/^(?:\d+\s*:\s*\d+|:)$/', $v)) { $result_i = $i; break; }
        if ($result_i === false || $result_i < 1 || !isset($after[$result_i + 1])) return false;
        $dt = DateTime::createFromFormat('!d.m.Y H:i', $m[1] . ' ' . $m[2], wp_timezone());
        if (!$dt) return false;
        return array('matchday'=>(int)$c[0], 'date'=>$m[1], 'time'=>$m[2], 'timestamp'=>$dt->getTimestamp(),
            'home'=>$after[$result_i-1], 'result'=>preg_replace('/\s+/', '', $after[$result_i]), 'away'=>$after[$result_i+1]);
    }
    private static function table_games($html, $current_only) {
        $doc = self::dom($html); if (!$doc) return array(); list($dom, $xp) = $doc; $games = array();
        foreach ($xp->query('//table') as $table) {
            $text = preg_replace('/\s+/u', ' ', $table->textContent);
            $is_current = stripos($text, 'AKTUELLE ERGEBNISSE') !== false;
            if ($current_only !== $is_current) continue;
            foreach ($xp->query('.//tr', $table) as $tr) { $g = self::parse_game_cells(self::cells($xp, $tr)); if ($g) $games[] = $g; }
        }
        $unique = array(); foreach ($games as $g) $unique[$g['matchday'].'|'.$g['home'].'|'.$g['away']] = $g;
        return array_values($unique);
    }
    public static function current_games($html) { return self::table_games($html, true); }
    public static function schedule($html) { $g = self::table_games($html, false); usort($g, fn($a,$b)=>$a['timestamp']<=>$b['timestamp']); return $g; }
    public static function filter_team($games, $team = '') {
        $teams = $team !== '' ? array($team) : DSVS_TEAMS;
        return array_values(array_filter($games, function($g) use ($teams) {
            foreach($teams as $t) if (!strcasecmp($g['home'],$t) || !strcasecmp($g['away'],$t)) return true;
            return false;
        }));
    }
    public static function upcoming($games, $team = '', $limit = 4) {
        $games = self::filter_team($games, $team); $now = current_time('timestamp');
        $games = array_values(array_filter($games, fn($g)=>$g['timestamp'] >= $now && $g['result'] === ':'));
        return array_slice($games, 0, $limit);
    }
}
