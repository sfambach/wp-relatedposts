<?php
/**
 * Plugin Name:       RelatedPosts
 * Plugin URI:        https://fambach.net
 * Description:       A native, modern Gutenberg block for WordPress 7+ that displays related posts based on automatic or manual categories, sorted descending by their last modification date.
 * Version:           1.0.2
 * Author:            Fambach & AI-Copilot
 * Author URI:        https://fambach.net
 * License:           GPL-2.0+
 * Text Domain:       relatedposts
 * GitHub Plugin URI: https://github.com
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. REGISTRIERUNG DES BLOCKS MIT ALLEN NEUEN ATTRIBUTEN
add_action( 'init', 'relatedposts_register_block' );
function relatedposts_register_block() {
    register_block_type( 'fambach/relatedposts', array(
        'title'           => 'RelatedPosts Block',
        'icon'            => 'category',
        'category'        => 'widgets',
        'description'     => 'Listet passende Beiträge auf (Kategorien, Überschriften und HTML-Tags steuerbar über die rechte Sidebar).',
        
        'attributes'      => array(
            'anzahl_beitraege' => array(
                'type'    => 'number',
                'default' => 5,
				'step'    => 1,
				'min'     => 1,
				'sanitize_callback' => 'absint'
            ),
            'auto_kategorien'  => array(
                'type'    => 'boolean',
                'default' => true,
            ),
            'manuelle_kategorien_ids' => array(
                'type'    => 'string',
                'default' => '',
            ),
            // 🌟 NEU: Attribut für den individuellen Überschriften-Text
            'ueberschrift_text' => array(
                'type'    => 'string',
                'default' => 'Passende Beiträge (Letzte Updates)',
            ),
            // 🌟 NEU: Attribut für die Überschriften-Größe (H2, H3, H4)
            'ueberschrift_tag' => array(
                'type'    => 'string',
                'default' => 'h2',
            ),
			'ueberschrift_tag' => array(
				'type'    => 'string',
				'enum'    => array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div'),
				'default' => 'h2',
            ),
        ),
        'supports'        => array(
            'autoRegister' => true,
        ),
        'render_callback' => 'relatedposts_render_block_html',
    ) );
}

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

// 2. RENDERING LOGIK (MIT ERWEITERTEN EINSTELLUNGEN)
function relatedposts_render_block_html( $attributes, $content ) {
    
    // Attribute laden (inklusive der neuen Überschriften-Optionen)
    $max_posts       = isset( $attributes['anzahl_beitraege'] ) ? intval( $attributes['anzahl_beitraege'] ) : 5;
    $is_auto_cat     = isset( $attributes['auto_kategorien'] ) ? (bool)$attributes['auto_kategorien'] : true;
    $manual_ids      = isset( $attributes['manuelle_kategorien_ids'] ) ? trim($attributes['manuelle_kategorien_ids']) : '';
    $ueberschrift    = isset( $attributes['ueberschrift_text'] ) ? trim($attributes['ueberschrift_text']) : 'Passende Beiträge (Letzte Updates)';
    $html_tag        = isset( $attributes['ueberschrift_tag'] ) ? trim($attributes['ueberschrift_tag']) : 'h2';

    // Sicherheits-Validierung des HTML-Tags (erlaubt nur h2, h3, h4)
    if ( ! in_array( $html_tag, array( 'h2', 'h3', 'h4' ) ) ) {
        $html_tag = 'h2';
    }

    $current_id = relatedposts_get_echte_id();

    // BACKEND: Visuelle Infobox im Gutenberg-Editor rendern
    $ist_gutenberg_editor = is_admin() || (defined('REST_REQUEST') && REST_REQUEST && strpos($_SERVER['REQUEST_URI'], 'block-renderer') !== false);

    if ( $ist_gutenberg_editor ) {
        $kategorien_anzeige = array();
        
        if ( $is_auto_cat && $current_id ) {
            $kategorie_objekte = get_the_category( $current_id );
            if ( ! empty( $kategorie_objekte ) ) {
                foreach ( $kategorie_objekte as $kat ) {
                    $kategorien_anzeige[] = $kat->name . ' (ID: ' . $kat->term_id . ')';
                }
            }
            $modus_text = 'Automatisch';
            $kats_liste_str = !empty($kategorien_anzeige) ? implode(', ', $kategorien_anzeige) : 'Keine Kategorien zugewiesen';
        } else {
            $modus_text = 'Manuell überschrieben';
            $kats_liste_str = !empty($manual_ids) ? $manual_ids : 'Keine IDs eingetragen';
        }

        $output = '
        <div class="wp-block-relatedposts-box" style="padding: 22px; border: 2px dashed #0073aa; background: #f0f6fa; color: #001f3f; margin: 25px auto !important; border-radius: 6px; display: block !important; width: 100% !important; max-width: 100% !important; box-sizing: border-box !important; clear: both !important; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Oxygen-Sans, Ubuntu, Cantarell, \"Helvetica Neue\", sans-serif; text-align: left;">
            <strong style="font-size: 1.15em; display: block; margin-bottom: 6px;">📂 RelatedPosts</strong>
            
            <div style="font-size: 0.9em; color: #333; line-height: 1.5; margin-bottom: 10px; background: #ffffff; padding: 10px; border-left: 4px solid #0073aa; border-radius: 2px;">
                • <strong>Überschrift (' . strtoupper($html_tag) . '):</strong> ' . esc_html($ueberschrift) . '<br>
                • <strong>Limit:</strong> ' . $max_posts . ' Beiträge<br>
                • <strong>Modus:</strong> ' . $modus_text . '<br>
                • <strong>Erkannte Kategorien im Editor:</strong> <code>' . esc_html($kats_liste_str) . '</code>
            </div>';
            
            if ( $is_auto_cat ) {
                $output .= '
                <div style="font-size: 0.85em; color: #d9534f; background: #fdf7f7; padding: 8px; border-radius: 4px; border: 1px solid #ebccd1; margin-top: 5px; font-weight: 500;">
                    ⚠️ <strong>Hinweis zur Kategorie-Änderung:</strong> Wenn Sie die Kategorien dieses Beitrags rechts in den WordPress-Dokument-Einstellungen ändern, speichern oder aktualisieren Sie den Beitrag bitte einmal, damit dieser Block die neuen Kategorien übernimmt.
                </div>';
            }
            
        $output .= '</div>';
        return $output;
    }

    // --- FRONTEND: Live-Ausgabe für Ihre Blog-Besucher ---
    if ( !$current_id ) return '';

    $kategorien_ids = array();

    if ( $is_auto_cat ) {
        $kategorie_objekte = get_the_category( $current_id );
        if ( ! empty( $kategorie_objekte ) ) {
            foreach ( $kategorie_objekte as $kat ) {
                $kategorien_ids[] = $kat->term_id;
                
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
    
    // 🌟 DYNAMISCHER HTML-TAG UND TEXT: Rendert genau das gewählte h2, h3 oder h4 aus der Sidebar
    $output .= '<' . $html_tag . ' style="margin-top:0; margin-bottom:15px; color:#333;">' . esc_html($ueberschrift) . '</' . $html_tag . '>';
    
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
