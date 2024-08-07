<?php
/**
 * Custom RSS2 Feed Template for displaying RSS2 Posts feed.
 */

header( 'Content-Type: ' . feed_content_type( 'rss2' ) . '; charset=' . get_option( 'blog_charset' ), true );

$header = '<?xml version="1.0" encoding="' . get_option( 'blog_charset' ) . '"?' . '>';

$header .= '
<rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
>';

$header .= '<channel>
	<title>'.get_wp_title_rss().'</title>
	<atom:link href="'.get_self_link().'" rel="self" type="application/rss+xml" />
	<link>'.get_bloginfo_rss( 'url' ).'</link>
	<description>'.get_bloginfo_rss( 'description' ).'</description>
	<lastBuildDate>'.get_feed_build_date( 'r' ).'</lastBuildDate>
	<language>'.get_bloginfo_rss( 'language' ).'</language>
	<sy:updatePeriod>hourly</sy:updatePeriod>
	<sy:updateFrequency>1</sy:updateFrequency>
';

$rss_item = '';
while ( have_posts() ) :
	the_post();

	$rss_item .= '<item>
		<title>'.get_the_title_rss().'</title>
		<link>'.get_permalink().'</link>
	';

	if ( get_comments_number() || comments_open() ) {
		$rss_item .= '<comments>'.get_comments_link().'</comments>';
	}

	$rss_item .= '<dc:creator><![CDATA['.get_the_author().']]></dc:creator>
		<pubDate>'.mysql2date( 'D, d M Y H:i:s +0000', get_post_time( 'Y-m-d H:i:s', true ), false ).'</pubDate>
		'.get_the_category_rss( 'rss2' ).'
		<guid isPermaLink="false">'.get_the_guid().'</guid>';

	if ( get_option( 'rss_use_excerpt' ) ) {
		$rss_item .= '<description><![CDATA['.get_the_excerpt().']]></description>';
	} else {
		$rss_item .= '<description><![CDATA['.get_the_excerpt().']]></description>';
		$content = get_the_content_feed( 'rss2' );
		if ( strlen( $content ) > 0 ) {
			$rss_item .= '<content:encoded><![CDATA['.$content.']]></content:encoded>';
		} else {
			$rss_item .= '<content:encoded><![CDATA['.get_the_excerpt().']]></content:encoded>';
		}
	}

	if ( get_comments_number() || comments_open() ) {
		$rss_item .= '
			<wfw:commentRss>'.esc_url( get_post_comments_feed_link( null, 'rss2' ) ).'</wfw:commentRss>
			<slash:comments>'.get_comments_number().'</slash:comments>
		';
	}
	$rss_item .= '</item>';
endwhile;

$footer = '</channel></rss>';

$rss_content = $header . $rss_item . $footer;

global $wpdb;
$appTable = $wpdb->prefix . "kpe_rss_snippet";
$query = $wpdb->prepare("SELECT * FROM $appTable order by position_in_feed");
$applications = $wpdb->get_results($query);

foreach ( $applications as $application ) {
	$snippet = '
		<item>
			<title>'.$application->snippet_title.'</title>
			<link>'.$application->snippet_url.'</link>
			<pubDate>'.mysql2date( 'D, d M Y H:i:s +0000', $application->date_created ).'</pubDate>
			<guid isPermaLink="false">'.$application->snippet_url.'</guid>
			<description><![CDATA['.$application->snippet_summary.']]></description>
		</item>
	';
	$strpostoinsert = kpe_findNthOccurrencePosition($rss_content, '<item>', $application->position_in_feed);
	if ($strpostoinsert == false) $strpostoinsert = kpe_findNthOccurrencePosition($rss_content, '<item>', 1);
	
	if (
		( ($application->feed == 'Global') && (!is_category()) && (!is_tag()) ) 
	) {
		$rss_content = substr_replace($rss_content, $snippet, $strpostoinsert, 0);
	} elseif ( ($application->feed == 'Category') && (is_category()) ) {
		$category_ids = wp_get_post_categories(get_the_ID());
		if (in_array($application->category_tag, $category_ids)) {
			$rss_content = substr_replace($rss_content, $snippet, $strpostoinsert, 0);
		}
	} elseif ( ($application->feed == 'Tag') && (is_tag()) ) {
		$tag_ids = wp_get_post_tags(get_the_ID(), array('fields' => 'ids'));
		if (in_array($application->category_tag, $tag_ids)) {
			$rss_content = substr_replace($rss_content, $snippet, $strpostoinsert, 0);
		}
	}
}

echo $rss_content;
