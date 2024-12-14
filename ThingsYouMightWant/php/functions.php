<?php

/**
 * Timber starter-theme
 * https://github.com/timber/starter-theme
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.1
 */

use Timber\{Timber, Site, Menu};

/**
 * If you are installing Timber as a Composer dependency in your theme, you'll need this block
 * to load your dependencies and initialize Timber. If you are using Timber via the WordPress.org
 * plug-in, you can safely delete this block.
 */
$composer_autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($composer_autoload)) {
	require_once $composer_autoload;
	$timber = new Timber();
}

/**
 * This ensures that Timber is loaded and available as a PHP class.
 * If not, it gives an error message to help direct developers on where to activate
 */
if (!class_exists('Timber')) {

	add_action(
		'admin_notices',
		function () {
			echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . esc_url(admin_url('plugins.php#timber')) . '">' . esc_url(admin_url('plugins.php')) . '</a></p></div>';
		}
	);

	add_filter(
		'template_include',
		function ($template) {
			return get_stylesheet_directory() . '/static/no-timber.html';
		}
	);
	return;
}

/**
 * Sets the directories (inside your theme) to find .twig files
 */
Timber::$dirname = array('templates');

/**
 * By default, Timber does NOT autoescape values. Want to enable Twig's autoescape?
 * No prob! Just set this value to true
 */
Timber::$autoescape = false;

$script_handle_types = [];
add_filter('script_loader_tag', function ($tag, $handle, $src) {
	global $script_handle_types;

	if (array_key_exists($handle, $script_handle_types)) {
		$type = $script_handle_types[$handle];
		return "<script type='$type' src='$src' id='{$handle}-js'></script>\n";
	}

	return $tag;
}, 10, 3);

// Custom enqueue methods that use filemtime for the version
function mtime_enqueue_script($handle, $relpath = null, $deps = [], $in_footer = false, $type = 'text/application') {
	if ($relpath === null) {
		wp_enqueue_script($handle);
	} else {
		if ($type !== 'text/application') {
			global $script_handle_types;
			$script_handle_types[$handle] = $type;
		}

		wp_enqueue_script($handle, get_theme_file_uri($relpath), $deps, filemtime(get_theme_file_path($relpath)), $in_footer);
	}
}

function mtime_register_script($handle, $relpath, $deps = [], $in_footer = false, $type = 'text/application') {
	if ($type !== 'text/application') {
		global $script_handle_types;
		$script_handle_types[$handle] = $type;
	}

	wp_register_script($handle, get_theme_file_uri($relpath), $deps, filemtime(get_theme_file_path($relpath)), $in_footer);
}

function mtime_enqueue_style($handle, $relpath = null, $deps = [], $media = 'all') {
	if ($relpath === null) {
		wp_enqueue_style($handle);
	} else {
		wp_enqueue_style($handle, get_theme_file_uri($relpath), $deps, filemtime(get_theme_file_path($relpath)), $media);
	}
}


function mtime_register_style($handle, $relpath, $deps = [], $media = 'all') {
	wp_register_style($handle, get_theme_file_uri($relpath), $deps, filemtime(get_theme_file_path($relpath)), $media);

}

function register_script_data(string $handle, string $globalName, callable $getData) {
	add_action('wp_print_scripts', function () use ($handle, $globalName, $getData) {
		$wp_scripts = wp_scripts();
		if (!in_array($handle, $wp_scripts->queue))
			return;

		$data = json_encode($getData());
		wp_add_inline_script($handle, "window.$globalName = $data", 'before');
	});
}

function register_vite_app($handle, $relpath) {
	$path = __DIR__ . '/' . $relpath;
	$manifest = json_decode(file_get_contents($path . '/.vite/manifest.json'), true);

	wp_enqueue_script($handle, get_theme_file_uri($relpath) . '/' . $manifest['index.html']['file'], [], null, true);

	foreach ($manifest['index.html']['css'] as $cssPath) {
		wp_enqueue_style($handle, get_theme_file_uri($relpath) . '/' . $cssPath);
	}
}

/**
 * We're going to configure our theme inside of a subclass of Timber\Site
 * You can move this to its own file and include here via php's include("MySite.php")
 */
