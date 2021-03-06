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
namespace APF\modules\usermanagement\pres\condition;

use APF\modules\usermanagement\biz\model\UmgtGroup;
use APF\modules\usermanagement\biz\model\UmgtUser;

/**
 * Implements the decision logic, whether a user is part of the groups
 * given in the options array.
 *
 * @author Christian Achatz
 * @version
 * Version 0.1, 01.06.2011
 */
class UmgtGroupCondition extends UserDependentContentConditionBase implements UserDependentContentCondition {

   public function matches($conditionKey, UmgtUser $user = null) {

      if ($user === null) {
         return false;
      }

      foreach ($this->getGroups($user) as $group) {
         if (in_array($group->getDisplayName(), $this->getOptions())) {
            return true;
         }
      }

      return false;
   }

   public function getConditionIdentifier() {
      return 'group';
   }

   /**
    * @param UmgtUser $user
    *
    * @return UmgtGroup[]
    */
   private function getGroups(UmgtUser $user) {
      return $user->loadRelatedObjects('Group2User');
   }

}
