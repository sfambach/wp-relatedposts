<?php
/**
 * Plugin Name:       RelatedPosts
 * Plugin URI:        https://fambach.net
 * Description:       Ein nativer, moderner Gutenberg-Block für WordPress 7+, der passende Beiträge anhand automatischer oder manueller Kategorien auflistet. Sortiert absteigend nach Änderungsdatum.
 * Version:           1.0.0
 * Author:            Fambach & AI-Copilot
 * Author URI:        https://fambach.net
 * License:           GPL-2.0+
 * Text Domain:       relatedposts
 * GitHub Plugin URI: https://github.com
 */

// Sicherheitsschritt: Verhindert den direkten Aufruf außerhalb von WordPress
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. REGISTRIERUNG DES BLOCKS BEIM INITIALISIEREN
add_action( 'init', 'relatedposts_register_block' );
function relatedposts_register_block() {
    register_block_type( 'fambach/relatedposts', array(
        'title'           => 'RelatedPosts Block',
        'icon'            => 'category',
        'category'        => 'widgets',
        'description'     => 'Listet passende Beiträge auf (Kategorien steuerbar über die rechte Sidebar).',
        
        // Definition der Sidebar-Attribute für WordPress 7+
        'attributes'      => array(
            'anzahl_beitraege' => array(
                'type'    => 'number',
                'default' => 5,
            ),
            'auto_kategorien'  => array(
                'type'    => 'boolean',
                'default' => true,
            ),
            'manuelle_kategorien_ids' => array(
                'type'    => 'string',
                'default' => '',
            ),
        ),
        'supports'        => array(
            'autoRegister' => true, // Automatische Vorschau im Gutenberg-Editor
        ),
        'render_callback' => 'relatedposts_render_block_html',
    ) );
}

// Hilfsfunktion: Ermittelt die korrekte ID (berücksichtigt den Vorschaumodus)
function relatedposts_get_echte_id() {
    $post_id = get_the_ID();
    if ( is_preview() ) {
        global $post;
        if ( $post && wp_is_post_revision( $post->ID ) ) {
            $post_id = wp_is_post_revision( $post->ID );
        }
    }
    return $post_id;
}

