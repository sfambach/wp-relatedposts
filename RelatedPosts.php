<?php
/**
 * Plugin Name:       WP-RelatedPosts
 * Description:       Ein nativer Gutenberg-Block für verwandte Beiträge.
 * Version:           1.0.2
 * Author:            sfambach & AI Assistant
 * License:           GPL-2.0+
 * GitHub Plugin URI: https://github.com
 * Primary Branch:    main
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registriert den Block und sorgt dafür, dass die JS-Abhängigkeiten geladen werden.
 */
function fambach_related_posts_block_init() {
    // Registriert den Block basierend auf der block.json
    register_block_type( __DIR__, array(
        'render_callback' => 'fambach_render_related_posts_block',
    ) );
}
add_action( 'init', 'fambach_related_posts_block_init' );

/**
 * Zwingt WordPress, die nötigen Editor-Skripte für unseren Vanilla-JS-Block bereitzustellen.
 */
function fambach_related_posts_enqueue_assets() {
    $script_asset_path = __DIR__ . '/js/block.js';
    if ( file_exists( $script_asset_path ) ) {
        wp_enqueue_script(
            'fambach-related-posts-editor',
            plugins_url( 'js/block.js', __FILE__ ),
            array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ),
            '1.0.2',
            true
        );
    }
}
add_action( 'enqueue_block_editor_assets', 'fambach_related_posts_enqueue_assets' );

/**
 * Render-Callback für die Ausgabe im Frontend.
 */
function fambach_render_related_posts_block( $attributes ) {
    if ( is_admin() || empty( $attributes['beitraege_aktivieren'] ) ) {
        return '';
    }

    $anzahl = isset( $attributes['anzahl_beitraege'] ) ? (int) $attributes['anzahl_beitraege'] : 5;
    $heading_tag = isset( $attributes['ueberschrift_typ'] ) ? $attributes['ueberschrift_typ'] : 'h3';

    if ( ! in_array( $heading_tag, array( 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div' ), true ) ) {
        $heading_tag = 'h3';
    }
    
    $current_post_id = get_the_ID();
    if ( ! $current_post_id ) {
        return '';
    }

    $categories = wp_get_post_categories( $current_post_id );
    if ( empty( $categories ) ) {
        return '<p class="related-posts-empty">Keine verwandten Kategorien gefunden.</p>';
    }

    $args = array(
        'post_type'      => 'post',
        'posts_per_page' => $anzahl, 
        'post__not_in'   => array( $current_post_id ),
        'category__in'   => $categories,
        'orderby'        => 'modified',
        'order'          => 'DESC',
    );

    $related_query = new WP_Query( $args );
    $output = '';

    if ( $related_query->have_posts() ) {
        $output .= '<div class="wp-block-fambach-related-posts">';
        $output .= '<' . $heading_tag . ' class="related-posts-title">Das könnte dich auch interessieren:</' . $heading_tag . '>';
        $output .= '<ul>';
        
        while ( $related_query->have_posts() ) {
            $related_query->the_post();
            $output .= '<li><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></li>';
        }
        
        $output .= '</ul>';
        $output .= '</div>';
        
        wp_reset_postdata();
    } else {
        $output .= '<p class="related-posts-empty">Keine ähnlichen Beiträge gefunden.</p>';
    }

    return $output;
}