class StarterSite extends Site {
	/** Add timber support. */
	public function __construct() {
		add_action('after_setup_theme', array($this, 'theme_supports'));
		add_filter('image_size_names_choose', [$this, 'custom_image_size_names']);
		add_filter('timber/context', array($this, 'add_to_context'));
		add_filter('timber/twig', array($this, 'add_to_twig'));
		add_action('init', array($this, 'register_post_types'));
		add_action('init', array($this, 'register_taxonomies'));
		add_action('wp_enqueue_scripts', array($this, 'load_scripts_styles'));
		add_action('after_setup_theme', array($this, 'register_display_locations'));
		parent::__construct();
	}
	/** This is where you can register custom post types. */
	function reading_time($post_ID) {
		$content = get_post_field( 'post_content', $post_ID );
		$word_count = str_word_count( strip_tags( $content ) );
		$readingtime = ceil($word_count / 200);

		
		$timer = " min read";
		
	
		
		$totalreadingtime = $readingtime . $timer;

		return $totalreadingtime;
	}
	public function register_post_types() {
		register_post_type(
			'faq',
			array(
				'labels'      => array(
					'name'          => 'FAQs',
					'singular_name' => 'FAQ',
					'add_new'  			=> 'Add New FAQ',
					'add_new_item'  => 'Add New FAQ',
					'edit_item'     => 'Edit FAQ',
					'new_item'      => 'New FAQ',
					'view_item'     => 'View FAQ',
					'view_items'    => 'View FAQs',
					'search_items'  => 'Search FAQs',
					'not_found'     => 'No FAQs found',
					'not_found_in_trash'    => 'No FAQs found in trash',
					'all_items'     => 'All FAQs',
				),
				'public'      		=> true,
				'query_var' 			=> false,
				'has_archive' 		=> false,
				'menu_icon'     	=> 'dashicons-open-folder',
				'show_in_rest' 		=> true,
				'supports' 				=> array('title', 'revisions', 'excerpt'),
				'menu_position' 	=> 22,
			)
		);
		register_post_type(
			'testimonial',
			array(
				'labels'      => array(
					'name'          => 'Testimonials',
					'singular_name' => 'Testimonial',
					'add_new'  			=> 'Add New Testimonial',
					'add_new_item'  => 'Add New Testimonial',
					'edit_item'     => 'Edit Testimonial',
					'new_item'      => 'New Testimonial',
					'view_item'     => 'View Testimonial',
					'view_items'    => 'View Testimonials',
					'search_items'  => 'Search Testimonials',
					'not_found'     => 'No Testimonials found',
					'not_found_in_trash'    => 'No Testimonials found in trash',
					'all_items'     => 'All Testimonials',
				),
				'public'      		=> true,
				'query_var' 			=> false,
				'has_archive' 		=> false,
				'menu_icon'     	=> 'dashicons-testimonial',
				'show_in_rest' 		=> true,
				'supports' 				=> array('title', 'revisions'),
				'menu_position' 	=> 22,
			)
		);
		register_post_type(
			'members',
			array(
				'labels'      => array(
					'name'          => 'Team Members',
					'singular_name' => 'members',
					'add_new'  			=> 'Add New members',
					'add_new_item'  => 'Add New members',
					'edit_item'     => 'Edit members',
					'new_item'      => 'New members',
					'view_item'     => 'View members',
					'view_items'    => 'View members',
					'search_items'  => 'Search members',
					'not_found'     => 'No memberss found',
					'not_found_in_trash'    => 'No memberss found in trash',
					'all_items'     => 'All members',
				),
				'public'      		=> true,
				'query_var' 			=> false,
				'has_archive' 		=> false,
				'menu_icon'     	=> 'dashicons-members',
				'show_in_rest' 		=> true,
				'supports' 				=> array('title', 'revisions'),
				'menu_position' 	=> 22,
			)
		);
	}
	/** This is where you can register custom taxonomies. */
	public function register_taxonomies() {
		register_taxonomy(
			'faq-category',
			['faq'],
			array(
				'labels' => array(
					'name' => 'FAQ Categories',
					'singular_name' => 'Category',
				),
				'show_in_rest' => true,
				'show_in_nav_menus' => true,
				'show_ui' => true,
				'hierarchical' => true,
			)
		);
	}
	public function disable_gutenberg($use_block_editor, $post_type)
	{
		$post_types = ['testimonial', 'members'];
		if (in_array($post_type, $post_types)) {
				$use_block_editor = false;
		}
		return $use_block_editor;
	}
	
