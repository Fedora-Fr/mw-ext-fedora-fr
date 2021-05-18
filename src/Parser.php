<?php

/**
 * A parser extension for Fedora-Fr
 *
 * @ingroup Extensions
 *
 * @author Guillaume Kulakowski <guillaume@kulakowski.fr>
 * @license GPL-2.0-or-later
 */

namespace FedoraFr;

use MediaWiki\MediaWikiServices;
use Parser as mwParser;
use PPFrame;

class Parser {

	/**
	 * Register any render callbacks with the parser
	 *
	 * @param Parser $parser MediaWiki Parser.
	 */
	public static function onParserFirstCallInit( mwParser $parser ) {
		$parser->setHook( 'path', [ self::class, 'renderPath' ] );
		$parser->setHook( 'chemin', [ self::class, 'renderPath' ] );
		$parser->setHook( 'key', [ self::class, 'renderKey' ] );
		$parser->setHook( 'touche', [ self::class, 'renderKey' ] );
		$parser->setHook( 'app', [ self::class, 'renderApp' ] );
		$parser->setHook( 'packet', [ self::class, 'renderPacket' ] );
		$parser->setHook( 'paquet', [ self::class, 'renderPacket' ] );
		$parser->setHook( 'menu', [ self::class, 'renderMenu' ] );
		$parser->setHook( 'cmd', [ self::class, 'renderCmd' ] );
		$parser->setHook( 'envrac', [ self::class, 'renderEnVrac' ] );
	}

	/**
	 * path tag render.
	 *
	 * @code <path>/var/log/messages</path>
	 *
	 * @param ?string $text String to parse.
	 * @param array $argv Argument passed to the render.
	 * @param mwParser $parser MediaWiki Parser.
	 * @param PPFrame $frame MediaWiki PPFrame.
	 * @return String String parsed.
	 */
	public static function renderPath( ?string $text, array $argv, mwParser $parser, PPFrame $frame ) {
		return '<tt>' . htmlspecialchars( $text ) . '</tt>';
	}

	/**
	 * touche tag render.
	 *
	 * @code <touche>CTRL + M</touche>
	 *
	 * @param ?string $text String to parse.
	 * @param array $argv Argument passed to the render.
	 * @param mwParser $parser MediaWiki Parser.
	 * @param PPFrame $frame MediaWiki PPFrame.
	 * @return String String parsed.
	 */
	public static function renderKey( ?string $text, array $argv, mwParser $parser, PPFrame $frame ) {
		return '<strong>[' . htmlspecialchars( $text ) . ']</strong>';
	}

	/**
	 * app tag render.
	 *
	 * @code <app>Firefox</app>
	 *
	 * @param ?string $text String to parse.
	 * @param array $argv Argument passed to the render.
	 * @param mwParser $parser MediaWiki Parser.
	 * @param PPFrame $frame MediaWiki PPFrame.
	 * @return String String parsed.
	 */
	public static function renderApp( ?string $text, array $argv, mwParser $parser, PPFrame $frame ) {
		return '<strong>' . htmlspecialchars( $text ) . '</strong>';
	}

	/**
	 * paquet tag render.
	 *
	 * @code <paquet>firefox-1.5.0.7</paquet>
	 *
	 * @param ?string $text String to parse.
	 * @param array $argv Argument passed to the render.
	 * @param mwParser $parser MediaWiki Parser.
	 * @param PPFrame $frame MediaWiki PPFrame.
	 * @return String String parsed.
	 */
	public static function renderPacket( ?string $text, array $argv, mwParser $parser, PPFrame $frame ) {
		return '<tt>' . htmlspecialchars( $text ) . '</tt>';
	}

	/**
	 * menu tag render.
	 *
	 * @code <menu>Item</menu>
	 *
	 * @param ?string $text String to parse.
	 * @param array $argv Argument passed to the render.
	 * @param mwParser $parser MediaWiki Parser.
	 * @param PPFrame $frame MediaWiki PPFrame.
	 * @return String String parsed.
	 */
	public static function renderMenu( ?string $text, array $argv, mwParser $parser, PPFrame $frame ) {
		return '« <em>' . htmlspecialchars( preg_replace( '/[-]+>/u', '→', $text ) ) . '</em> »';
	}

/**
 * cmd tag render.
 *
 * @code <cmd>dnf update</cmd>
 *
 * @param ?string $text String to parse.
 * @param array $argv Argument passed to the render.
 * @param mwParser $parser MediaWiki Parser.
 * @param PPFrame $frame MediaWiki PPFrame.
 * @return String String parsed.
 */
	public static function renderCmd( ?string $text, array $argv, mwParser $parser, PPFrame $frame ) {
		return '<tt>' . htmlspecialchars( $text ) . '</tt>';
	}

	/**
	 * envrac tag. Show list of x random articles of one or more categories.
	 *
	 * @code <envrac nbre="10" categories="a,b" />
	 *
	 * @param ?string $text String to parse.
	 * @param array $argv Argument passed to the render.
	 * @param mwParser $parser MediaWiki Parser.
	 * @param PPFrame $frame MediaWiki PPFrame.
	 * @return String String parsed.
	 */
	public static function renderEnVrac( ?string $text, array $argv, mwParser $parser, PPFrame $frame ) {
		if ( empty( $args['nbre'] ) ) {
			$args['nbre'] = 10;
		} else {
			$args['nbre'] += 0;
		}

		if ( !empty( $args['categories'] ) ) {
			$categories = explode( ',', $args['categories'] );
			foreach ( $categories as $acategorie ) {
				if ( substr( $acategorie, 0, 1 ) == '-' ) {
					$catMinus[] = ucfirst( substr( $acategorie, 0, 1 ) );
				} else {
					$catPlus[] = ucfirst( $acategorie );
				}
			}
		}

		$whereClauseArray = [];
		if ( !empty( $catMinus ) ) {
			$whereClauseArray[] = " cl_to NOT IN ( '" . implode( "','", $catMinus ) . "' ) ";
		}
		if ( !empty( $catPlus ) ) {
			$whereClauseArray[] = " cl_to IN ('" . implode( "','", $catPlus ) . "' ) ";
		}

		$whereClauseArray[] = 'cl_from = page_id';
		$lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$dbr = $lb->getConnection( DB_REPLICA );
		$res = $dbr->select(
			[ 'page', 'categorylinks' ],
			[ 'page_title' ],
			$whereClauseArray,
			__METHOD__,
			[
				'ORDER BY' => 'RAND()',
				'LIMIT' => "${args['nbre']}"
			]
		);

		$list = [];
		if ( $res ) {
			foreach ( $res as $row ) {
				$list[] = '* [[' . $row->page_title . ']]';
			}
		} else {
			die( 'Oups, erreur de la requète' );
		}
		$output = $parser->recursiveTagParse( implode( "\n", $list ) );

		return $output;
	}
}
