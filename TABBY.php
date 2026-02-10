<?php
/**
 * Plugin Name: TABBY: A Multi-media WooCommerce Product Tab Builder
 * Version: 1.9.15
 * Author: BravoTechnologies.com
 * Author URI: https://bravotechnologies.com
 * Description: NIGHTY-friendly by design (CSS vars + stable selectors), hardened saving, safer runtime guards, reliable sortable/media enqueues, stable tab ordering, and old-version conflict prevention (no fatal redeclare).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'TABBY_KEY', '_tabby_custom_tabs_data' );

/**
 * -----------------------------------------------------------------------------
 * SAFETY + COMPAT HELPERS
 * -----------------------------------------------------------------------------
 */
function tabby_v199_is_woocommerce_active(): bool {
	return class_exists( 'WooCommerce' ) || function_exists( 'WC' );
}

/**
 * -----------------------------------------------------------------------------
 * ACTIVATION: prevent conflicts with legacy TABBY installs
 * -----------------------------------------------------------------------------
 */
register_activation_hook( __FILE__, function() {
	$maybe_old = [
		// Your reported old plugin folder/file:
		'TABBY-Multimedia-WC-Product-tab-builder.php_/TABBY-Multimedia-WC-Product-tab-builder.php',

		// Other common variants:
		'TABBY-Multimedia-WC-Product-tab-builder/TABBY-Multimedia-WC-Product-tab-builder.php',
		'tabby-multimedia-wc-product-tab-builder/tabby-multimedia-wc-product-tab-builder.php',
		'wc-product-content-tabs-v1/wc-product-content-tabs-v1.php',
	];

	$active = (array) get_option( 'active_plugins', [] );
	$to_deactivate = [];

	foreach ( $maybe_old as $relpath ) {
		if ( in_array( $relpath, $active, true ) ) {
			$to_deactivate[] = $relpath;
		}
	}

	if ( ! empty( $to_deactivate ) ) {
		deactivate_plugins( $to_deactivate, true );
		set_transient( 'tabby_v199_deactivated_old_versions', $to_deactivate, 120 );
	}
} );

add_action( 'admin_notices', function() {
	$old = get_transient( 'tabby_v199_deactivated_old_versions' );
	if ( ! $old ) return;
	delete_transient( 'tabby_v199_deactivated_old_versions' );

	echo '<div class="notice notice-warning"><p><strong>TABBY:</strong> Older TABBY plugin versions were deactivated to prevent conflicts. You can delete them from Plugins → Installed Plugins.</p></div>';
} );

/**
 * -----------------------------------------------------------------------------
 * ADMIN ASSETS (NIGHTY-friendly by design)
 * -----------------------------------------------------------------------------
 */
add_action( 'admin_enqueue_scripts', function( $hook ) {
	if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || $screen->post_type !== 'product' ) return;

	wp_enqueue_media();
	wp_enqueue_script( 'jquery-ui-sortable' );

	wp_register_style( 'tabby-admin', false, [], '1.9.14' );
	wp_enqueue_style( 'tabby-admin' );

	$css = <<<CSS
#tabby-builder-container {
	--tabby-surface: var(--wp-admin-theme-color-background, #ffffff);
	--tabby-surface-2: rgba(0,0,0,0.03);
	--tabby-text: #1d2327;
	--tabby-text-muted: #50575e;

	--tabby-border: rgba(0,0,0,0.12);
	--tabby-border-2: rgba(0,0,0,0.08);

	--tabby-accent: var(--wp-admin-theme-color, #2271b1);
	--tabby-danger: #cc0000;

	--tabby-shadow: 0 1px 2px rgba(0,0,0,0.06);
	--tabby-radius: 6px;
}

#tabby-builder-container,
#tabby-builder-container * { box-sizing: border-box; }

#tabby-builder-container .tabby-ctrl-bar {
	background: var(--tabby-surface-2);
	padding: 12px;
	border: 1px solid var(--tabby-border);
	margin-bottom: 10px;
	display: flex;
	gap: 12px;
	border-radius: var(--tabby-radius);
}

#tabby-builder-container .tabby-tab-row {
	background: var(--tabby-surface);
	border: 1px solid var(--tabby-border);
	margin-bottom: 15px;
	border-radius: var(--tabby-radius);
	box-shadow: var(--tabby-shadow);
	position: relative;
	overflow: hidden;
}

