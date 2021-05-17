<?php

/**
 * A parser extension for Fedora-Fr
 *
 * @ingroup Extensions
 *
 * @author Guillaume Kulakowski <guillaume@kulakowski.fr>
 * @license GPL-2.0
 */

namespace FedoraFr;

use Parser as mwParser;
use PPFrame;
use MediaWiki\MediaWikiServices;

class Parser {

  /**
   * Register any render callbacks with the parser
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
   * <path>/var/log/messages</path>
   */
  public static function renderPath( ?string $text, array $argv, mwParser $parser, PPFrame $frame ) {
    return '<tt>' . htmlspecialchars( $text ) . '</tt>';
  }

  /**
   * <touche>CTRL + M</touche>
   */
  public static function renderKey ( ?string $text, array $argv, mwParser $parser, PPFrame $frame ) {
    return '<strong>[' . htmlspecialchars( $text ) . ']</strong>';
  }

  /**
   * <app>Firefox</app>
   */
  public static function renderApp ( ?string $text, array $argv, mwParser $parser, PPFrame $frame ) {
    return '<strong>' . htmlspecialchars( $text ) . '</strong>';
  }

  /**
   * <paquet>firefox-1.5.0.7</paquet>
   */
  public static function renderPacket ( ?string $text, array $argv, mwParser $parser, PPFrame $frame ) {
    return '<tt>' . htmlspecialchars( $text ) . '</tt>';
  }

  /**
   * <menu>Item</menu>
   */
  public static function renderMenu ( ?string $text, array $argv, mwParser $parser, PPFrame $frame ) {
    return '« <em>' . htmlspecialchars( preg_replace('/[-]+>/u', '→', $text) ) . '</em> »';
  }

  /**
   * <cmd>dnf update</cmd>
   */
  public static function renderCmd ( ?string $text, array $argv, mwParser $parser, PPFrame $frame ) {
    return '<tt>' . htmlspecialchars( $text ) . '</tt>';
  }

  /**
   * envrac tag.
   * Show list of x random articles of one or more categories.
   * Example: <envrac nbre="10" categories="a,b" />
   */
  public static function renderEnVrac ( ?string $text, array $argv, mwParser $parser, PPFrame $frame ) {
    global $wgDBprefix;

    if ( empty( $args['nbre'] ) )
      $args['nbre'] = 10;
    else
      $args['nbre'] += 0;

    if ( !empty( $args['categories'] ) ) {
      $categories = explode( ',', $args['categories'] );
      foreach ( $categories as $acategorie )   {
        if ( substr( $acategorie, 0, 1) == '-' )
          $catMinus[] = mysql_escape_string( ucfirst( substr( $acategorie,0,1 ) ) );
        else
          $catPlus[] = mysql_escape_string( ucfirst( $acategorie ) );
      }
    }

    if ( !empty( $catMinus ) )
      $catMinus = " cl_to NOT IN ( '" . implode( "','", $catMinus ) . "' ) ";
    else
      $catMinus = '';

    if ( !empty($catPlus))
      $catPlus = " cl_to IN ('" . implode( "','", $catPlus ) . "' ) ";
    else
      $catPlus = '';

    if ( empty( $catMinus ) and empty( $catPlus ) )
      $whereClause = '';
    else
      $whereClause = ' WHERE ';

    if ( !empty( $catMinus ) and !empty( $catPlus ) )
      $andClause = ' AND ';
    else
      $andClause = '';

    $sql = "SELECT page_title
            FROM ${wgDBprefix}categorylinks
              LEFT JOIN ${wgDBprefix}page ON (cl_from=page_id)
            ${whereClause}${catMinus}${andClause}${catPlus}
            ORDER BY RAND()
            LIMIT 0, ${args['nbre']}";

    $lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
    $dbr = $lb->getConnection( DB_REPLICA );
    if( $res = $dbr->query( $sql) ) {

      while( $ligne = $dbr->fetchRow( $res) ) {
        $liste[] = '* [['.$ligne['page_title'].']]';
      }
    }
    else
      die('Oups, erreur de la requète');

    $output = $parser->recursiveTagParse( implode("\n",$liste) );

    return $output;
  }
}
