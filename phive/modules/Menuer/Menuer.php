<?php
require_once __DIR__ . '/../../modules/HierarchySQL/HierarchySQL.php';

class Menuer extends HierarchySQL {

  private $menuer_prefix = "menuer.";

  private $secondary_menu = "secondary-top-menu";

  private $mob_secondary_menu = "mobile-secondary-top-menu";

  private $secondary_menu_html_id = "secondary-nav";

  private $secondary_menu_config = "secondary_nav";

  public function __construct () {

    $this->setKeys( "menu_id", "parent_id", "alias", "name", "priority" );
  }

  /**
   * @return string
   **/
  public function getMenuerPrefix() {
    return $this->menuer_prefix;
  }

  /**
   * @return string
   **/
  public function getSecondaryMobileMenuId() {
      return $this->mob_secondary_menu;
  }

  /**
   * @return string
   **/
  public function getSecondaryMenuId() {
      return $this->secondary_menu;
  }

  /**
   * @return string
   **/
  public function getSecondaryMenuHtmlId() {
      return $this->secondary_menu_html_id;
  }

  public function getMenu ( $id ) { return $this->getEntry( $id ); }

  function getCurMenu ($page_id = null) {

    $page_id = $page_id ??  phive( 'Pager' )->getId();
    $q = "SELECT * FROM `{$this->getSetting('table_entries')}` WHERE `link_page_id` = " . $page_id;
    $items = phive( 'SQL' )->loadArray( $q );
    if ( count( $items ) == 1 ) {
      return $items[0];
    } else {
      $raw_path = phive( 'Pager' )->getRawPathNoTrailing();
      foreach ( $items as $item ) {
        if ( $item['link'] == $raw_path ) {
          return $item;
        }
        $path_arr = explode( '/', str_replace( ']', '', $item['getvariables'] ) );
        array_pop( $path_arr );
        $page_path = array_pop( $path_arr );
        if ( $page_path == phive( 'Pager' )->getLastLvl() ) {
          return $item;
        }
      }
    }

    return $items[0] ?? [];
  }

  public function getCurrentMenuParent ( $as_arr = false ) {

    $sql = phive( 'SQL' );
    $table = $this->getSetting( 'table_entries' );
    $pid = phive( 'Pager' )->getId();
    if ( !$as_arr ) {
      $sql->getValue( "SELECT `parent_id` FROM `$table` WHERE `link_page_id` = $pid" );
    } else {
      $sql->loadAssoc( "SELECT * FROM `$table` WHERE parent_id IN(SELECT menu_id FROM $table WHERE `link_page_id` = $pid)" );
    }
  }

  public function getCurrentMenuObject ( $parent = null, $as_arr = false ) {

    $sql = phive( 'SQL' );
    $table = $this->getSetting( 'table_entries' );
    $select = $as_arr ? '*' : 'menu_id';
    $q = "SELECT $select FROM `$table` WHERE `link_page_id` = " . phive( 'Pager' )->getId();

    if ( $parent !== null ) {
      $q .= " AND `parent_id`=" . $parent;
    }

    return $as_arr ? $sql->loadAssoc( $q ) : (int)$sql->getValue( $q );
  }

  public function countryIsBlocked($el, $user = null)
  {
      $user = cu($user);
      $country = licJurOrCountry($user);
      $province = $user ? $user->getProvince() : licSetting('default_main_province');

      if(!empty($el['excluded_countries']) && in_array($country, explode(' ', $el['excluded_countries']))) {
          return true;
      }

      if (!empty($el['excluded_provinces']) && in_array($province, explode(' ', $el['excluded_provinces']))) {
          return true;
      }

      if(!empty($el['included_countries']) && !in_array($country, explode(' ', $el['included_countries']))){
          return true;
      }

      return false;
  }

  public function brandIsBlocked($el)
  {
      $brand = phive('BrandedConfig')->getBrand();

      if(!empty($el['excluded_brands']) && in_array($brand, explode(' ', $el['excluded_brands']))) {
          return true;
      }

      return false;
  }