	public function load_scripts_styles() {
		wp_enqueue_script('modernizr-js', get_template_directory_uri() . '/assets/js/modernizr.js', [], '1.0.0');
		
		mtime_enqueue_script('custom-js', 'assets/js/index.js', [], true);
		wp_register_style('keen-slider', "https://cdn.jsdelivr.net/npm/keen-slider@latest/keen-slider.min.css");
		wp_register_script('keen-slider', "https://cdn.jsdelivr.net/npm/keen-slider@latest/keen-slider.js");
		wp_register_script('cloudflare-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js');
		// wp_register_script('stickymate', "https://cdn.jsdelivr.net/npm/keen-slider@latest/stickymate.js");

		mtime_enqueue_style('aos', 'assets/css/aos.css');
		mtime_enqueue_style('global', 'assets/css/global.css');
		// mtime_register_style('hero', 'assets/css/modules/hero.css');
		
	}
	public function register_display_locations() {
		register_nav_menus(array(
			'header' => 'Header',
			'footer' => 'Footer'
		));
	}
	/** This is where you add some context
	 *
	 * @param string $context context['this'] Being the Twig's {{ this }}.
	 */
	public function add_to_context($context) {
		$context['header_menu'] = new Menu('header');
		$context['footer_menu'] = new Menu('footer');
		$context['main_logo'] = get_field('main_logo', 'options');
		$context['footer_logo'] = get_field('footer_logo', 'options');
		$context['site'] = $this;
		return $context;
	}

	public function render_file(string $relPath) {

		  $file = file_get_contents(__DIR__ . $relPath);
			if ($file) {
				echo $file;
			}
	}

	public function theme_supports() {
		// Add excerpt support for pages
    add_post_type_support('page', 'excerpt');
		// Add default posts and comments RSS feed links to head.
		add_theme_support('automatic-feed-links');

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support('title-tag');

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support('post-thumbnails');

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support(
			'html5',
			array(
				// 'comment-form',
				// 'comment-list',
				'gallery',
				'caption',
			)
		);

		/*
		 * Enable support for Post Formats.
		 *
		 * See: https://codex.wordpress.org/Post_Formats
		 */
		add_theme_support(
			'post-formats',
			array(
				'aside',
				'image',
				'video',
				'quote',
				'link',
				'gallery',
				'audio',
			)
		);

		add_theme_support('menus');

		add_image_size('large-mobile', 600);
		add_image_size('small-mobile', 320);
		if (function_exists('acf_add_options_page')) {
			acf_add_options_page(array(
				'page_title'    => 'Global Options',
				'menu_title'    => 'Global Options',
				'menu_slug'     => 'options',
				'capability'    => 'edit_posts',
				'redirect'      => false
			));
			acf_add_options_page(array(
				'page_title'    => '404 Page',
				'menu_title'    => '404 Page',
				'menu_slug'     => '404_page',
				'capability'    => 'edit_posts',
				'redirect'      => false
			));
		}

	}

	function custom_image_size_names($sizes) {
		return array_merge($sizes, [
			'large-mobile' => 'Large Mobile',
			'small-mobile' => 'Small Mobile'
		]);
	}

	/** This is where you can add your own functions to twig.
	 *
	 * @param Twig\Environment $twig get extension.
	 */
	

	public function add_to_twig(Twig\Environment $twig) {
		$twig->addExtension(new Twig\Extension\StringLoaderExtension());
		$twig->addFunction(new Twig\TwigFunction('get_field', 'get_field'));
		$twig->addFunction(new Twig\TwigFunction('enqueue_style', 'mtime_enqueue_style'));
		$twig->addFunction(new Twig\TwigFunction('enqueue_script', 'mtime_enqueue_script'));
		$twig->addFunction(new Twig\TwigFunction('render_file', [$this, 'render_file']));
		$twig->addFunction(new Twig\TwigFunction('reading_time', array($this, 'reading_time')));
		$twig->addFunction(new Twig\TwigFunction('get_categories', 'get_categories'));
		$twig->addFunction(new Twig\TwigFunction('get_privacy_policy_url', 'get_privacy_policy_url'));
		$twig->addFunction(new Twig\TwigFunction('getForm', function (string $name) {
			if (!function_exists('getFormByName'))
				return null;

			return getFormByName($name);
		}));
		$twig->addFunction(new Twig\TwigFunction('getFAQs', function (int $count = 4) {
			return get_posts([ 'post_type' => 'faq', 'numberposts' => $count ]);
		}));
		$twig->addFunction(new Twig\TwigFunction('getFaqCategories', function () {
			// return get_posts([ 'post_type' => 'faq', 'numberposts' => $count ]);
			return get_terms(['taxonomy' => 'faq-category']);
		}));
		return $twig;
	}
}

new StarterSite();

add_filter('comments_open', function ($open, $post_id) {
	$post_type = get_post_type($post_id);
	if ($post_type == 'attachment') {
		return false;
	}
	return $open;
}, 10, 2);
