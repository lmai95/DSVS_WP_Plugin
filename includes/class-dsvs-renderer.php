<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Generates the small amount of HTML needed by the shortcodes. */
final class DSVS_Renderer {
    public static function error() {
        return '<p class="dsvs-error">DSVS-Daten momentan nicht verfügbar.</p>';
    }

    public static function standings( $standings, $league ) {
        if ( empty( $standings ) ) {
            return '<p class="dsvs-notice">Für diese Liga ist keine Tabelle verfügbar.</p>';
        }

        ob_start();
        ?>
        <section class="dsvs-block">
            <?php self::render_heading( $league['name'], 'Tabelle' ); ?>
            <div class="dsvs-table-wrap">
                <table class="dsvs-table">
                    <thead>
                        <tr>
                            <?php foreach ( array( 'Platz', 'Mannschaft', 'S', 'G', 'U', 'V', 'Pkt.', 'Diff.', 'Partien' ) as $heading ) : ?>
                                <th><?php echo esc_html( $heading ); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $standings as $row ) : ?>
                            <tr<?php echo DSVS_Parser::is_boebingen_team( $row[1] ) ? ' class="dsvs-own-team"' : ''; ?>>
                                <?php foreach ( $row as $cell ) : ?>
                                    <td><?php echo esc_html( $cell ); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    public static function games( $games, $league, $subtitle, $element_id = '', $enable_live_updates = false ) {
        ob_start();
        ?>
        <section
            <?php echo $element_id ? 'id="' . esc_attr( $element_id ) . '"' : ''; ?>
            class="dsvs-block dsvs-games"
            <?php echo $enable_live_updates ? 'data-dsvs-league="' . esc_attr( $league['key'] ) . '"' : ''; ?>
        >
            <?php self::render_heading( $league['name'], $subtitle ); ?>

            <?php if ( empty( $games ) ) : ?>
                <p>Keine passenden Spiele gefunden.</p>
            <?php else : ?>
                <div class="dsvs-table-wrap">
                    <table class="dsvs-table">
                        <thead><tr><th>Termin</th><th>Heim</th><th>Ergebnis</th><th>Gast</th></tr></thead>
                        <tbody>
                            <?php foreach ( $games as $game ) : ?>
                                <?php $is_own_game = DSVS_Parser::is_boebingen_team( $game['home'] ) || DSVS_Parser::is_boebingen_team( $game['away'] ); ?>
                                <tr<?php echo $is_own_game ? ' class="dsvs-own-team"' : ''; ?>>
                                    <td><?php echo esc_html( $game['date'] . ' ' . $game['time'] ); ?></td>
                                    <td><?php echo esc_html( $game['home'] ); ?></td>
                                    <td class="dsvs-result"><?php echo esc_html( $game['result'] ); ?></td>
                                    <td><?php echo esc_html( $game['away'] ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
        <?php
        return ob_get_clean();
    }

    private static function render_heading( $league_name, $subtitle ) {
        ?>
        <header class="dsvs-heading">
            <h2><?php echo esc_html( $league_name ); ?></h2>
            <h3><?php echo esc_html( $subtitle ); ?></h3>
        </header>
        <?php
    }
}