  function forRender ( $id, $default_path = '/.', $complex = false, $username = '', $pid = '', $default = true,
                       $separator = '/', $action = '', $user = null, $translate = true ) {

      /** @var Pager $p */
    $p = phive( 'Pager' );
    /** @var SQL $sql */
    $sql = phive('SQL');

    $raw_path_no_trailing = $p->getRawPathNoTrailing();

    if ( $complex ) {
      $cur_path = $p->getLastLvl();
      $cur_path = $cur_path == $username ? $default_path : $cur_path;
    } else {
      $cur_path = $p->getPathNoTrailing();
      $cur_path = empty( $cur_path ) ? $default_path : $cur_path;
    }

    $cur_path = empty( $cur_path ) ? $default_path : $cur_path;
    $rarr = array();

    $cur_menu = $this->getCurMenu();

    if (isLogged()) {
        $isLogged = true;
    } else {
        $isLogged = !is_null($user) && $user !== false;
    }

    $page_route = $sql->loadAssoc("SELECT * FROM page_routes WHERE route = {$sql->escape(substr($p->getPathNoTrailing(), 1))}");
    foreach ( $this->getChildren( $id, $pid, $default, $user ) as $el ) {

      if ($this->countryIsBlocked($el, $user)) {
          continue;
      }

      if ($this->brandIsBlocked($el)) {
          continue;
      }

      if ( $el['logged_in'] == 1 && !$isLogged ) {
        continue;
      }

      if ( $el['logged_out'] == 1 && $isLogged ) {
        continue;
      }

      if ( $complex ) {
        $path_arr = explode( '/', str_replace( ']', '', $el['linkparams'] ) );
        array_pop( $path_arr );
        $page_path = array_pop( $path_arr );
        if ( !empty( $action ) ) $page_path = $action;
        $current = $cur_path == $page_path;

        if ( $current == false ) {
          $current = $cur_menu['parent_id'] == $el['menu_id'];
        }
      } else {
        $current = $el['page_path'] == $cur_path;
      }

      if ( $current == false ) {
        $current = $el['link'] == $raw_path_no_trailing;
      }

      if ( $current == false ) {
        $alias = empty( $_GET['alias'] ) ? $_POST['alias'] : $_GET['alias'];
        $current = $el['alias'] == $alias;
      }

      if ($current == false) {
        $current = $page_route && $page_route['page_id'] == $el['link_page_id'];
      }

      if ( !empty( $username ) ) {
        $params = str_replace( "[user/]", urlencode( $username ) . $separator, $el['linkparams'] );
        if (strpos( $el['linkparams'], "[user]") !== false)
          $params = str_replace( "[user]", urlencode( $username ), $el['linkparams'] );

        $plink = str_replace( array( '"', 'href', '=' ), '', $params );
      } else {
        $params = $el['linkparams'];
      }

        $txt = $translate ?
            $this->maintenanceMenuTransform(
                $el['alias'],
                phive('Localizer')->getPotentialString($el['name'])
            ) :
            $el['name'];

        $rarr[] = array(
        'params'    => $params,
        'txt'       => $txt,
        'current'   => $current,
        'name'      => $el['name'],
        'alias'     => $el['alias'],
        'link'      => $el['link'],
        'plink'     => $plink,
        'parent_id' => $el['parent_id'],
        'icon' => $el['icon'],
        'page_id' => (int) $el['link_page_id'],
        'priority' => (int) $el['priority'],
      );
    }

    return $rarr;
  }

  function renderMenu ( $menu, $attrs = '', $default_path = '/.', $complex = false, $username = '', $pid = '',
                        $render_subs = true, $extra = array() ) {

    $menu = $this->forRender( $menu, $default_path, $complex, $username, $pid, false );
    if ( empty( $menu ) ) {
      return;
    }

    if ( !empty( $extra ) ) {
      $menu = array_merge( $menu, array( $extra ) );
    }
    ?>
    <ul <?php echo $attrs ?>>
      <?php foreach ( $menu as $item ): ?>
        <li <?php echo $item['current'] ? 'class="active"' : '' ?>>
          <a <?php echo $item['params'] ?>><?php echo $item['txt'] ?></a>
          <?php if ( $render_subs ): ?>
            <?php $this->renderMenu( $item['alias'], '', $default_path, $complex, $username,
                                     $item['parent_id'] ) ?>
          <?php endif ?>
        </li>
      <?php endforeach ?>
    </ul>
    <?php
    foreach ( $menu as $item ) {
      if ( $item['current'] ) {
        return $item;
      }
    }
  }

  /**
   * Render the `secondary-nav` of the page's header.
   *
   */
  public function renderSecondaryMobileMenu(): void
  {
    $secondary_menu = $this->forRender($this->mob_secondary_menu);
    if ($this->getSetting($this->secondary_menu_config, false)) {
    ?>
    <div id="<?= $this->secondary_menu_html_id ?>" class="<?= lic('isSportsbookEnabled') ? 'secondary-nav_sports_icon sportsbook-live-menu' : '' ?>">
      <ul>
        <?php foreach ($secondary_menu as $item): ?>
          <li <?php echo $item['current'] ? 'class="active"' : '' ?>>
            <a <?php echo $item['params'] ?>>
              <span class="icon <?= $item['icon'] ?>"></span>
              <?php echo $this->maintenanceMenuTransform($item['alias'], t($item['txt'])) ?>
            </a>
          </li>
        <?php endforeach ?>
      </ul>
    </div>
    <?php
    }
  }

