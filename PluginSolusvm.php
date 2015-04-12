<?php

require_once 'library/CE/XmlFunctions.php';
require_once 'library/CE/NE_MailGateway.php';
require_once 'modules/admin/models/ServerPlugin.php';

/**
* SolusVM Server Plugin
* @Author Matt Grandy
* @email matt@clientexec.com
*/

Class PluginSolusvm extends ServerPlugin {

    public $features = array(
        'packageName' => true,
        'testConnection' => true,
        'showNameservers' => false,
        'directlink' => true
    );

    var $host;
    var $id;
    var $key;
    var $url;

    function setup ( $args ) {
        if ( isset($args['server']['variables']['ServerHostName']) && isset($args['server']['variables']['plugin_solusvm_ID']) && isset($args['server']['variables']['plugin_solusvm_Key']) ) {
            if ( $args['server']['variables']['plugin_solusvm_Port'] == '' ) {
                $args['server']['variables']['plugin_solusvm_Port'] = 5656;
            }
            $this->host = $args['server']['variables']['ServerHostName'];
            $this->id = $args['server']['variables']['plugin_solusvm_ID'];
            $this->key = $args['server']['variables']['plugin_solusvm_Key'];
            $this->url = "https://{$this->host}:{$args['server']['variables']['plugin_solusvm_Port']}/api/admin";
        } else {
            throw new CE_Exception("Missing Server Credentials: please fill out all information when editing the server.");
        }
    }

    function email_error ( $name, $message, $params, $args ) {
        $error = "SolusVM Account " .$name." Failed. ";
        $error .= "An email with the Details was sent to ". $args['server']['variables']['plugin_solusvm_Failure_E-mail'].'<br /><br />';

        if ( is_array($message) ) {
            $message = implode ( "\n", trim($message) );
        }

        CE_Lib::log(1, 'SolusVM Error: '.print_r(array('type' => $name, 'error' => $error, 'message' => $message, 'params' => $params, 'args' => $args), true));

        if ( !empty($args['server']['variables']['plugin_solusvm_Failure_E-mail']) ) {
            $mailGateway = new NE_MailGateway();
            $mailGateway->mailMessageEmail( $message,
            $args['server']['variables']['plugin_solusvm_Failure_E-mail'],
            "SolusVM Plugin",
            $args['server']['variables']['plugin_solusvm_Failure_E-mail'],
            "",
            "SolusVM Account ".$name." Failure");
        }
        return $error.nl2br($message);
    }

    function getVariables() {

        $variables = array (
            lang("Name") => array (
                "type"=>"hidden",
                "description"=>"Used by CE to show plugin - must match how you call the action function names",
                "value"=>"Solusvm"
            ),
            lang("Description") => array (
                "type"=>"hidden",
                "description"=>lang("Description viewable by admin in server settings"),
                "value"=>lang("SolusVM control panel integration")
            ),
            lang("ID") => array (
                "type"=>"text",
                "description"=>lang("API ID"),
                "value"=>"",
                "encryptable"=>true
            ),
            lang("Key") => array (
                "type"=>"text",
                "description"=>lang("API Key"),
                "value"=>"",
                "encryptable"=>true
            ),
            lang("Port") => array (
                "type"=>"text",
                "description"=>lang("API Port"),
                "value"=>"5656"
            ),
            lang("VM Username Custom Field") => array(
                "type"        => "text",
                "description" => lang("Enter the name of the package custom field that will hold the SolusVM Username. This field should not be included in sign up."),
                "value"       => ""
            ),
            lang("VM Password Custom Field") => array(
                "type"        => "text",
                "description" => lang("Enter the name of the package custom field that will hold the SolusVM Password."),
                "value"       => ""
            ),
            lang("VM Hostname Custom Field") => array(
                "type"        => "text",
                "description" => lang("Enter the name of the package custom field that will hold the VM hostname for SolusVM."),
                "value"       => ""
            ),
            lang("VM Operating System Custom Field") => array(
                "type"        => "text",
                "description" => lang("Enter the name of the package custom field that will hold the VM Operating System for SolusVM."),
                "value"       => ""
            ),
            lang("Failure E-mail") => array (
                "type"=>"text",
                "description"=>lang("E-mail address Virualmin error messages will be sent to"),
                "value"=>""
            ),
            lang("Actions") => array (
                "type"=>"hidden",
                "description"=>lang("Current actions that are active for this plugin per server"),
                "value"=>"Create,Delete,Suspend,UnSuspend,Reboot,Boot,Shutdown,TUNTAP"
            ),
            lang('Registered Actions For Customer') => array(
                "type"=>"hidden",
                "description"=>lang("Current actions that are active for this plugin per server for customers"),
                "value"=>"authenticateClient"
            ),
            lang("reseller") => array (
                "type"=>"hidden",
                "description"=>lang("Whether this server plugin can set reseller accounts"),
                "value"=>"1",
            ),
            lang("package_addons") => array (
                "type"=>"hidden",
                "description"=>lang("Supported signup addons variables"),
                "value"=>"",
            ),
            lang('package_vars')  => array(
                'type'            => 'hidden',
                'description'     => lang('Whether package settings are set'),
                'value'           => '0',
            ),
            lang('package_vars_values') => array(
                'type'            => 'hidden',
                'description'     => lang('SolusVM Settings'),
                'value'           => array(
                    'maxdisk' => array(
                        'type'            => 'text',
                        'label'            => 'HDD Amount',
                        'description'     => lang('Enter the amount of HDD.'),
                        'value'           => '',
                    ),
                    'maxbw' => array(
                        'type'            => 'text',
                        'label'            => 'Max BW',
                        'description'     => lang('Enter the BW.'),
                        'value'           => '',
                    ),
                    'maxip' => array(
                        'type'            => 'text',
                        'label'            => 'IP's Avail,
                        'description'     => lang('Enter amount of IP'),
                        'value'           => '',
                    ),
                    'maxipv6' => array(
                        'type'            => 'text',
                        'label'            => 'IPv6 Amount',
                        'description'     => lang('Enter the amount of IPV6 the user should have.'),
                        'value'           => '',
                    ),
                    'ram_amount' => array(
                        'type'            => 'text',
                        'label'            => 'RAM',
                        'description'     => lang('Enter the the amount of RAM the user is able to create.'),
                        'value'           => '',
                    )
                )
            )
        );

        return $variables;
    }

    function validateCredentials($args) {

    }

    function doDelete($args) {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->delete($args);
        $VM_Hostname = $userPackage->getCustomField($args['server']['variables']['plugin_solusvm_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        return $VM_Hostname . ' has been deleted.';
    }

    function doTUNTAP($args) {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->enableTunTap($args);
        $VM_Hostname = $userPackage->getCustomField($args['server']['variables']['plugin_solusvm_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        return $VM_Hostname . ' has has TUN/TAP enabled.';
    }

    function doCreate($args) {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->create($args);
        $VM_Hostname = $userPackage->getCustomField($args['server']['variables']['plugin_solusvm_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        return $VM_Hostname . ' has been created.';
    }

    function doSuspend($args) {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->suspend($args);
        $VM_Hostname = $userPackage->getCustomField($args['server']['variables']['plugin_solusvm_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        return $VM_Hostname . ' has been suspended.';
    }

    function doUnSuspend($args) {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->unsuspend($args);
        $VM_Hostname = $userPackage->getCustomField($args['server']['variables']['plugin_solusvm_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        return $VM_Hostname . ' has been unsuspended.';
    }

    function doReboot($args) {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->reboot($args);
        $VM_Hostname = $userPackage->getCustomField($args['server']['variables']['plugin_solusvm_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        return $VM_Hostname . ' has been rebooted.';
    }

    function doBoot($args) {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->boot($args);
        $VM_Hostname = $userPackage->getCustomField($args['server']['variables']['plugin_solusvm_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        return $VM_Hostname . ' has been booted.';
    }

    function doShutdown($args) {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->shutdown($args);
        $VM_Hostname = $userPackage->getCustomField($args['server']['variables']['plugin_solusvm_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        return $VM_Hostname . ' has been shutdown.';
    }

    function enableTunTap($args) {
        $this->setup($args);
        $params = array();

        $params['action'] = 'vserver-tun-enable';
        $params['vserverid'] = $args['package']['ServerAcctProperties'];

        $request = $this->call($params, $args);
    }

    function boot($args) {
        $this->setup($args);
        $params = array();

        $params['action'] = 'vserver-boot';
        $params['vserverid'] = $args['package']['ServerAcctProperties'];

        $request = $this->call($params, $args);
    }

    function shutdown($args) {
        $this->setup($args);
        $params = array();

        $params['action'] = 'vserver-shutdown';
        $params['vserverid'] = $args['package']['ServerAcctProperties'];

        $request = $this->call($params, $args);
    }

    function reboot($args) {
        $this->setup($args);
        $params = array();

        $params['action'] = 'vserver-reboot';
        $params['vserverid'] = $args['package']['ServerAcctProperties'];

        $request = $this->call($params, $args);
    }

    function unsuspend($args) {
        $this->setup($args);
        $params = array();

        $params['action'] = 'vserver-unsuspend';
        $params['vserverid'] = $args['package']['ServerAcctProperties'];

        $request = $this->call($params, $args);
    }

    function suspend($args) {
        $this->setup($args);
        $params = array();

        $params['action'] = 'vserver-suspend';
        $params['vserverid'] = $args['package']['ServerAcctProperties'];

        $request = $this->call($params, $args);
    }

    function delete($args) {
        $this->setup($args);
        $params = array();

        $params['action'] = 'vserver-terminate';
        $params['vserverid'] = $args['package']['ServerAcctProperties'];
        $params['deleteclient'] = 'true';

        $request = $this->call($params, $args);

        // remove the stored virtual id
        $userPackage = new UserPackage($args['package']['id']);
        $userPackage->setCustomField('Server Acct Properties', '');
    }

    function getAvailableActions($userPackage) {
        $args = $this->buildParams($userPackage);
        $this->setup($args);
        $actions = array();

        $params = array();

        $params['action'] = 'vserver-status';
        $params['vserverid'] = $args['package']['ServerAcctProperties'];

        try {
            $request = $this->call($params, $args);
            $actions[] = 'Delete';
            if ( $request['statusmsg'] == 'disabled' ) {
                $actions[] = 'UnSuspend';
            } else {
                $actions[] = 'Suspend';
                $actions[] = 'Reboot';
                $actions[] = 'TUNTAP';
                if ( $request['statusmsg'] == 'offline' ) {
                    $actions[] = 'Boot';
                } else {
                    $actions[] = 'Shutdown';
                }
            }
        } catch (Exception $e) {
            $actions[] = 'Create';
        }

        return $actions;
    }

    function create($args) {
        $this->setup($args);
        $userPackage = new UserPackage($args['package']['id']);

        $username = 'ce' . $args['customer']['id'];

        // create the client
        $params['action'] = 'client-create';
        $params['username'] = $username;
        $params['password'] = html_entity_decode($userPackage->getCustomField($args['server']['variables']['plugin_solusvm_VM_Password_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE));
        $params['email'] = $args['customer']['email'];
        $params['firstname'] = $args['customer']['first_name'];
        $params['lastname'] = $args['customer']['last_name'];
        $params['company'] = $args['customer']['organization'];
        try {
            $result = $this->call($params, $args);
        } catch ( Exception $e ) {
            // If the message is that the client already exists, we can ignore it...
            if ( $e->getMessage() != 'Client already exists' ) {
                throw new CE_Exception($e->getMessage());
            }
        }

        // create the server
        $params = array();
        $params['action'] = 'vserver-create';
        $params['type'] = $args['package']['variables']['vm_type'];
        $params['hostname'] = $userPackage->getCustomField($args['server']['variables']['plugin_solusvm_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        $params['username'] = $username;
        $params['password'] = html_entity_decode($userPackage->getCustomField($args['server']['variables']['plugin_solusvm_VM_Password_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE));
        $params['plan'] = $args['package']['name_on_server'];
        $params['nodegroup'] = $args['package']['variables']['node_group'];
        $params['ips'] = $args['package']['variables']['num_of_ips'];
        $params['template'] = $userPackage->getCustomField($args['server']['variables']['plugin_solusvm_VM_Operating_System_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);


        $username = 'ce' . $args['customer']['id'];
        function generatePassword($length = 8) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $count = mb_strlen($chars);

        for ($i = 0, $result = ''; $i < $length; $i++) {
        $index = rand(0, $count - 1);
        $result .= mb_substr($chars, $index, 1);
          }

         return $result;
        }
        //create the reseller
        $params['action'] = 'reseller-create';
        $params['username'] = $username;
        $params['password'] = $result;
        $params['company'] = $args['customer']['organization'];
        $params['email'] = $args['customer']['email'];
        $params['firstname'] = $args['customer']['first_name']
        $params['lastname'] = $args['customer']['last_name'];
        $params['company'] = $args['customer']['organization'];
        $params['maxvps'] = 5000
        $params['maxusers'] = 5000
        $params['maxmem'] = $args['package']['variables']['ram_amount'];
        $params['maxburst'] = $args['package']['variables']['ram_amount'];
        $params['maxdisk'] = $args['package']['variables']['maxdisk'];
        $params['maxbw'] = $args['package']['variables']['maxbw'];
        $params['maxipv4'] = $args['package']['variables']['maxip'];
        $params['maxipv4'] = $args['package']['variables']['maxipv6'];
        $params['nodegroup'] = 1, 2
        $params['openvz'] = 1
        $params['kvm'] = 1
        

    }


    function call($params, $args)
    {
        if ( !function_exists('curl_init') )
        {
            throw new CE_Exception('cURL is required in order to connect to SolusVM');
        }

        $params['id'] = $this->id;
        $params['key'] = $this->key;

        CE_Lib::log(4, 'SolusVM Params: ' . print_r($params, true));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url . "/command.php");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Expect:"));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $data = curl_exec($ch);

        if ( $data === false )
        {
            $error = "SolusVM API Request / cURL Error: ".curl_error($ch);
            CE_Lib::log(4, $error);
            throw new CE_Exception($error);
        }
        curl_close($ch);
        // Because SolusVM passes back broken XML...:
        preg_match_all('/<(.*?)>([^<]+)<\/\\1>/i', $data, $match);
        $result = array();
        foreach ($match[1] as $x => $y) {
            $result[$y] = $match[2][$x];
        }

        if ( $result['status'] == 'error' ) {
            CE_Lib::log(4, 'SolusVM Error: ' . $result['statusmsg']);
            // don't e-mail about a status fail
            if ( $params['action'] != 'vserver-status' && $result['statusmsg'] != 'Client already exists' ) {
                $this->email_error($params['action'], $result['statusmsg'], $params, $args);
            }
            throw new CE_Exception($result['statusmsg']);
        }
        return $result;
    }

    function getVServerInfo($args, $id)
    {
        $params = array();

        $params['action'] = 'vserver-info';
        $params['vserverid'] = $id;

        return $this->call($params, $args);
    }


    function testConnection($args)
    {
        CE_Lib::log(4, 'Testing connection to SolusVM server');
        $this->setup($args);

        $params = array();
        $params['action'] = 'node-idlist';
        // we send openvz, just as a test, to see if we can connect or not.
        $params['type'] = 'openvz';
        $response = $this->call($params, $args);
    }

    function doauthenticateClient($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->setup($args);

        $params = array();
        $params['action'] = 'client-key-login';
        $params['username'] = 'ce' . $args['customer']['id'];;
        $params['vserverid'] = $userPackage->getCustomField('Server Acct Properties');
        $params['returnurl'] = CE_Lib::getSoftwareURL() . '/index.php?fuse=clients&controller=products&view=product&id=' . $userPackage->id;

        // we do not want to show the client the error message from solus, so try/catch this
        try {
            $result = $this->call($params, $args);
        } catch (Exception $e ) {
            return array();
        }

        if ($result['status'] == 'success' ) {
            return array('hasha' => $result['hasha'], 'hashb' => $result['hashb']);
        } else {
            return array();
        }
    }


    function getDirectLink($userPackage)
    {
        $args = $this->buildParams($userPackage);
        $this->setup($args);
        $this->view->packageId = $userPackage->id;
        $this->view->serverURL = "https://{$this->host}:{$args['server']['variables']['plugin_solusvm_Port']}/auth.php";
        $form = $this->view->render('login.phtml');

        return array(
            'link' => '<li><a href="#" onclick="loginToSolusVM(); return false">' . $this->user->lang('Login to SolusVM') . '</a></li>',
            'form' => $form
        );
    }
}