#tabby-builder-container .tabby-tab-row::before {
	content:"";
	position:absolute;
	left:0; top:0; bottom:0;
	width:4px;
	background: var(--tabby-accent);
}

#tabby-builder-container .tabby-tab-header {
	display:flex;
	gap:10px;
	padding: 12px 12px 12px 16px;
	align-items:center;
	cursor:move;
	border-bottom: 1px solid var(--tabby-border-2);
	color: var(--tabby-text);
}

#tabby-builder-container .tabby-tab-body {
	padding: 12px 12px 16px 16px;
}

#tabby-builder-container .tabby-admin-grid {
	display:grid;
	grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
	gap:15px;
	padding:15px;
	background: var(--tabby-surface-2);
	border: 1px dashed var(--tabby-border);
	margin-top:10px;
	min-height:80px;
	border-radius: calc(var(--tabby-radius) - 2px);
}

#tabby-builder-container .tabby-admin-item {
	text-align:center;
	background: var(--tabby-surface);
	padding:10px;
	border:1px solid var(--tabby-border);
	border-radius: calc(var(--tabby-radius) - 2px);
	position:relative;
	cursor:grab;
}

#tabby-builder-container .tabby-admin-item img {
	width:60px; height:60px;
	object-fit: contain;
	display:block;
	margin: 0 auto 10px;
	pointer-events:none;
}

#tabby-builder-container .tabby-resource-label-input {
	width:100%;
	font-size:11px;
	padding:4px;
	border:1px solid var(--tabby-border);
	border-radius: 4px;
	text-align:center;
	color: var(--tabby-text);
	background: var(--tabby-surface);
}

#tabby-builder-container .tabby-video-row {
	background: var(--tabby-surface);
	padding:16px;
	border: 1px solid var(--tabby-border);
	margin-bottom:10px;
	position:relative;
	border-radius: var(--tabby-radius);
}

#tabby-builder-container .tabby-remove-node {
	position:absolute;
	top:-8px;
	right:-8px;
	background: var(--tabby-danger);
	color:#fff;
	border:none;
	border-radius:50%;
	cursor:pointer;
	width:22px; height:22px;
	line-height:20px;
	font-weight:bold;
	z-index:100;
}

#tabby-builder-container .tabby-hidden { display:none !important; }
#tabby-builder-container .tabby-collapsed .tabby-tab-body { display:none; }

#tabby-builder-container .ui-sortable-placeholder {
	border: 2px dashed var(--tabby-accent) !important;
	visibility: visible !important;
	background: rgba(34,113,177,0.08) !important;
	height: 90px;
	border-radius: var(--tabby-radius);
}
CSS;

	wp_add_inline_style( 'tabby-admin', $css );
} );

/**
 * -----------------------------------------------------------------------------
 * FRONTEND ASSETS (themeable)
 * -----------------------------------------------------------------------------
 */
