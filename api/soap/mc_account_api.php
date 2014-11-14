<?php
# MantisBT - A PHP based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A webservice interface to Mantis Bug Tracker
 *
 * @package MantisBT
 * @copyright Copyright 2004  Victor Boctor - vboctor@users.sourceforge.net
 * @copyright Copyright 2005  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 */

/**
 * Get username, realname and email from for a given user id
 * @param integer $p_user_id A valid user identifier.
 * @return array
 */
function mci_account_get_array_by_id( $p_user_id ) {
	$t_result = array();
	$t_result['id'] = $p_user_id;

	if( user_exists( $p_user_id ) ) {
		$t_result['name'] = user_get_field( $p_user_id, 'username' );
		$t_dummy = user_get_field( $p_user_id, 'realname' );

		if( !empty( $t_dummy ) ) {
			$t_result['real_name'] = $t_dummy;
		}

		$t_dummy = user_get_field( $p_user_id, 'email' );

		if( !empty( $t_dummy ) ) {
			$t_result['email'] = $t_dummy;
		}
	}
	return $t_result;
}

/**
 * Get username, realname and email from for a set of given user ids
 * @param array $p_user_ids An array of user identifiers.
 * @return array
 */
function mci_account_get_array_by_ids ( array $p_user_ids ) {
	$t_result = array();

	foreach ( $p_user_ids as $t_user_id ) {
		$t_result[] = mci_account_get_array_by_id( $t_user_id );
	}

	return $t_result;
}
/** 
+* Add a new user.
+*
+* @param string $p_username  The name of the user trying to create an account.
+* @param string $p_password  The password of the user.
+* @param Array $p_user A new AccountData structure
+* @return integer the new users's users_id
+*/
function mc_account_add( $p_username, $p_password, $newusername, $newuserrealname, $newuseremail, $newuseraccess, $p_pass ) {
	$t_user_id = mci_check_login( $p_username, $p_password );               
	if ( $t_user_id === false ) {
            return mci_soap_fault_login_failed();
	}

	if ( !mci_has_administrator_access( $t_user_id ) ) {
		return mci_soap_fault_access_denied( 'Client', '', 'Access Denied', 'User does not have administrator access');
	}
//error_log('p_password - ' . $p_password);
//error_log('p_pass-'. $p_pass);
//error_log('dumping..' . var_dump($p_user));
//error_log('this is defined vars...' . var_dump(xdebug_get_declared_vars()));
	// extract( $p_user, EXTR_PREFIX_ALL, 'v');
	// validate user object
	if ( is_blank($newusername)) return new soap_fault('Client', '', 'Mandatory field "name" was missing');
	if ( is_blank($newuserrealname) ) return new soap_fault('Client', '', 'Mandatory field "real_name" was missing');
	if ( is_blank($newuseremail) ) return new soap_fault('Client', '', 'Mandatory field "email" was missing');

	if ( !user_is_name_valid( $newusername ) ) return new soap_fault( 'Client', '', 'user name invalid');
	if ( !user_is_name_unique( $newusername ) ) return mci_soap_fault_user_exists( 'Client', '', 'user name exists');
	if ( !user_is_realname_valid( $newuserrealname ) ) return new soap_fault( 'Client', '', 'real name invalid');

	// set defaults
	if ( is_null( $newuseraccess ) ) $newuseraccess = VIEWER;

	// create user account and get it's id
	user_create($newusername, $p_pass, $newuseremail, $newuseraccess, false, true, $newuserrealname);
	$t_user_id = user_get_id_by_name($newusername);

	// return id of new user back to caller
	return $t_user_id;
}

function mci_soap_fault_user_exists() {
	return SoapObjectsFactory::newSoapFault('Client', 'User Already Exists');
}

