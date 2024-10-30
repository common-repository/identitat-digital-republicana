<?php
/*
Plugin Name: Identitat Digital Republicana
Plugin URI: https://siriondev.com
description: Integració amb el procés de validació de la Identitat Digital Republicana del Consell per la República Catalana
Version: 1.0.3
Author: Sirion Developers
Author URI: https://siriondev.com
License: GPL-3.0
*/

add_action('user_new_form', "show_cxr_idrepublicana" );
add_action('edit_user_profile', 'show_cxr_idrepublicana');
add_action('show_user_profile', 'show_cxr_idrepublicana');

add_action('user_register', 'save_cxr_idrepublicana');
add_action('profile_update', 'save_cxr_idrepublicana');
add_action('profile_update', 'save_cxr_idrepublicana', 10, 2);

add_action('user_profile_update_errors', 'validate_cxr_idrepublicana', 0, 3);
add_action('wp_authenticate', 'authenticate_cxr_idrepublicana');

/**
 * Mostra el formulari per a introduir la Identitat Digital Republicana
 *
 * @param User $user
 *
 * @return void
 */
function show_cxr_idrepublicana($user)
{
    $meta = $users = get_user_meta($user->id);

    $cxr_idrepublicana = isset($meta['cxr_idrepublicana'][0]) ? $meta['cxr_idrepublicana'][0] : '';

    $cxr_idrepublicana = preg_replace('/[^a-z0-9\-]/i', '_', $cxr_idrepublicana); ?>

    <h3 class="heading">Consell per la República</h3>

    <table class="form-table">

        <tr>

            <th><label for="contact">Identitat Digital Republicana</label></th>

            <td><input type="text" class="input-text form-control" name="cxr_idrepublicana" id="cxr_idrepublicana" placeholder="C-999-99999" value="<?php echo $cxr_idrepublicana; ?>"/></td>

        </tr>

    </table> <?php
}


/**
 * Guarda el valor de la Identitat Digital Republicana
 *
 * @param int $user_id
 * @param array $old_user_data
 *
 * @return void
 */
function save_cxr_idrepublicana($user_id, $old_user_data)
{
    if (current_user_can('edit_user', $user_id)) {

        $idr = preg_replace('/[^a-z0-9\-]/i', '_', $_POST['cxr_idrepublicana']);

        update_user_meta($user_id, 'cxr_idrepublicana', $idr);
    }
}

/**
 * Valida la Identitat Digital Republicana
 *
 * @param array $errors
 * @param array $update
 * @param User $user
 *
 * @return array $errors
 */
function validate_cxr_idrepublicana($errors, $update, $user)
{
    if (!empty($_POST['cxr_idrepublicana'])) {

        if (!preg_match("/[A-Za-z]{1}\-[0-9]{3}\-[0-9]{5}/", $_POST['cxr_idrepublicana'])) {

            $errors->add('cxr_idrepublicana', "<strong>Error</strong>: L'ID Republicana introduïda no és vàlida!! Si us plau, torna-ho a intentar.");

        } else {

            $idr = preg_replace('/[^a-z0-9\-]/i', '_', $_POST['cxr_idrepublicana']);

            $response = wp_remote_get('https://apis.consellrepublica.cat/idserv/validate?idCiutada=' . $idr);

            $json = json_decode($response['body'], true);

            if (!isset($json['state']) || $json['state'] != 'VALID_ACTIVE') {

                $errors->add('cxr_idrepublicana', "<strong>Error</strong>: L'ID Republicana introduïda no és vàlida. Si us plau, torna-ho a intentar.");

            }

            $args = array(
        		'meta_key'     => 'cxr_idrepublicana',
        		'meta_value'   => $idr,
        		'meta_compare' => '=',
        		'exclude'      => array($user->ID)
        	);

        	$user_query = new WP_User_Query($args);

        	$users = $user_query->get_results();

            if ($users) {

                $errors->add('cxr_idrepublicana', "<strong>Error</strong>: L'ID Republicana introduïda ja està en ús. Si us plau, torna-ho a intentar.");

            }
        }
    }

    return $errors;
}

/**
 * Utilitza la Identitat Digital Republicana per a iniciar sessió
 *
 * @param array $errors
 * @param array $update
 * @param User $user
 *
 * @return void
 */
function authenticate_cxr_idrepublicana(&$username)
{
    $user = get_user_by('login', $username);

    if (!$user) {

        $user = get_user_by('email', $username);

        if (!$user) {

            $args = array(
        		'meta_key'     => 'cxr_idrepublicana',
        		'meta_value'   => $username,
        		'meta_compare' => '=',
        	);

            $user_query = new WP_User_Query($args);

        	$query_users = $user_query->get_results();

            if (sizeof($query_users) == 1) {

                $username = $query_users[0]->user_login;
            }
        }
    }
}

?>