add_action( 'wp_enqueue_scripts', function() {
	if ( ! tabby_v199_is_woocommerce_active() ) return;
	if ( ! function_exists( 'is_product' ) || ! is_product() ) return;

	wp_register_style( 'tabby-frontend', false, [], '1.9.14' );
	wp_enqueue_style( 'tabby-frontend' );

	$css = <<<CSS
.tabby-frontend {
	--tabby-fe-text: inherit;
	--tabby-fe-border: rgba(0,0,0,0.12);
	--tabby-fe-surface: transparent;
	--tabby-fe-accent: currentColor;
}

/* PDF / Image grid */
.tabby-frontend .tabby-grid {
	display:flex;
	flex-wrap:wrap;
	gap:45px;
	justify-content:flex-start;
	padding:20px 0;
}

.tabby-frontend .tabby-item { flex:0 0 140px; text-align:center; margin-bottom:25px; }

.tabby-frontend .tabby-img {
	width:100px; height:100px;
	object-fit:contain;
	border:1px solid var(--tabby-fe-border);
	padding:4px;
	margin:0 auto 12px;
	display:block;
	background: var(--tabby-fe-surface);
	border-radius: 6px;
	box-shadow: 0 2px 4px rgba(0,0,0,0.03);
	transition: all 0.2s ease;
}

.tabby-frontend .tabby-item a:hover .tabby-img {
	transform: translateY(-5px);
	box-shadow: 0 8px 15px rgba(0,0,0,0.1);
	border-color: var(--tabby-fe-accent);
}

.tabby-frontend .tabby-label {
	display:block;
	font-size:13px;
	line-height:1.3;
	font-weight:500;
	color: var(--tabby-fe-text);
}

/* Video embed (Divi-safe + click-safe) */
.tabby-frontend .tabby-video-wrap {
	position: relative;
	z-index: 1;
	margin-bottom: 35px;
}

.tabby-frontend .tabby-video-wrap {
	pointer-events: none; /* allow iframe to receive hover/click even if wrap overlays */
}
.tabby-frontend .tabby-video-title,
.tabby-frontend .tabby-video-frame,
.tabby-frontend .tabby-video-frame iframe {
	pointer-events: auto !important;
}

.tabby-frontend .tabby-video-title { margin-bottom: 12px; }

.tabby-frontend .tabby-video-frame {
	position: relative;
	z-index: 2;
	width: 100%;
	aspect-ratio: 16 / 9;
	/* fallback if aspect-ratio unsupported */
	min-height: 240px;
	overflow: hidden;
	border-radius: 8px;
	background: #000;
}

.tabby-frontend .tabby-video-frame iframe {
	position: absolute !important;
	inset: 0 !important;
	width: 100% !important;
	height: 100% !important;
	display: block !important;
}


/* Prevent transformed ancestor hit-test bugs (Divi animated containers) */
.tabby-frontend,
.tabby-frontend .tabby-video-wrap,
.tabby-frontend .tabby-video-frame {
	transform: none !important;
	filter: none !important;
	perspective: none !important;
	backface-visibility: visible !important;
}

.tabby-frontend .tabby-video-frame iframe {
	position:absolute;
	top:0; left:0;
	width:100%;
	height:100%;
	border:0;
	z-index: 9999;
	pointer-events: auto !important;
	display:block;
}

/* Kill common overlay click-capture layers within TABBY scope (Divi/players) */
.tabby-frontend .et_pb_video_overlay,
.tabby-frontend .et_pb_video_overlay_hover,
.tabby-frontend .et_pb_video_play,
.tabby-frontend .mejs-overlay,
.tabby-frontend .mejs-layer {
	pointer-events: none !important;
}
CSS;

	wp_add_inline_style( 'tabby-frontend', $css );

	// Force YouTube controls + clickability after tab activation (Woo/Divi tabs may render hidden first).
	wp_register_script( 'tabby-video-fix', false, [], '1.9.14', true );
	wp_enqueue_script( 'tabby-video-fix' );

	$js = <<<JS
(function(){
	function setParam(url, k, v){
		try {
			var u = new URL(url, window.location.href);
			u.searchParams.set(k, v);
			return u.toString();
		} catch(e){ return url; }
	}

	function normalizeYouTubeSrc(src){
		if (!src) return src;
		// Convert youtu.be to /embed/
		if (src.indexOf('youtu.be/') !== -1) {
			var m = src.match(/youtu\.be\/([A-Za-z0-9_-]{11})/);
			if (m && m[1]) src = 'https://www.youtube.com/embed/' + m[1];
		}
		// Force key params
		src = setParam(src, 'controls', '1');
		src = setParam(src, 'fs', '1');
		src = setParam(src, 'playsinline', '1');
		src = setParam(src, 'rel', '0');
		src = setParam(src, 'modestbranding', '1');
		src = setParam(src, 'enablejsapi', '1');
		return src;
	}

	function temporarilyDisableTransform(el){
		// Divi sometimes applies transforms on large wrappers which can break iframe hit-testing inside hidden tabs.
		// We only touch ancestors of the TABBY iframe, and we revert quickly.
		if (!el) return;
		var key = 'data-tabby-prev-transform';
		var nodes = [];
		var cur = el;
		var guard = 0;

		while (cur && guard < 20 && cur !== document.body && cur !== document.documentElement) {
			if (cur.classList && cur.classList.contains('et-animated-content')) {
				nodes.push(cur);
				break; // one is enough
			}
			cur = cur.parentElement;
			guard++;
		}

		nodes.forEach(function(node){
			try {
				if (node.getAttribute(key) !== null) return;
				var prev = node.style.transform || '';
				node.setAttribute(key, prev);
				node.style.transform = 'none';
				node.style.filter = 'none';
				node.style.perspective = 'none';
				// revert shortly after
				setTimeout(function(){
					try {
						var old = node.getAttribute(key);
						node.style.transform = old || '';
						node.removeAttribute(key);
					} catch(e){}
				}, 1200);
			} catch(e){}
		});
	}

	function refreshIframes(root){
		root = root || document;
		var iframes = root.querySelectorAll('.tabby-frontend .tabby-video-frame iframe');
		if (!iframes.length) return;

		iframes.forEach(function(iframe){
			var src = iframe.getAttribute('src') || '';
			src = normalizeYouTubeSrc(src);
			iframe.setAttribute('src', src);
			iframe.style.pointerEvents = 'auto';

			// Rebuild iframe node to fix hit-testing in Divi/Woo tabs when panels are initially hidden.
			var parent = iframe.parentNode;
			if (!parent) return;

			try {
				temporarilyDisableTransform(iframe);
				var clone = iframe.cloneNode(true);
				// Ensure clone is interactive
				clone.style.pointerEvents = 'auto';
				parent.replaceChild(clone, iframe);
			} catch(e){}
		});
	}

	function scheduleForce(){
		// Run multiple times to cover: initial load, tab activation, Divi animations, and late DOM injection.
		refreshIframes(document);
		setTimeout(function(){ refreshIframes(document); }, 150);
		setTimeout(function(){ refreshIframes(document); }, 600);
		setTimeout(function(){ refreshIframes(document); }, 1200);
	}

	function bindTabClicks(){
		document.addEventListener('click', function(e){
			var a = e.target && e.target.closest ? e.target.closest('.wc-tabs a, .woocommerce-tabs .tabs a, .et_pb_tabs_controls a, .et_pb_wc_tabs .tabs a') : null;
			if (!a) return;
			scheduleForce();
		}, true);

		// Also listen for hash changes (some tab systems use hash navigation)
		window.addEventListener('hashchange', function(){ scheduleForce(); }, false);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function(){ scheduleForce(); bindTabClicks(); });
	} else {
		scheduleForce(); bindTabClicks();
	}
})();
JS;

	wp_add_inline_script( 'tabby-video-fix', $js );
} );

