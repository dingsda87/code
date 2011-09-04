<?php
/**
 * <!--
 * This file is part of the adventure php framework (APF) published under
 * http://adventure-php-framework.org.
 *
 * The APF is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * The APF is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with the APF. If not, see http://www.gnu.org/licenses/lgpl-3.0.txt.
 * -->
 */
import('modules::usermanagement::pres::documentcontroller', 'umgt_base_controller');

/**
 * @package modules::usermanagement::pres::documentcontroller
 * @class umgt_menu_controller
 *
 * Displays the user management menu.
 *
 * @author Christian Achatz
 * @version
 * Version 0.1, 28.12.2008<br />
 */
class umgt_menu_controller extends umgt_base_controller {

   public function transformContent() {

      // define the general param exclusion array
      $generalExclusion = array('userid' => '', 'groupid' => '', 'roleid' => '', 'permissionid' => '', 'proxyid' => '',
                                'userview' => '', 'groupview' => '', 'roleview' => '', 'permissionsetview' => '',
                                'permissionview' => '', 'proxyview' => '');

      // display the links
      $this->setPlaceHolder('home', $this->generateLink(array_merge($generalExclusion, array('mainview' => ''))));
      $this->setPlaceHolder('manage_user', $this->generateLink(array_merge($generalExclusion, array('mainview' => 'user'))));
      $this->setPlaceHolder('manage_groups', $this->generateLink(array_merge($generalExclusion, array('mainview' => 'group'))));
      $this->setPlaceHolder('manage_roles', $this->generateLink(array_merge($generalExclusion, array('mainview' => 'role'))));
      $this->setPlaceHolder('manage_permissions', $this->generateLink(array_merge($generalExclusion, array('mainview' => 'permission'))));
      $this->setPlaceHolder('manage_proxies', $this->generateLink(array_merge($generalExclusion, array('mainview' => 'proxy'))));

   }

}

?>