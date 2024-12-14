<?php

$templates = array( 'archive.twig', 'index.twig' );
$context = Timber::context();
$timber_post = new Timber\Post();
$context['post'] = $timber_post;

$postNumber = wp_count_posts('post') -> publish;

	if ($postNumber < 6 || $postNumber == 7) {
			$query = [
			'post_type' => 'post',
			'posts_per_page' => 1,
		];
		$context['featured_posts'] = new Timber\PostQuery($query);

	}
	elseif ($postNumber == 6 || $postNumber == 8) {
	 $query = [
			'post_type' => 'post',
			'posts_per_page' => 2,
		];
		$context['featured_posts'] = new Timber\PostQuery($query);
	} else { 
		$query = [
			'post_type' => 'post',
			'posts_per_page' => 5,
		];
		$context['featured_posts'] = new Timber\PostQuery($query);
	}
	$excludeIDs = [];
	foreach ($context['featured_posts'] as $post) {
		 array_push($excludeIDs, $post -> ID);
	}
	$query = [
		'post_type' => 'post',
		'post__not_in' => $excludeIDs,
	];
	$catQuery = $_GET['category'];
	if ($catQuery && $catQuery != 'all') {
		$context['category'] = $catQuery;
		$query['cat'] = get_category_by_slug($catQuery)->term_id;
	}
	$context['posts'] = new Timber\PostQuery($query);
Timber::render( $templates, $context );
