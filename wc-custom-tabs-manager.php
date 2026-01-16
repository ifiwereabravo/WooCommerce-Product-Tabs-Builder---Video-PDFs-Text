<?php
/**
 * Plugin Name: TABBY: A Multi-media WooCommerce Product Tab Builder
 * Version: 1.9.0
 * Author: Your Partner
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * Description: A robust, multi-media tab builder for products. Requires <a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a> to function.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Updated naming convention to match TABBY branding
define( 'TABBY_KEY', '_tabby_custom_tabs_data' );

/** * MODULE 1: ADMIN UI (TABBY BRANDED) */
add_action( 'add_meta_boxes', function() {
    add_meta_box('tabby_builder', 'TABBY: Product Resource Tabs', 'tabby_builder_html', 'product', 'normal', 'high');
});

function tabby_builder_html( $post ) {
    $tabs = get_post_meta( $post->ID, TABBY_KEY, true ) ?: ['row_0' => ['title' => '', 'layout' => 'editor', 'content' => '']];
    wp_nonce_field( 'tabby_save', 'tabby_nonce' );
    wp_enqueue_media();
    ?>
    <style>
        /* BRANDED UI */
        .tabby-ctrl-bar { background: #f0f0f1; padding: 12px; border: 1px solid #ccd0d4; margin-bottom: 10px; display: flex; gap: 12px; border-radius: 4px; }
        .tabby-tab-row { background: #fff; border: 1px solid #ccd0d4; margin-bottom: 15px; border-radius: 4px; border-left: 4px solid #2271b1; }
        .tabby-tab-header { display: flex; gap: 10px; padding: 12px; align-items: center; cursor: move; border-bottom: 1px solid #eee; background: #fff; }
        .tabby-tab-body { padding: 20px; }
        
        /* ADMIN GRID */
        .tabby-admin-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 15px; padding: 15px; background: #fafafa; border: 1px dashed #dcdcde; margin-top: 10px; }
        .tabby-admin-item { text-align: center; background: #fff; padding: 5px; border: 1px solid #ddd; border-radius: 4px; position: relative; }
        .tabby-admin-item img { width: 60px; height: 60px; object-fit: contain; display: block; margin: 0 auto; }
        
        .tabby-hidden { display: none !important; }
        .tabby-collapsed .tabby-tab-body { display: none; }
        .tabby-toggle-row { transition: 0.2s; cursor:pointer; font-size: 20px; }
        .tabby-collapsed .tabby-toggle-row { transform: rotate(-90deg); }
    </style>

    <div id="tabby-builder-container">
        <div class="tabby-ctrl-bar">
            <button type="button" class="button tabby-expand-all">Expand All</button>
            <button type="button" class="button tabby-collapse-all">Collapse All</button>
            <button type="button" class="button button-primary tabby-add-tab" style="margin-left:auto;">+ Create New Tab</button>
        </div>

        <div id="tabby-tabs-list">
            <?php foreach ( $tabs as $id => $tab ) : $layout = $tab['layout'] ?? 'editor'; ?>
                <div class="tabby-tab-row postbox" data-id="<?php echo esc_attr($id); ?>">
                    <div class="tabby-tab-header">
                        <span class="dashicons dashicons-arrow-down-alt2 tabby-toggle-row"></span>
                        <span class="dashicons dashicons-menu" style="color:#ccc;"></span>
                        <input type="text" name="tabby_data[<?php echo $id; ?>][title]" value="<?php echo esc_attr($tab['title'] ?? ''); ?>" placeholder="Enter Tab Title" style="flex-grow:1; font-weight:600;">
                        <select name="tabby_data[<?php echo $id; ?>][layout]" class="tabby-layout-selector">
                            <option value="editor" <?php selected($layout, 'editor'); ?>>TEXT EDITOR</option>
                            <option value="grid" <?php selected($layout, 'grid'); ?>>PDF/IMAGE RESOURCES</option>
                            <option value="video" <?php selected($layout, 'video'); ?>>VIDEO EMBED</option>
                        </select>
                        <button type="button" class="button-link-delete tabby-remove-tab" style="color:#a00;">Remove</button>
                    </div>

                    <div class="tabby-tab-body">
                        <div class="view-editor <?php echo $layout !== 'editor' ? 'tabby-hidden' : ''; ?>">
                            <textarea name="tabby_data[<?php echo $id; ?>][content]" class="widefat" rows="8"><?php echo esc_textarea($tab['content'] ?? ''); ?></textarea>
                        </div>

                        <div class="view-grid <?php echo $layout !== 'grid' ? 'tabby-hidden' : ''; ?>">
                            <button type="button" class="button tabby-batch-upload">Batch Image Upload</button>
                            <div class="tabby-admin-grid">
                                <?php if(!empty($tab['items'])) foreach ($tab['items'] as $it_id => $item) : ?>
                                    <div class="tabby-admin-item">
                                        <img src="<?php echo esc_url($item['thumb']); ?>">
                                        <input type="hidden" name="tabby_data[<?php echo $id; ?>][items][<?php echo $it_id; ?>][label]" value="<?php echo esc_attr($item['label']); ?>">
                                        <input type="hidden" name="tabby_data[<?php echo $id; ?>][items][<?php echo $it_id; ?>][url]" value="<?php echo esc_url($item['url']); ?>">
                                        <input type="hidden" name="tabby_data[<?php echo $id; ?>][items][<?php echo $it_id; ?>][thumb]" value="<?php echo esc_url($item['thumb']); ?>">
                                        <button type="button" class="button-link-delete tabby-remove-node">×</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="view-video <?php echo $layout !== 'video' ? 'tabby-hidden' : ''; ?>">
                            <p><em>Add video links (YouTube/Vimeo) below.</em></p>
                            </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $("#tabby-tabs-list").sortable({ handle: ".tabby-tab-header" });
        $(document).on('click', '.tabby-expand-all', function(){ $('.tabby-tab-row').removeClass('tabby-collapsed'); });
        $(document).on('click', '.tabby-collapse-all', function(){ $('.tabby-tab-row').addClass('tabby-collapsed'); });
        $(document).on('click', '.tabby-toggle-row', function() { $(this).closest('.tabby-tab-row').toggleClass('tabby-collapsed'); });
        $(document).on('change', '.tabby-layout-selector', function() {
            var row = $(this).closest('.tabby-tab-row');
            row.find('.view-editor, .view-grid').addClass('tabby-hidden');
            row.find('.view-' + $(this).val()).removeClass('tabby-hidden');
        });
        $(document).on('click', '.tabby-batch-upload', function(e) {
            e.preventDefault();
            var grid = $(this).closest('.view-grid').find('.tabby-admin-grid');
            var tabId = $(this).closest('.tabby-tab-row').data('id');
            var frame = wp.media({ title: 'TABBY Batch Upload', multiple: true }).open();
            frame.on('select', function() {
                frame.state().get('selection').each(function(a) {
                    var d = a.toJSON();
                    var t = d.icon; if (d.sizes && d.sizes.thumbnail) t = d.sizes.thumbnail.url;
                    var itId = 'item_' + Math.random().toString(36).substr(2, 9);
                    grid.append('<div class="tabby-admin-item"><img src="'+t+'"><input type="hidden" name="tabby_data['+tabId+'][items]['+itId+'][label]" value="'+d.title+'"><input type="hidden" name="tabby_data['+tabId+'][items]['+itId+'][url]" value="'+d.url+'"><input type="hidden" name="tabby_data['+tabId+'][items]['+itId+'][thumb]" value="'+t+'"><button type="button" class="button-link-delete tabby-remove-node">×</button></div>');
                });
            });
        });
        $(document).on('click', '.tabby-remove-tab', function() { if(confirm('Remove this Tab?')) $(this).closest('.tabby-tab-row').remove(); });
        $(document).on('click', '.tabby-remove-node', function() { $(this).closest('.tabby-admin-item').remove(); });
    });
    </script>
    <?php
}

/** * MODULE 2: SAVE DATA (LOCKED SCHEMA) */
add_action( 'save_post_product', function( $post_id ) {
    if ( isset($_POST['tabby_data']) ) update_post_meta($post_id, TABBY_KEY, $_POST['tabby_data']);
});

/** * MODULE 3: FRONTEND RENDERING (VISIBILITY TUNED) */
add_filter( 'woocommerce_product_tabs', function( $tabs ) {
    global $post;
    $data = get_post_meta($post->ID, TABBY_KEY, true);
    if ( is_array($data) ) {
        foreach($data as $id => $tab) {
            $title = !empty($tab['title']) ? $tab['title'] : 'Resources';
            $tabs['tabby_'.$id] = [ 'title' => esc_html($title), 'priority' => 50, 'callback' => 'tabby_render_tab_content', 'tab_data' => $tab ];
        }
    }
    return $tabs;
});

function tabby_render_tab_content($key, $tab_info) {
    $tab = $tab_info['tab_data'];
    if ( ($tab['layout'] ?? 'editor') === 'grid' && !empty($tab['items']) ) {
        echo '<style>
            .tabby-grid { display: flex; flex-wrap: wrap; gap: 45px; justify-content: flex-start; padding: 20px 0; }
            .tabby-item { flex: 0 0 140px; text-align: center; margin-bottom: 25px; }
            .tabby-img { 
                width: 100px; height: 100px; object-fit: contain; 
                border: 1px solid #eee; padding: 4px; margin: 0 auto 12px; 
                display: block; background: #fff; border-radius: 4px; 
                box-shadow: 0 2px 4px rgba(0,0,0,0.03); 
                transition: all 0.25s ease;
            }
            .tabby-item a:hover .tabby-img { 
                transform: translateY(-6px); 
                box-shadow: 0 8px 15px rgba(0,0,0,0.1); 
                border-color: #2271b1; 
            }
            .tabby-label { display: block; font-size: 13px; line-height: 1.3; font-weight: 500; color: #333; }
        </style>';

        echo '<div class="tabby-grid">';
        foreach($tab['items'] as $item) {
            echo '<div class="tabby-item">';
            echo '<a href="'.esc_url($item['url']).'" target="_blank" style="text-decoration:none; color:inherit;">';
            echo '<img src="'.esc_url($item['thumb']).'" class="tabby-img">';
            echo '<span class="tabby-label">'.esc_html($item['label']).'</span>';
            echo '</a></div>';
        }
        echo '</div>';
    } else {
        echo apply_filters('the_content', $tab['content'] ?? '');
    }
}