/**
 * -----------------------------------------------------------------------------
 * MODULE 1: ADMIN UI (metabox)
 * -----------------------------------------------------------------------------
 */
add_action( 'add_meta_boxes', function() {
	add_meta_box(
		'tabby_builder',
		'TABBY: Product Resource Tabs by Bravo Technologies',
		'tabby_v199_builder_html',
		'product',
		'normal',
		'high'
	);
} );

function tabby_v199_builder_html( $post ) {
	try {
		$tabs = get_post_meta( $post->ID, TABBY_KEY, true );
		if ( ! is_array( $tabs ) || empty( $tabs ) ) {
			$tabs = [
				'row_0' => [ 'title' => '', 'layout' => 'editor', 'content' => '' ],
			];
		}

		wp_nonce_field( 'tabby_save_action', 'tabby_nonce_field' );
		?>
		<div id="tabby-builder-container" class="tabby-scope tabby-admin-scope">
			<div class="tabby-ctrl-bar">
				<button type="button" class="button tabby-expand-all">Expand All</button>
				<button type="button" class="button tabby-collapse-all">Collapse All</button>
				<button type="button" class="button button-primary tabby-add-tab" style="margin-left:auto;">+ Create New Tab</button>
			</div>

			<div id="tabby-tabs-list">
				<?php foreach ( $tabs as $id => $tab ) :
					$layout   = isset( $tab['layout'] ) ? (string) $tab['layout'] : 'editor';
					$id_clean = esc_attr( (string) $id );
					?>
					<div class="tabby-tab-row postbox" data-id="<?php echo $id_clean; ?>">
						<div class="tabby-tab-header">
							<span class="dashicons dashicons-arrow-down-alt2 tabby-toggle-row" title="Collapse/Expand"></span>
							<input type="text"
								name="tabby_data[<?php echo $id_clean; ?>][title]"
								value="<?php echo esc_attr( $tab['title'] ?? '' ); ?>"
								placeholder="Tab Title"
								style="flex-grow:1; font-weight:bold;">
							<select name="tabby_data[<?php echo $id_clean; ?>][layout]" class="tabby-layout-selector">
								<option value="editor" <?php selected( $layout, 'editor' ); ?>>TEXT EDITOR</option>
								<option value="grid" <?php selected( $layout, 'grid' ); ?>>PDF/IMAGE RESOURCES</option>
								<option value="video" <?php selected( $layout, 'video' ); ?>>VIDEO EMBED</option>
							</select>
							<button type="button" class="button-link-delete tabby-remove-tab" style="color:#a00;">Remove</button>
						</div>

						<div class="tabby-tab-body">
							<div class="view-editor <?php echo $layout !== 'editor' ? 'tabby-hidden' : ''; ?>">
								<textarea name="tabby_data[<?php echo $id_clean; ?>][content]" class="widefat" rows="8"><?php echo esc_textarea( $tab['content'] ?? '' ); ?></textarea>
							</div>

							<div class="view-grid <?php echo $layout !== 'grid' ? 'tabby-hidden' : ''; ?>">
								<button type="button" class="button tabby-batch-upload">Batch Image/PDF Upload</button>
								<div class="tabby-admin-grid tabby-sortable-items">
									<?php
									if ( ! empty( $tab['items'] ) && is_array( $tab['items'] ) ) :
										foreach ( $tab['items'] as $it_id => $item ) :
											$it_id_clean = esc_attr( (string) $it_id );
											?>
											<div class="tabby-admin-item" data-item-id="<?php echo $it_id_clean; ?>">
												<button type="button" class="tabby-remove-node" title="Remove">×</button>
												<img src="<?php echo esc_url( $item['thumb'] ?? '' ); ?>" alt="">
												<input type="text"
													class="tabby-resource-label-input"
													name="tabby_data[<?php echo $id_clean; ?>][items][<?php echo $it_id_clean; ?>][label]"
													value="<?php echo esc_attr( $item['label'] ?? '' ); ?>">
												<input type="hidden"
													name="tabby_data[<?php echo $id_clean; ?>][items][<?php echo $it_id_clean; ?>][url]"
													value="<?php echo esc_url( $item['url'] ?? '' ); ?>">
												<input type="hidden"
													name="tabby_data[<?php echo $id_clean; ?>][items][<?php echo $it_id_clean; ?>][thumb]"
													value="<?php echo esc_url( $item['thumb'] ?? '' ); ?>">
											</div>
										<?php
										endforeach;
									endif;
									?>
								</div>
							</div>

							<div class="view-video <?php echo $layout !== 'video' ? 'tabby-hidden' : ''; ?>">
								<div class="tabby-video-list">
									<?php
									if ( ! empty( $tab['videos'] ) && is_array( $tab['videos'] ) ) :
										foreach ( $tab['videos'] as $v_id => $vid ) :
											$v_id_clean = esc_attr( (string) $v_id );
											?>
											<div class="tabby-video-row" data-video-id="<?php echo $v_id_clean; ?>">
												<button type="button" class="tabby-remove-node" title="Remove">×</button>
												<label><strong>Video Title</strong></label>
												<input type="text"
													name="tabby_data[<?php echo $id_clean; ?>][videos][<?php echo $v_id_clean; ?>][v_title]"
													value="<?php echo esc_attr( $vid['v_title'] ?? '' ); ?>"
													placeholder="e.g. Product Showcase"
													class="widefat">
												<label><strong>Embed URL (YouTube/Vimeo)</strong></label>
												<input type="text"
													name="tabby_data[<?php echo $id_clean; ?>][videos][<?php echo $v_id_clean; ?>][v_embed]"
													value="<?php echo esc_attr( $vid['v_embed'] ?? '' ); ?>"
													placeholder="https://www.youtube.com/watch?v=..."
													class="widefat">
											</div>
										<?php
										endforeach;
									endif;
									?>
								</div>
								<button type="button" class="button tabby-add-video-row" style="margin-top:10px;">+ Add Video Link</button>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {

			function initSort() {
				if ($("#tabby-tabs-list").data("ui-sortable")) $("#tabby-tabs-list").sortable("destroy");
				$("#tabby-tabs-list").sortable({ handle: ".tabby-tab-header", placeholder: "ui-sortable-placeholder" });

				$(".tabby-sortable-items").each(function(){
					var $el = $(this);
					if ($el.data("ui-sortable")) $el.sortable("destroy");
					$el.sortable({ placeholder: "ui-sortable-placeholder", tolerance: "pointer" });
				});
			}
			initSort();

			$(document).on('click', '.tabby-add-tab', function(e) {
				e.preventDefault();
				var newId = 'row_' + Date.now();

				var html =
					'<div class="tabby-tab-row postbox" data-id="'+newId+'">' +
						'<div class="tabby-tab-header">' +
							'<span class="dashicons dashicons-arrow-down-alt2 tabby-toggle-row" title="Collapse/Expand"></span>' +
							'<input type="text" name="tabby_data['+newId+'][title]" placeholder="New Tab Name" style="flex-grow:1; font-weight:bold;">' +
							'<select name="tabby_data['+newId+'][layout]" class="tabby-layout-selector">' +
								'<option value="editor">TEXT EDITOR</option>' +
								'<option value="grid">PDF/IMAGE RESOURCES</option>' +
								'<option value="video">VIDEO EMBED</option>' +
							'</select>' +
							'<button type="button" class="button-link-delete tabby-remove-tab" style="color:#a00;">Remove</button>' +
						'</div>' +
						'<div class="tabby-tab-body">' +
							'<div class="view-editor"><textarea name="tabby_data['+newId+'][content]" class="widefat" rows="8"></textarea></div>' +
							'<div class="view-grid tabby-hidden"><button type="button" class="button tabby-batch-upload">Batch Image/PDF Upload</button><div class="tabby-admin-grid tabby-sortable-items"></div></div>' +
							'<div class="view-video tabby-hidden"><div class="tabby-video-list"></div><button type="button" class="button tabby-add-video-row" style="margin-top:10px;">+ Add Video Link</button></div>' +
						'</div>' +
					'</div>';

				$('#tabby-tabs-list').append(html);
				initSort();
			});

			$(document).on('change', '.tabby-layout-selector', function() {
				var row = $(this).closest('.tabby-tab-row');
				row.find('.view-editor, .view-grid, .view-video').addClass('tabby-hidden');
				row.find('.view-' + $(this).val()).removeClass('tabby-hidden');
				initSort();
			});

			$(document).on('click', '.tabby-add-video-row', function(e) {
				e.preventDefault();
				var list = $(this).siblings('.tabby-video-list');
				var tabId = $(this).closest('.tabby-tab-row').data('id');
				var vId = 'vid_' + Math.random().toString(36).substr(2, 9);

				list.append(
					'<div class="tabby-video-row" data-video-id="'+vId+'">' +
						'<button type="button" class="tabby-remove-node" title="Remove">×</button>' +
						'<label><strong>Video Title</strong></label>' +
						'<input type="text" name="tabby_data['+tabId+'][videos]['+vId+'][v_title]" placeholder="e.g. Video Title" class="widefat">' +
						'<label><strong>Embed URL</strong></label>' +
						'<input type="text" name="tabby_data['+tabId+'][videos]['+vId+'][v_embed]" placeholder="URL here..." class="widefat">' +
					'</div>'
				);
			});

			$(document).on('click', '.tabby-batch-upload', function(e) {
				e.preventDefault();

				var grid  = $(this).closest('.view-grid').find('.tabby-admin-grid');
				var tabId = $(this).closest('.tabby-tab-row').data('id');

				var frame = wp.media({ title: 'Select Resources', multiple: true }).open();

				frame.on('select', function() {
					frame.state().get('selection').each(function(a) {
						var d = a.toJSON();
						var t = (d.sizes && d.sizes.thumbnail) ? d.sizes.thumbnail.url : (d.icon || d.url);
						var itId = 'item_' + Math.random().toString(36).substr(2, 9);
						var title = (d.title && d.title.length) ? d.title : 'Resource';

						grid.append(
							'<div class="tabby-admin-item" data-item-id="'+itId+'">' +
								'<button type="button" class="tabby-remove-node" title="Remove">×</button>' +
								'<img src="'+t+'" alt="">' +
								'<input type="text" class="tabby-resource-label-input" name="tabby_data['+tabId+'][items]['+itId+'][label]" value="'+$('<div/>').text(title).html()+'">' +
								'<input type="hidden" name="tabby_data['+tabId+'][items]['+itId+'][url]" value="'+d.url+'">' +
								'<input type="hidden" name="tabby_data['+tabId+'][items]['+itId+'][thumb]" value="'+t+'">' +
							'</div>'
						);
					});
					initSort();
				});
			});

			$(document).on('click', '.tabby-remove-tab', function(e) {
				e.preventDefault();
				if (confirm("Remove this tab?")) $(this).closest('.tabby-tab-row').remove();
			});

			$(document).on('click', '.tabby-remove-node', function(e) {
				e.preventDefault();
				if (confirm("Remove this item?")) $(this).closest('.tabby-admin-item, .tabby-video-row').remove();
			});

			$(document).on('click', '.tabby-toggle-row', function(e) {
				e.preventDefault();
				$(this).closest('.tabby-tab-row').toggleClass('tabby-collapsed');
			});

			$(document).on('click', '.tabby-expand-all', function(e){
				e.preventDefault();
				$('.tabby-tab-row').removeClass('tabby-collapsed');
			});

			$(document).on('click', '.tabby-collapse-all', function(e){
				e.preventDefault();
				$('.tabby-tab-row').addClass('tabby-collapsed');
			});
		});
		</script>
		<?php
	} catch ( Throwable $e ) {
		echo '<div class="notice notice-error"><p><strong>TABBY:</strong> The tab builder encountered an error and was temporarily disabled on this screen.</p></div>';
	}
}