  public function getChildren ( $entry_id_or_alias = 0, $pid = '', $default = true, $user = null ) {

    if ( !$this->requireKeys( 'id', 'parent' ) ) {
      return false;
    }

    if ( $entry_id_or_alias === null && $default ) {
      $entry_id_or_alias = 0;
    }

    $db_menus = $this->getSetting( 'table_entries' );
    $db_pages = phive( 'Pager' )->getSetting( 'table_entries' );

    // Possibly change to is_numeric(), but that would disable numeric aliases.
    if ( is_numeric( $entry_id_or_alias ) ) {
      $prio = $this->getKey( 'priority' );
      $p_str = ( ( $prio ) ? " ORDER BY `$prio`" : "" );

      $id = intval($entry_id_or_alias);
      $pid = intval($pid);
      $where_parent = empty( $pid ) ? '' : " AND $db_menus.parent_id = $pid ";

      $str = "SELECT `$db_menus`.*, `$db_pages`.`cached_path` as `page_path` FROM `$db_menus`
                                  LEFT JOIN `$db_pages` ON `$db_menus`.`link_page_id` = `$db_pages`.`page_id`
                                  WHERE `$db_menus`.`{$this->getKey('parent')}` = $id $where_parent $p_str";

      phive( 'SQL' )->query( $str );

      $ret = phive( 'SQL' )->fetchArray( 'ASSOC' );
    } else {
      if ( ( $alias_key = $this->getKey( 'alias' ) ) != "" ) {
        $prio = $this->getKey( 'priority' );
        $p_str = ( ( $prio ) ? " ORDER BY a.`$prio`" : "" );

        $where_parent = empty( $pid ) ? '' : " AND b.parent_id = $pid ";

        $alias = $entry_id_or_alias;
        phive( 'SQL' )->query(
          "SELECT a.*, p.`cached_path` as `page_path` FROM `$db_menus` as a
                                  INNER JOIN `$db_menus` as b ON a.`{$this->getKey('parent')}` = b.`{$this->getKey('id')}`
                                  LEFT JOIN `$db_pages` as p ON a.`link_page_id` = p.`page_id`
                                  WHERE b.`$alias_key`='$alias' $where_parent " . $p_str );
        $ret = phive( 'SQL' )->fetchArray( 'ASSOC' );
      } else {
        trigger_error( "Could not produce result with Menuer::getChildren()", E_USER_WARNING );
      }
    }

    $ret2 = array();

    foreach ( $ret as $key => $menu ) {
      $ret[ $key ]['linkparams'] = $this->getLinkParams( $menu );

      if ( $menu['check_permission'] ) {

          if ( p( 'menuer.' . $menu[ 'alias' ], $user ) )
            $ret2[ $key ] = $ret[ $key ];
      } else
        $ret2[ $key ] = $ret[ $key ];
    }

    return $ret2;
  }

    public function getLinkParams ( array $menu ) {

        $ret = "";
        if ( $menu['new_window'] ) {
            $ret .= 'onclick="window.open(this.href); return false;" ';
        }

        $ret .= 'href="';

        $p = phive( 'Pager' );

        $loc_type = $p->getSetting( 'loc_type' );

        // Do we have an absolute link? If yes we use it as is, nothing more needed.
        if(strpos($menu['link'], 'http') !== false){
            $ret .= $menu['link'];
        }else{
            if ( $loc_type == 'dir' ) {
                if ( $p->getSetting( 'show_default_lang' ) ) {
                    $lang = empty( $p->cur_lang ) ? '/' . phive('Localizer')->getDefaultLanguage() :
                            '/' . $p->cur_lang;
                } else {
                    $lang = ($p->cur_lang == phive('Localizer')->getDefaultLanguage()) ? '' : "/{$p->cur_lang}";
                }
            } else {
                $lang = '';
            }

            $menu['page_path'] = phive('Pager')->resolvePageRoutes($menu['page_path']);
            if ( $menu['page_path'] ) {
                $ret .= $lang . Pager::addSlash( $menu['page_path'] );
            } else {
                if ( $menu['link_page_id'] ) {
                    $ret .= $lang . phive( 'Pager' )->getPath( $menu['link_page_id'] );
                } else {
                    $ret .= $lang . phive('Pager')->resolvePageRoutes($menu['link']);
                }
            }

            $ret .= $menu['getvariables'];
        }
        $ret .= '"';

        return $ret;
    }

  public function getTextLink ( array $menu ) {

    $ret = '<a ';
    if ( $menu['linkparams'] ) {
      $ret .= $menu['linkparams'];
    } else {
      $ret .= $this->getLinkParams( $menu );
    }

    $name = $menu['name'];
    if ( phive()->moduleExists( 'Localizer' ) ) {
      $name = phive( 'Localizer' )->getPotentialString( $name );
    }

    $ret .= '>' . str_replace( ' ', '&nbsp;', $name ) . '</a>';

    return $ret;
  }

    private function maintenanceMenuTransform(string $alias, string $text): string
    {
        if (phive('Sportsbook')->shouldTransformMenuItemToMaintenance($alias, 'isSportsbookOnMaintenance')) {
            return "$text (Maintenance)";
        }

        if (phive('Sportsbook')->shouldTransformMenuItemToMaintenance($alias, 'isPoolxOnMaintenance', 'poolx_maintenance_menus')) {
            return "$text (Poolx Maintenance)";
        }

        return $text;
    }

}
