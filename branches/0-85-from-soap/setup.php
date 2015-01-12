<?php
/*
 * @version $Id: HEADER 15930 2012-12-15 11:10:55Z tsmr $
-------------------------------------------------------------------------
Ocsinventoryng plugin for GLPI
Copyright (C) 2012-2013 by the ocsinventoryng plugin Development Team.

https://forge.indepnet.net/projects/ocsinventoryng
-------------------------------------------------------------------------

LICENSE

This file is part of ocsinventoryng.

Ocsinventoryng plugin is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

Ocsinventoryng plugin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with ocsinventoryng. If not, see <http://www.gnu.org/licenses/>.
-------------------------------------------------------------------------- */

define("PLUGIN_OCSINVENTORYNG_STATE_STARTED", 1);
define("PLUGIN_OCSINVENTORYNG_STATE_RUNNING", 2);
define("PLUGIN_OCSINVENTORYNG_STATE_FINISHED", 3);

define("PLUGIN_OCSINVENTORYNG_LOCKFILE", GLPI_LOCK_DIR . "/ocsinventoryng.lock");

/**
 * Init the hooks of the plugins -Needed
**/
function plugin_init_ocsinventoryng() {
   global $PLUGIN_HOOKS, $CFG_GLPI;

   $PLUGIN_HOOKS['csrf_compliant']['ocsinventoryng'] = true;
   $PLUGIN_HOOKS['use_rules']['ocsinventoryng']      = array('RuleImportEntity', 'RuleImportComputer');

   $PLUGIN_HOOKS['change_profile']['ocsinventoryng'] = array('PluginOcsinventoryngProfile',
                                                             'initProfile');

   $PLUGIN_HOOKS['import_item']['ocsinventoryng']    = array('Computer');

   $PLUGIN_HOOKS['autoinventory_information']['ocsinventoryng']
      = array('Computer'               => array('PluginOcsinventoryngOcslink', 'showSimpleForItem'),
              'ComputerDisk'           => array('PluginOcsinventoryngOcslink', 'showSimpleForChild'),
              'ComputerVirtualMachine' => array('PluginOcsinventoryngOcslink', 'showSimpleForChild'));

   //Locks management
   $PLUGIN_HOOKS['display_locked_fields']['ocsinventoryng'] = 'plugin_ocsinventoryng_showLocksForItem';
   $PLUGIN_HOOKS['unlock_fields']['ocsinventoryng']         = 'plugin_ocsinventoryng_unlockFields';

   Plugin::registerClass('PluginOcsinventoryngOcslink',
                         array('forwardentityfrom' => 'Computer',
                               'addtabon'          => 'Computer'));

   Plugin::registerClass('PluginOcsinventoryngRegistryKey',
                         array('addtabon'          => 'Computer'));

   Plugin::registerClass('PluginOcsinventoryngOcsServer',
                         array('massiveaction_noupdate_types' => true,
                               'systeminformations_types'     => true));

   Plugin::registerClass('PluginOcsinventoryngProfile',
                         array('addtabon' => 'Profile'));

   Plugin::registerClass('PluginOcsinventoryngNotimportedcomputer',
                         array ('massiveaction_noupdate_types' => true,
                                'massiveaction_nodelete_types' => true,
                                'notificationtemplates_types'  => true));

   Plugin::registerClass('PluginOcsinventoryngDetail',
                         array ('massiveaction_noupdate_types' => true,
                                'massiveaction_nodelete_types' => true));

   Plugin::registerClass('PluginOcsinventoryngNetworkPort',
                         array('networkport_instantiations' => true));


   // transfer
   $PLUGIN_HOOKS['item_transfer']['ocsinventoryng']="plugin_ocsinventoryng_item_transfer";


   if (Session::getLoginUserID()) {

      // Display a menu entry ?
      if (Session::haveRight("plugin_ocsinventoryng", READ)) {
         $PLUGIN_HOOKS['menu_toadd']['ocsinventoryng'] = array('tools'   => 'PluginOcsinventoryngMenu');
         //$PLUGIN_HOOKS['menu_entry']['ocsinventoryng']               = 'front/ocsng.php';
         //$PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['search']  = 'front/ocsng.php';
      }

      if (Session::haveRight("plugin_ocsinventoryng", UPDATE) || Session::haveRight("config",UPDATE)) {
         $PLUGIN_HOOKS['use_massive_action']['ocsinventoryng'] = 1;
         $PLUGIN_HOOKS['redirect_page']['ocsinventoryng']      = "front/notimportedcomputer.form.php";
         
         //TODO Change for menu
         $PLUGIN_HOOKS['config_page']['ocsinventoryng']              = 'front/config.php';
         /*$PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['config']  = 'front/config.php';
         $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['ocsserver']['title']
            = __s("OCSNG server 's configuration", 'ocsinventoryng');
         $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['ocsserver']['page']
            = '/plugins/ocsinventoryng/front/ocsserver.php';
         $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['config']['title']
            = __s("Automatic synchronization's configuration", 'ocsinventoryng');
         $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['config']['page']
            = '/plugins/ocsinventoryng/front/config.form.php';
            

         if ($_SERVER['PHP_SELF']
                  == $CFG_GLPI["root_doc"]."/plugins/ocsinventoryng/front/ocsserver.php"
             || $_SERVER['PHP_SELF']
                  == $CFG_GLPI["root_doc"]."/plugins/ocsinventoryng/front/ocsserver.form.php") {
            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['search']  = 'front/ocsserver.php';
            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['add']     = 'front/ocsserver.form.php';*/
         //}


         if (Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
            //TODO Change for menu
            /*$PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['import']['title']
               = __s('Import new computers', 'ocsinventoryng');
            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['import']['page']
               = '/plugins/ocsinventoryng/front/ocsng.import.php';

            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['sync']['title']
               = __s('Synchronize computers already imported', 'ocsinventoryng');
            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['sync']['page']
               = '/plugins/ocsinventoryng/front/ocsng.sync.php';

            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['link']['title']
               = __s('Link new OCSNG computers to existing GLPI computers', 'ocsinventoryng');
            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['link']['page']
               = '/plugins/ocsinventoryng/front/ocsng.link.php';

            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['thread']['title']
               = __s('Scripts execution of automatic actions', 'ocsinventoryng');
            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['thread']['page']
               = '/plugins/ocsinventoryng/front/thread.php';

            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['detail']['title']
               = __('Computers imported by automatic actions', 'ocsinventoryng');
            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['detail']['page']
               = '/plugins/ocsinventoryng/front/detail.php';

            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['notimported']['title']
               = __s('Computers not imported by automatic actions', 'ocsinventoryng');
            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['notimported']['page']
               = '/plugins/ocsinventoryng/front/notimportedcomputer.php';
         
            if (Session::haveRight("plugin_ocsinventoryng_clean", READ)) {
            $PLUGIN_HOOKS ['submenu_entry'] ['ocsinventoryng'] ['options'] ['deleted_equiv'] ['title'] 
            = __s ( 'Clean OCSNG deleted computers', 'ocsinventoryng' );
            $PLUGIN_HOOKS ['submenu_entry'] ['ocsinventoryng'] ['options'] ['deleted_equiv'] ['page'] 
            = '/plugins/ocsinventoryng/front/deleted_equiv.php';
               $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['clean']['title']
                  = __s('Clean links between GLPI and OCSNG', 'ocsinventoryng');
               $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['clean']['page']
                  = '/plugins/ocsinventoryng/front/ocsng.clean.php';*/
            //}
         }

         $PLUGIN_HOOKS['post_init']['ocsinventoryng'] = 'plugin_ocsinventoryng_postinit';
      }
   }

   $CFG_GLPI['ocsinventoryng_devices_index'] = array(1  => 'Item_DeviceMotherboard',
                                                     2  => 'Item_DeviceProcessor',
                                                     3  => 'Item_DeviceMemory',
                                                     4  => 'Item_DeviceHardDrive',
                                                     5  => 'Item_DeviceNetworkCard',
                                                     6  => 'Item_DeviceDrive',
                                                     7  => 'Item_DeviceControl',
                                                     8  => 'Item_DeviceGraphicCard',
                                                     9  => 'Item_DeviceSoundCard',
                                                     10 => 'Item_DevicePci',
                                                     11 => 'Item_DeviceCase',
                                                     12 => 'Item_DevicePowerSupply');
}


/**
 * Get the name and the version of the plugin - Needed
**/
function plugin_version_ocsinventoryng() {

   return array('name'           => "OCS Inventory NG",
                'version'        => '1.1.0',
                'author'         => 'Remi Collet, Nelly Mahu-Lasson, David Durieux, Xavier Caillaud, Walid Nouh, Arthur Jaouen',
                'license'        => 'GPLv2+',
                'homepage'       => 'https://forge.indepnet.net/repositories/show/ocsinventoryng',
                'minGlpiVersion' => '0.85');// For compatibility / no install in version < 0.80

}


/**
 * Optional : check prerequisites before install : may print errors or add to message after redirect
**/
function plugin_ocsinventoryng_check_prerequisites() {

   if (version_compare(GLPI_VERSION,'0.85','lt') || version_compare(GLPI_VERSION,'0.86','ge')) {
      echo "This plugin requires GLPI = 0.85";
      return false;
   }
   return true;
}


// Uninstall process for plugin : need to return true if succeeded : may display messages or add to message after redirect
function plugin_ocsinventoryng_check_config() {
   return true;
}

?>