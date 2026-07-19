<?php
/**
 * Plugin Name:       WP-RelatedPosts
 * Description:       Ein nativer Gutenberg-Block für verwandte Beiträge mit erweiterten Filtern und Datumsoptionen.
 * Version:           1.3.0
 * Author:            sfambach & AI Assistant
 * License:           GPL-2.0+
 * GitHub Plugin URI: https://github.com
 * Primary Branch:    main
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function fambach_related_posts_block_init() {
    wp_register_script(
        'fambach-related-posts-editor-script',
        plugins_url( 'js/block.js', __FILE__ ),
        array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data' ),
        '1.3.0',
        true
    );

    register_block_type( __DIR__, array(
        'editor_script'   => 'fambach-related-posts-editor-script',
        'render_callback' => 'fambach_render_related_posts_block',
    ) );
}
add_action( 'init', 'fambach_related_posts_block_init' );

function fambach_render_related_posts_block( $attributes ) {
    $aktiviert = isset( $attributes['beitraege_aktivieren'] ) ? $attributes['beitraege_aktivieren'] : true;
    
    if ( is_admin() || ! $aktiviert ) {
        return '';
    }

    // Attribute und Fallbacks auslesen
    $ueberschrift_text = ! empty( $attributes['ueberschrift_text'] ) ? esc_html( $attributes['ueberschrift_text'] ) : 'Verwandte Beiträge';
    $heading_tag       = ! empty( $attributes['ueberschrift_typ'] ) ? $attributes['ueberschrift_typ'] : 'h2';
    $anzahl            = isset( $attributes['anzahl_beitraege'] ) ? (int) $attributes['anzahl_beitraege'] : 5;
    $kategorie_modus   = isset( $attributes['kategorie_modus'] ) ? $attributes['kategorie_modus'] : 'automatisch';
    $debug_modus       = isset( $attributes['debug_modus'] ) ? (bool) $attributes['debug_modus'] : false;
    
    // NEU: Datums- und Listen-Optionen auslesen
    $datum_anzeigen    = isset( $attributes['datum_anzeigen'] ) ? (bool) $attributes['datum_anzeigen'] : false;
    $datum_typ         = ! empty( $attributes['datum_typ'] ) ? $attributes['datum_typ'] : 'date';
    $punkte_anzeigen   = isset( $attributes['punkte_anzeigen'] ) ? (bool) $attributes['punkte_anzeigen'] : true;

    if ( ! in_array( $heading_tag, array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div' ), true ) ) {
        $heading_tag = 'h2';
    }
    
    $current_post_id = get_the_ID();
    $categories = array();

    if ( $kategorie_modus === 'automatisch' && $current_post_id ) {
        $categories = wp_get_post_categories( $current_post_id );
    } elseif ( $kategorie_modus === 'manuell' && isset( $attributes['eigene_kategorien_ids'] ) ) {
        $categories = array_map( 'absint', (array) $attributes['eigene_kategorien_ids'] );
    }

    $output = '';

    // Debugging Ausgabe
    if ( $debug_modus ) {
        $output .= '<div class="wp-related-posts-debug" style="background:#fff3cd; border:1px solid #ffeeba; color:#856404; padding:15px; margin:15px 0; font-family:monospace; font-size:12px; border-radius:4px;">';
        $output .= '<strong>[WP-RelatedPosts Debug Mode]</strong><br>';
        $output .= '• Aktuelle Post ID: ' . ( $current_post_id ? $current_post_id : 'Keine' ) . '<br>';
        $output .= '• Modus: ' . esc_html( $kategorie_modus ) . '<br>';
        $output .= '• Kategorie IDs: ' . ( ! empty( $categories ) ? implode( ', ', $categories ) : 'Keine' ) . '<br>';
        $output .= '• Datum anzeigen: ' . ( $datum_anzeigen ? 'Ja (' . esc_html( $datum_typ ) . ')' : 'Nein' ) . '<br>';
        $output .= '• Punkte anzeigen: ' . ( $punkte_anzeigen ? 'Ja' : 'Nein' ) . '<br>';
        $output .= '</div>';
    }

    if ( empty( $categories ) ) {
        return $output . '<p class="related-posts-empty">Keine Kategorien für die Filterung vorhanden.</p>';
    }

    $args = array(
        'post_type'      => 'post',
        'posts_per_page' => $anzahl,
        'category__in'   => $categories,
        'orderby'        => 'modified',
        'order'          => 'DESC',
    );

    if ( $kategorie_modus === 'automatisch' && $current_post_id ) {
        $args['post__not_in'] = array( $current_post_id );
    }

    $related_query = new WP_Query( $args );

    if ( $related_query->have_posts() ) {
        $output .= '<div class="wp-block-fambach-related-posts">';
        $output .= '<' . $heading_tag . ' class="related-posts-title">' . $ueberschrift_text . '</' . $heading_tag . '>';
        
        // CSS-Inline-Style für die Ausblendung der Punkte generieren
        $ul_style = ! $punkte_anzeigen ? ' style="list-style-type:none; padding-left:0;"' : '';
        $output .= '<ul' . $ul_style . '>';
        
        while ( $related_query->have_posts() ) {
            $related_query->the_post();
            
            // Datum ermitteln falls aktiv
            $formatted_date = '';
            if ( $datum_anzeigen ) {
                $formatted_date = ( $datum_typ === 'modified' ) ? get_the_modified_date( 'd.m.Y' ) : get_the_date( 'd.m.Y' );
                $formatted_date = '<span class="related-post-date" style="margin-right:5px; color:#666;">' . esc_html( $formatted_date ) . ' - </span>';
            }

            $output .= '<li>' . $formatted_date . '<a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></li>';
        }
        
        $output .= '</ul>';
        $output .= '</div>';
        
        wp_reset_postdata();
    } else {
        $output .= '<p class="related-posts-empty">Keine passenden Beiträge gefunden.</p>';
    }

    return $output;
}