/**
 * -----------------------------------------------------------------------------
 * MODULE 2: SAVE DATA (hardened + "never wipe if missing")
 * -----------------------------------------------------------------------------
 */
add_action( 'save_post_product', function( $post_id ) {
	if ( ! isset( $_POST['tabby_nonce_field'] ) || ! wp_verify_nonce( $_POST['tabby_nonce_field'], 'tabby_save_action' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( function_exists( 'wp_is_post_revision' ) && wp_is_post_revision( $post_id ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	// Do not delete/wipe if metabox didn't post.
	if ( ! isset( $_POST['tabby_data'] ) || ! is_array( $_POST['tabby_data'] ) ) return;

	$clean = [];

	foreach ( $_POST['tabby_data'] as $row_id => $tab ) {
		if ( ! is_array( $tab ) ) continue;

		$row_id = sanitize_key( (string) $row_id );

		$layout = isset( $tab['layout'] ) ? sanitize_key( (string) $tab['layout'] ) : 'editor';
		if ( ! in_array( $layout, [ 'editor', 'grid', 'video' ], true ) ) $layout = 'editor';

		$clean[ $row_id ] = [
			'title'   => isset( $tab['title'] ) ? sanitize_text_field( (string) $tab['title'] ) : '',
			'layout'  => $layout,
			'content' => isset( $tab['content'] ) ? wp_kses_post( (string) $tab['content'] ) : '',
		];

		if ( ! empty( $tab['items'] ) && is_array( $tab['items'] ) ) {
			$items = [];
			foreach ( $tab['items'] as $it_id => $item ) {
				if ( ! is_array( $item ) ) continue;
				$it_id = sanitize_key( (string) $it_id );

				$items[ $it_id ] = [
					'label' => isset( $item['label'] ) ? sanitize_text_field( (string) $item['label'] ) : '',
					'url'   => isset( $item['url'] ) ? esc_url_raw( (string) $item['url'] ) : '',
					'thumb' => isset( $item['thumb'] ) ? esc_url_raw( (string) $item['thumb'] ) : '',
				];
			}
			if ( ! empty( $items ) ) $clean[ $row_id ]['items'] = $items;
		}

		if ( ! empty( $tab['videos'] ) && is_array( $tab['videos'] ) ) {
			$videos = [];
			foreach ( $tab['videos'] as $v_id => $vid ) {
				if ( ! is_array( $vid ) ) continue;
				$v_id = sanitize_key( (string) $v_id );

				$videos[ $v_id ] = [
					'v_title' => isset( $vid['v_title'] ) ? sanitize_text_field( (string) $vid['v_title'] ) : '',
					'v_embed' => isset( $vid['v_embed'] ) ? esc_url_raw( (string) $vid['v_embed'] ) : '',
				];
			}
			if ( ! empty( $videos ) ) $clean[ $row_id ]['videos'] = $videos;
		}
	}

	update_post_meta( $post_id, TABBY_KEY, $clean );
} );

/**
 * -----------------------------------------------------------------------------
 * MODULE 3: FRONTEND RENDERING (guarded)
 * -----------------------------------------------------------------------------
 */
add_filter( 'woocommerce_product_tabs', function( $tabs ) {
	if ( ! tabby_v199_is_woocommerce_active() ) return $tabs;

	try {
		global $product, $post;

		$product_id = 0;
		if ( is_object( $product ) && method_exists( $product, 'get_id' ) ) {
			$product_id = (int) $product->get_id();
		} elseif ( is_object( $post ) && ! empty( $post->ID ) ) {
			$product_id = (int) $post->ID;
		}
		if ( $product_id <= 0 ) return $tabs;

		$data = get_post_meta( $product_id, TABBY_KEY, true );
		if ( ! is_array( $data ) || empty( $data ) ) return $tabs;

		$i = 0;
		foreach ( $data as $id => $tab ) {
			if ( ! is_array( $tab ) ) continue;

			$title = isset( $tab['title'] ) ? trim( (string) $tab['title'] ) : '';
			if ( $title === '' ) $title = 'Resources';

			$tabs[ 'tabby_' . sanitize_key( (string) $id ) ] = [
				'title'    => esc_html( $title ),
				'priority' => 50 + ( $i * 5 ),
				'callback' => 'tabby_v199_render_tab_content',
				'tab_data' => $tab,
			];
			$i++;
		}

		return $tabs;
	} catch ( Throwable $e ) {
		return $tabs;
	}
}, 20 );

function tabby_v199_render_tab_content( $key, $tab_info ) {
	try {
		$tab = ( isset( $tab_info['tab_data'] ) && is_array( $tab_info['tab_data'] ) ) ? $tab_info['tab_data'] : [];
		$layout = isset( $tab['layout'] ) ? (string) $tab['layout'] : 'editor';

		echo '<div class="tabby-frontend tabby-scope tabby-frontend-scope">';

		if ( $layout === 'grid' && ! empty( $tab['items'] ) && is_array( $tab['items'] ) ) {
			echo '<div class="tabby-grid">';
			foreach ( $tab['items'] as $item ) {
				if ( ! is_array( $item ) ) continue;

				$url   = isset( $item['url'] ) ? esc_url( (string) $item['url'] ) : '';
				$thumb = isset( $item['thumb'] ) ? esc_url( (string) $item['thumb'] ) : '';
				$label = isset( $item['label'] ) ? trim( (string) $item['label'] ) : '';
				if ( $label === '' ) $label = 'Resource';
				if ( $url === '' ) continue;

				echo '<div class="tabby-item">';
				echo '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">';
				if ( $thumb !== '' ) echo '<img src="' . $thumb . '" class="tabby-img" alt="">';
				echo '<span class="tabby-label">' . esc_html( $label ) . '</span>';
				echo '</a>';
				echo '</div>';
			}
			echo '</div>';

		} elseif ( $layout === 'video' && ! empty( $tab['videos'] ) && is_array( $tab['videos'] ) ) {

			foreach ( $tab['videos'] as $vid ) {
				if ( ! is_array( $vid ) ) continue;
				if ( empty( $vid['v_embed'] ) ) continue;

				$raw_url   = (string) $vid['v_embed'];
				$embed_url = $raw_url;

				if ( preg_match( '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $raw_url, $match ) ) {
					$embed_url = 'https://www.youtube.com/embed/' . $match[1];
				} elseif ( preg_match( '%vimeo\.com/(?:channels/(?:\w+/)?|groups/(?:[^\/]*)/videos/|album/(?:\d+)/video/|video/|)(\d+)(?:$|/|\?)%i', $raw_url, $match ) ) {
					$embed_url = 'https://player.vimeo.com/video/' . $match[1];
				}

				echo '<div class="tabby-video-wrap">';
				if ( ! empty( $vid['v_title'] ) ) echo '<h3 class="tabby-video-title">' . esc_html( (string) $vid['v_title'] ) . '</h3>';
				echo '<div class="tabby-video-frame"><iframe src="' . esc_url( $embed_url ) . '" allowfullscreen loading="lazy"></iframe></div>';
				echo '</div>';
			}

		} else {
			echo apply_filters( 'the_content', $tab['content'] ?? '' );
		}

		echo '</div>';
	} catch ( Throwable $e ) {
		echo '<!-- TABBY render error suppressed -->';
	}
}