// 2. RENDERING LOGIK (NATIVE EDITOR-BOX & LIVE-AUSGABE)
function relatedposts_render_block_html( $attributes, $content ) {
    
    // Einstellungen aus den Block-Attributen laden
    $max_posts   = isset( $attributes['anzahl_beitraege'] ) ? intval( $attributes['anzahl_beitraege'] ) : 5;
    $is_auto_cat = isset( $attributes['auto_kategorien'] ) ? (bool)$attributes['auto_kategorien'] : true;
    $manual_ids  = isset( $attributes['manuelle_kategorien_ids'] ) ? trim($attributes['manuelle_kategorien_ids']) : '';

    // BACKEND: Visuelle Platzhalter-Box im Gutenberg-Editor rendern
    $ist_gutenberg_editor = is_admin() || (defined('REST_REQUEST') && REST_REQUEST && strpos($_SERVER['REQUEST_URI'], 'block-renderer') !== false);

    if ( $ist_gutenberg_editor ) {
        $modus_text = $is_auto_cat ? 'Automatisch (aktuelle Kategorien)' : 'Manuell überschrieben (IDs: ' . (!empty($manual_ids) ? $manual_ids : 'keine angegeben') . ')';
        return '
        <div class="wp-block-relatedposts-box" style="padding: 22px; border: 2px dashed #0073aa; background: #f0f6fa; color: #001f3f; margin: 25px auto !important; border-radius: 6px; display: block !important; width: 100% !important; max-width: 100% !important; box-sizing: border-box !important; clear: both !important; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Oxygen-Sans, Ubuntu, Cantarell, \"Helvetica Neue\", sans-serif;">
            <strong style="font-size: 1.15em; display: block; margin-bottom: 4px;">📂 RelatedPosts</strong>
            <span style="font-size: 0.9em; color: #555; font-style: italic; display:block; margin-bottom: 6px;">
                Limit: <strong>' . $max_posts . ' Beiträge</strong> | Modus: <strong>' . $modus_text . '</strong>
            </span>
            <span style="font-size: 0.85em; color: #0073aa; font-weight: 500;">⚙️ Einstellungen und manuelle IDs können rechts in den Block-Eigenschaften angepasst werden.</span>
        </div>';
    }

    // FRONTEND: Live-Ausgabe für Ihre Blog-Besucher
    $current_id = relatedposts_get_echte_id();
    if ( !$current_id ) return '';

    $kategorien_ids = array();

    // Modus auswerten
    if ( $is_auto_cat ) {
        $kategorie_objekte = get_the_category( $current_id );
        if ( ! empty( $kategorie_objekte ) ) {
            foreach ( $kategorie_objekte as $kat ) {
                $kategorien_ids[] = $kat->term_id;
                
                // Unterkategorien automatisch mit einbeziehen
                $unterkategorien = get_term_children( $kat->term_id, 'category' );
                if ( ! is_wp_error( $unterkategorien ) && ! empty( $unterkategorien ) ) {
                    $kategorien_ids = array_merge( $kategorien_ids, $unterkategorien );
                }
            }
        }
    } else {
        if ( ! empty( $manual_ids ) ) {
            $id_array = explode( ',', $manual_ids );
            foreach ( $id_array as $id ) {
                $clean_id = intval( trim( $id ) );
                if ( $clean_id > 0 ) {
                    $kategorien_ids[] = $clean_id;
                    
                    // Unterkategorien auch bei den manuellen IDs mitnehmen
                    $unterkategorien = get_term_children( $clean_id, 'category' );
                    if ( ! is_wp_error( $unterkategorien ) && ! empty( $unterkategorien ) ) {
                        $kategorien_ids = array_merge( $kategorien_ids, $unterkategorien );
                    }
                }
            }
        }
    }

    $kategorien_ids = array_unique( $kategorien_ids );

    if ( empty( $kategorien_ids ) ) {
        return '';
    }

    // Datenbank-Abfrage nach passenden Beiträgen
    $args = array(
        'post_type'      => 'post',
        'post__not_in'   => array( $current_id ),
        'category__in'   => $kategorien_ids,
        'posts_per_page' => $max_posts,
        'orderby'        => 'modified',
        'order'          => 'DESC',
    );

    $beitrags_query = new WP_Query( $args );

    if ( ! $beitrags_query->have_posts() ) {
        wp_reset_postdata();
        return '';
    }

    // HTML-Listenstruktur generieren
    $output = '<div class="wp-block-verwandte-beitraege-liste" style="margin-top: 40px; padding: 25px; border-top: 2px solid #eeeeee; text-align: left;">';
    $output .= '<h2 style="margin-top:0; margin-bottom:15px; font-size:1.5em; color:#333;">Passende Beiträge (Letzte Updates)</h2>';
    $output .= '<ul style="list-style-type: disc; padding-left: 20px; margin: 0;">';

    while ( $beitrags_query->have_posts() ) {
        $beitrags_query->the_post();
        
        $anderungs_datum = get_the_modified_date( 'd.m.Y' );
        $beitrag_titel   = get_the_title();
        $beitrag_url     = get_permalink();

        $output .= '<li style="margin-bottom: 8px; line-height: 1.4; list-style-type: disc !important;">';
        $output .= '<span style="color: #666666; font-size: 0.95em; margin-right: 10px;">[' . esc_html( $anderungs_datum ) . ']</span>';
        $output .= '<a href="' . esc_url( $beitrag_url ) . '">' . esc_html( $beitrag_titel ) . '</a>';
        $output .= '</li>';
    }

    $output .= '</ul>';
    $output .= '</div>';

    wp_reset_postdata();
    return $output;
}
